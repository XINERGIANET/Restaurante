<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Branch; 
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class RecipeBookController extends Controller
{
    public function index(Request $request)
    {
        $currentBranchId = session('branch_id');         
        $companyId = Branch::where('id', $currentBranchId)->value('company_id');
        $companyBranchIds = Branch::where('company_id', $companyId)->pluck('id');
        $productsForRecipe = Product::whereHas('branches', function ($query) use ($companyBranchIds) {
                $query->whereIn('branches.id', $companyBranchIds);
            })
            ->where('recipe', true) 
            ->whereNotIn('id', function($subquery) use ($companyId) {
                $subquery->select('product_id')
                            ->from('recipes')
                            ->where('company_id', $companyId);
            })
            ->distinct() 
            ->orderBy('description')
            ->get();

        $ingredientsList = Product::whereHas('branches', function ($query) use ($companyBranchIds) {
                $query->whereIn('branches.id', $companyBranchIds);
            })
            ->with(['branches' => function($query) use ($currentBranchId) {
                $query->where('branches.id', $currentBranchId);
            }])
            ->where('type', 'INGREDENT') 
            ->orderBy('description')
            ->get()
            ->map(function ($product) {
                $branch = $product->branches->first();
                $product->current_price = ($branch && $branch->pivot) ? $branch->pivot->price : 0;
                
                return $product;
            });

        $query = Recipe::query()->where('company_id', $companyId);

        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->whereHas('product', function($p) use ($search) {
                    $p->where('description', 'like', $search)
                    ->orWhere('code', 'like', $search);
                })
                ->orWhere('description', 'like', $search);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $recipes = $query->with(['product', 'unit'])->paginate(12);
        $categories = Category::get();
        $units = Unit::all();
        $recipe = new Recipe();

        $totalRecipes = Recipe::where('company_id', $companyId)->count();
        $activeRecipes = Recipe::where('company_id', $companyId)->where('status', 'A')->count();
        $inactiveRecipes = Recipe::where('company_id', $companyId)->where('status', 'I')->count();

        $viewId = $request->input('view_id');

        return view('recipe_book.index', compact(
            'recipes', 
            'categories', 
            'totalRecipes', 
            'activeRecipes', 
            'inactiveRecipes', 
            'viewId', 
            'units', 
            'productsForRecipe', 
            'ingredientsList', 
            'recipe'
        ));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
                'product_id'         => 'required|exists:products,id',
                'yield_unit_id'      => 'required|exists:units,id',
                'yield_quantity'     => 'required|numeric|min:0.01',
                'status'             => 'required|in:A,I',
                'preparation_time'   => 'nullable|integer|min:0',
                'preparation_method' => 'nullable|string',
                'description'        => 'nullable|string',
                'notes'              => 'nullable|string',
                'ingredients'        => 'required|array|min:1',
                'ingredients.*.product_id' => 'required|exists:products,id',
                'ingredients.*.quantity'   => 'required|numeric|min:0.0001',
                'ingredients.*.unit_cost'  => 'required|numeric|min:0',
            ]);

        DB::beginTransaction();

        try {
            $branchId = session('branch_id');
            $branch = Branch::findOrFail($branchId);
            $companyId = $branch->company_id;
            $baseProduct = Product::findOrFail($validated['product_id']);
            $totalCost = collect($request->ingredients)->sum(function($item) {
                return (float)$item['quantity'] * (float)$item['unit_cost'];
            });

            $dataToInsert = [
                'product_id'         => $baseProduct->id,
                'code'               => $baseProduct->code,       
                'name'               => $baseProduct->description,
                'description'        => $validated['description'],
                'category_id'        => $baseProduct->category_id,
                'yield_unit_id'      => $validated['yield_unit_id'],
                'preparation_time'   => $validated['preparation_time'],
                'preparation_method' => $validated['preparation_method'],
                'yield_quantity'     => $validated['yield_quantity'],
                'cost_total'         => $totalCost,
                'status'             => $validated['status'],
                'image'              => $baseProduct->image,      
                'notes'              => $validated['notes'],
                'branch_id'          => $branchId,
                'company_id'         => $companyId,
            ];

            $recipe = Recipe::create($dataToInsert);

            $this->saveIngredients($recipe->id, $validated['ingredients']);

            DB::commit();

            return redirect()->route('recipe-book.index')
                            ->with('success', 'Receta "' . $recipe->name . '" creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    private function saveIngredients($recipeId, $ingredients)
    {
        foreach ($ingredients as $index => $item) {
            $ingProduct = Product::find($item['product_id']);
            RecipeIngredient::create([
                'recipe_id' => $recipeId,
                'product_id' => $item['product_id'],
                'unit_id'    => $ingProduct->base_unit_id ?? null, 
                'quantity'   => $item['quantity'],
                'notes'      => $item['notes'] ?? null,
                'unit_cost'  => $item['unit_cost'],
                'order'      => $index,
            ]);
        }
    }






    public function show(Recipe $recipe)
    {
        // Seguridad: Verificar que sea de mi empresa
        if ($recipe->company_id !== Auth::user()->company_id) {
            abort(403, 'No autorizado');
        }
        
        $recipe->load(['ingredients.product', 'ingredients.unit', 'branch', 'product']);
        return view('recipe_book.show', compact('recipe'));
    }

    public function edit(Recipe $recipe)
    {
        if ($recipe->company_id !== Auth::user()->company_id) {
            abort(403, 'No autorizado');
        }

        $categories = Category::all();
        $units = Unit::all();
        
        // CORRECCIÓN BOOLEANA AQUÍ TAMBIÉN
        $products = Product::where('recipe', true)
                           ->orderBy('description')
                           ->get();
                           
        // Sucursales para editar el alcance
        $branches = Branch::where('company_id', Auth::user()->company_id)->get();

        return view('recipe_book.edit', compact('recipe', 'categories', 'units', 'products', 'branches'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        if ($recipe->company_id !== Auth::user()->company_id) {
            abort(403, 'No autorizado');
        }

        $validated = $request->validate([
            // Permitimos cambiar el alcance (branch_id) pero no el producto padre ni empresa
            'branch_id'  => 'nullable|exists:branches,id', 
            
            'code' => 'required|string|unique:recipes,code,' . $recipe->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'yield_unit_id' => 'required|exists:units,id',
            'yield_quantity' => 'required|numeric|min:0.01',
            'preparation_time' => 'nullable|integer|min:0',
            'preparation_method' => 'nullable|string',
            'status' => 'required|in:A,I',
            'notes' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ingredients' => 'nullable|array',
            // ... validaciones de items internas igual que store ...
             'ingredients.*.product_id' => 'required', // simplificado para el ejemplo
             'ingredients.*.quantity' => 'required|numeric',
             'ingredients.*.unit_id' => 'required',
             'ingredients.*.unit_cost' => 'required|numeric',
             'ingredients.*.notes' => 'nullable',
        ]);

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                if ($recipe->image) {
                    Storage::disk('public')->delete($recipe->image);
                }
                $recipe->image = $request->file('image')->store('recipes', 'public');
            }

            $recipe->update([
                'branch_id' => $validated['branch_id'] ?? null, // Actualizar alcance
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category_id' => $validated['category_id'],
                'yield_unit_id' => $validated['yield_unit_id'],
                'yield_quantity' => $validated['yield_quantity'],
                'preparation_time' => $validated['preparation_time'],
                'preparation_method' => $validated['preparation_method'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                // No actualizamos image aquí si no cambió, Laravel lo maneja
            ]);

            // Re-guardar ingredientes
            $recipe->ingredients()->delete();
            
            if (isset($validated['ingredients']) && !empty($validated['ingredients'])) {
                $this->saveIngredients($recipe, $validated['ingredients']);
            } else {
                // Si borraron todos los ingredientes, el costo es 0
                $recipe->update(['cost_total' => 0]);
            }

            DB::commit();

            return redirect()->route('recipe-book.show', $recipe)
                           ->with('success', 'Receta actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy(Recipe $recipe)
    {
        if ($recipe->company_id !== Auth::user()->company_id) {
            abort(403);
        }
        
        try {
            if ($recipe->image && Storage::disk('public')->exists($recipe->image)) {
                Storage::disk('public')->delete($recipe->image);
            }
            $recipe->delete();
            return redirect()->route('recipe-book.index')->with('success', 'Eliminado');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}