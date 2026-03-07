<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        $viewId = $request->input('view_id');
        $branchId = (int) ($request->input('branch_id') ?? $request->session()->get('branch_id') ?? 0) ?: null;
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $operaciones = collect();
        if ($viewId && $branchId && $profileId) {
            $operaciones = Operation::query()
                ->select('operations.*')
                ->join('branch_operation', function ($join) use ($branchId) {
                    $join->on('branch_operation.operation_id', '=', 'operations.id')
                        ->where('branch_operation.branch_id', $branchId)
                        ->where('branch_operation.status', 1)
                        ->whereNull('branch_operation.deleted_at');
                })
                ->join('operation_profile_branch', function ($join) use ($branchId, $profileId) {
                    $join->on('operation_profile_branch.operation_id', '=', 'operations.id')
                        ->where('operation_profile_branch.branch_id', $branchId)
                        ->where('operation_profile_branch.profile_id', $profileId)
                        ->where('operation_profile_branch.status', 1)
                        ->whereNull('operation_profile_branch.deleted_at');
                })
                ->where('operations.status', 1)
                ->where('operations.view_id', $viewId)
                ->whereNull('operations.deleted_at')
                ->orderBy('operations.id')
                ->distinct()
                ->get();
        }

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        // Solo categorías que tienen registro en category_branch para ESTA sucursal
        $categoriesQuery = Category::query();

        if ($branchId !== null) {
            $categoryIdsInBranch = DB::table('category_branch')
                ->where('branch_id', $branchId)
                ->whereNull('deleted_at')
                ->pluck('category_id')
                ->unique()
                ->values()
                ->all();
            $categoriesQuery->whereIn('id', $categoryIdsInBranch);
        } else {
            $categoriesQuery->whereRaw('1 = 0');
        }

        $categories = $categoriesQuery
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'ILIKE', "%{$search}%")
                        ->orWhere('abbreviation', 'ILIKE', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
            'search' => $search,
            'perPage' => $perPage,
            'operaciones' => $operaciones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('category', 'public');
            $data['image'] = $path;
        }

        $category = Category::create($data);

        $branchId = $request->session()->get('branch_id');
        if ($branchId) {
            $now = now();
            DB::table('category_branch')->insert([
                'category_id' => $category->id,
                'branch_id' => $branchId,
                'menu_type' => 'PLATOS A LA CARTA',
                'status' => 'E',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $viewId = $request->input('view_id');

        return redirect()
            ->route('categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Categoria creada correctamente.');
    }

    public function edit(Request $request, Category $category)
    {
        return view('categories.edit', [
            'category' => $category,
            'viewId' => $request->input('view_id'),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'description'  => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'image'        => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }


            $path = $request->file('image')->store('category', 'public');
            $data['image'] = $path;
        }
        
        $category->update($data);
        $viewId = $request->input('view_id');

        return redirect()
            ->route('categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Categoria actualizada correctamente.');
    }

    /**
     * Elimina la categoría solo de la sucursal actual (desvincula en category_branch).
     * La categoría maestra se mantiene; otras sucursales siguen pudiendo usarla.
     */
    public function destroy(Request $request, Category $category)
    {
        $branchId = (int) ($request->input('branch_id') ?? $request->session()->get('branch_id') ?? 0);
        if (!$branchId) {
            return redirect()
                ->route('categories.index', $request->only('view_id'))
                ->with('error', 'Debe tener una sucursal seleccionada para eliminar una categoría de ella.');
        }

        $updated = DB::table('category_branch')
            ->where('category_id', $category->id)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        $viewId = $request->input('view_id');
        if ($updated) {
            return redirect()
                ->route('categories.index', $viewId ? ['view_id' => $viewId] : [])
                ->with('status', 'Categoría eliminada de esta sucursal.');
        }

        return redirect()
            ->route('categories.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('error', 'La categoría no estaba asignada a esta sucursal.');
    }

    /**
     * Sincroniza categorías existentes a todas las sucursales: para cada categoría y cada sucursal,
     * si no existe la pareja (category_id, branch_id) en category_branch, se inserta.
     */
    private function syncExistingCategoriesToAllBranches(): void
    {
        $categoryIds = Category::query()->pluck('id')->all();
        $branchIds = Branch::query()->pluck('id')->all();
        if (empty($categoryIds) || empty($branchIds)) {
            return;
        }
        $now = now();
        foreach ($categoryIds as $categoryId) {
            foreach ($branchIds as $branchId) {
                $exists = DB::table('category_branch')
                    ->where('category_id', $categoryId)
                    ->where('branch_id', $branchId)
                    ->whereNull('deleted_at')
                    ->exists();
                if (!$exists) {
                    DB::table('category_branch')->insert([
                        'category_id' => $categoryId,
                        'branch_id' => $branchId,
                        'menu_type' => 'GENERAL',
                        'status' => 'E',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }
}
