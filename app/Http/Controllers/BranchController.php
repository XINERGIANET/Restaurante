<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $search = $request->input('search');

        $branches = $company->branches()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('legal_name', 'ilike', "%{$search}%")
                        ->orWhere('tax_id', 'ilike', "%{$search}%")
                        ->orWhere('address', 'ilike', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('branches.index', [
            'company' => $company,
            'branches' => $branches,
            'search' => $search,
        ]);
    }

    public function create(Company $company)
    {
        return view('branches.create', [
            'company' => $company,
        ]);
    }

    public function store(Request $request, Company $company)
    {
        $data = $this->validateBranch($request);
        $data['company_id'] = $company->id;

        $company->branches()->create($data);

        return redirect()
            ->route('admin.companies.branches.index', $company)
            ->with('status', 'Sucursal creada correctamente.');
    }

    public function show(Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);

        return view('branches.show', [
            'company' => $company,
            'branch' => $branch,
        ]);
    }

    public function edit(Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);

        return view('branches.edit', [
            'company' => $company,
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $data = $this->validateBranch($request);

        $branch->update($data);

        return redirect()
            ->route('admin.companies.branches.index', $company)
            ->with('status', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $branch->delete();

        return redirect()
            ->route('admin.companies.branches.index', $company)
            ->with('status', 'Sucursal eliminada correctamente.');
    }

    private function validateBranch(Request $request): array
    {
        return $request->validate([
            'tax_id' => ['required', 'string', 'max:255'],
            'ruc' => ['nullable', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);
    }

    private function resolveBranch(Company $company, Branch $branch): Branch
    {
        if ($branch->company_id !== $company->id) {
            abort(404);
        }

        return $branch;
    }
}
