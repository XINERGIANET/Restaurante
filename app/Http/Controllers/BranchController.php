<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $branches = $company->branches()
            ->with('location')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('legal_name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('branches.index', [
            'company' => $company,
            'branches' => $branches,
            'search' => $search,
            'perPage' => $perPage,
        ] + $this->getLocationData());
    }

    public function create(Company $company)
    {
        return view('branches.create', [
            'company' => $company,
        ] + $this->getLocationData());
    }

    public function store(Request $request, Company $company)
    {
        $data = $this->validateBranch($request);
        $data['company_id'] = $company->id;
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branches/logos', 'public');
            $data['logo'] = Storage::url($path);
        }

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
        ] + $this->getLocationData($branch));
    }

    public function profiles(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $profiles = Profile::query()
            ->whereNull('profiles.deleted_at')
            ->whereExists(function ($query) use ($branch) {
                $query->select(DB::raw(1))
                    ->from('profile_branch')
                    ->whereColumn('profile_branch.profile_id', 'profiles.id')
                    ->where('profile_branch.branch_id', $branch->id)
                    ->whereNull('profile_branch.deleted_at');
            })
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('profiles.name')
            ->paginate($perPage)
            ->withQueryString();

        return view('branches.profiles.index', [
            'company' => $company,
            'branch' => $branch,
            'profiles' => $profiles,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function profilePermissions(Request $request, Company $company, Branch $branch, Profile $profile)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureProfileAssignedToBranch($profile->id, $branch->id);

        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $permissions = DB::table('user_permission')
            ->join('menu_option', 'menu_option.id', '=', 'user_permission.menu_option_id')
            ->join('modules', 'modules.id', '=', 'menu_option.module_id')
            ->where('user_permission.profile_id', $profile->id)
            ->where('user_permission.branch_id', $branch->id)
            ->whereNull('user_permission.deleted_at')
            ->when($search, function ($query) use ($search) {
                $query->where('user_permission.name', 'like', "%{$search}%");
            })
            ->orderBy('modules.name')
            ->orderBy('user_permission.name')
            ->select([
                'user_permission.id',
                'user_permission.name',
                'user_permission.status',
                'modules.name as module_name',
            ])
            ->paginate($perPage)
            ->withQueryString();

        return view('branches.profiles.permissions.index', [
            'company' => $company,
            'branch' => $branch,
            'profile' => $profile,
            'permissions' => $permissions,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function toggleProfilePermission(Company $company, Branch $branch, Profile $profile, string $permission)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureProfileAssignedToBranch($profile->id, $branch->id);

        $record = DB::table('user_permission')
            ->where('id', $permission)
            ->where('profile_id', $profile->id)
            ->where('branch_id', $branch->id)
            ->whereNull('deleted_at')
            ->first();

        if (!$record) {
            abort(404);
        }

        DB::table('user_permission')
            ->where('id', $permission)
            ->update([
                'status' => !$record->status,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.companies.branches.profiles.permissions.index', [$company, $branch, $profile])
            ->with('status', 'Permiso actualizado correctamente.');
    }

    public function update(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $data = $this->validateBranch($request);
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branches/logos', 'public');
            $data['logo'] = Storage::url($path);
        } else {
            unset($data['logo']);
        }

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
            'logo' => ['nullable', 'image', 'max:2048'],
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

    private function getLocationData(?Branch $branch = null): array
    {
        
        $departments = Location::query()
            ->where('type', 'department')
            ->orderBy('name')
            ->get(['id', 'name']);

        $provinces = Location::query()
            ->where('type', 'province')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_location_id']);

        $districts = Location::query()
            ->where('type', 'district')
            ->orderBy('name')
            ->get(['id', 'name', 'parent_location_id']);

        $selectedDistrictId = $branch?->location_id;
        $selectedProvinceId = null;
        $selectedDepartmentId = null;

        if ($selectedDistrictId) {
            $district = Location::find($selectedDistrictId);
            
            if ($district) {
                $province = $district->parent;
                $selectedProvinceId = $province?->id;
                $selectedDepartmentId = $province?->parent_location_id;
            }
        }

        return [
            'departments' => $departments,
            'provinces' => $provinces,
            'districts' => $districts,
            'selectedDepartmentId' => $selectedDepartmentId,
            'selectedProvinceId' => $selectedProvinceId,
            'selectedDistrictId' => $selectedDistrictId,
        ];
    }

    private function ensureProfileAssignedToBranch(int $profileId, int $branchId): void
    {
        $assigned = DB::table('profile_branch')
            ->where('profile_id', $profileId)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$assigned) {
            abort(404);
        }
    }
}
