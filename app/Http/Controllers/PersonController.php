<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Person;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonController extends Controller
{
    public function index(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $search = $request->input('search');
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $profiles = Profile::query()->orderBy('name')->get(['id', 'name']);

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $people = $branch->people()
            ->with(['location', 'user.profile'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('branches.people.index', [
            'company' => $company,
            'branch' => $branch,
            'people' => $people,
            'search' => $search,
            'perPage' => $perPage,
            'roles' => $roles,
            'profiles' => $profiles,
            'selectedRoleIds' => old('roles', []),
            'selectedProfileId' => old('profile_id'),
            'userName' => old('user_name'),
        ] + $this->getLocationData());
    }

    public function store(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $data = $this->validatePerson($request);
        $data['branch_id'] = $branch->id;
        $roleIds = $this->validateRoles($request);
        $hasUserRole = in_array(1, $roleIds, true);
        $userData = $this->validateUserData($request, $hasUserRole, null);

        DB::transaction(function () use ($branch, $data, $roleIds, $hasUserRole, $userData) {
            $person = $branch->people()->create($data);
            $this->syncRoles($person, $roleIds, $branch->id);

            if ($hasUserRole) {
                User::create([
                    'name' => $userData['user_name'],
                    'email' => $person->email,
                    'password' => Hash::make($userData['password']),
                    'person_id' => $person->id,
                    'profile_id' => $userData['profile_id'],
                ]);
            }
        });

        return redirect()
            ->route('admin.companies.branches.people.index', [$company, $branch])
            ->with('status', 'Personal creado correctamente.');
    }

    public function edit(Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);
        $roles = Role::query()->orderBy('name')->get(['id', 'name']);
        $profiles = Profile::query()->orderBy('name')->get(['id', 'name']);
        $selectedRoleIds = old('roles', $person->roles()->pluck('roles.id')->all());
        $user = $person->user;

        return view('branches.people.edit', [
            'company' => $company,
            'branch' => $branch,
            'person' => $person,
            'roles' => $roles,
            'profiles' => $profiles,
            'selectedRoleIds' => $selectedRoleIds,
            'selectedProfileId' => old('profile_id', $user?->profile_id),
            'userName' => old('user_name', $user?->name),
        ] + $this->getLocationData($person));
    }

    public function update(Request $request, Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);
        $data = $this->validatePerson($request);
        $roleIds = $this->validateRoles($request);
        $hasUserRole = in_array(1, $roleIds, true);
        $userData = $this->validateUserData($request, $hasUserRole, $person);

        DB::transaction(function () use ($person, $branch, $data, $roleIds, $hasUserRole, $userData) {
            $person->update($data);
            $this->syncRoles($person, $roleIds, $branch->id);

            if ($hasUserRole) {
                $user = $person->user;
                if ($user) {
                    $user->update([
                        'name' => $userData['user_name'],
                        'email' => $person->email,
                        'profile_id' => $userData['profile_id'],
                    ]);
                    if (!empty($userData['password'])) {
                        $user->update([
                            'password' => Hash::make($userData['password']),
                        ]);
                    }
                } else {
                    User::create([
                        'name' => $userData['user_name'],
                        'email' => $person->email,
                        'password' => Hash::make($userData['password']),
                        'person_id' => $person->id,
                        'profile_id' => $userData['profile_id'],
                    ]);
                }
            }
        });

        return redirect()
            ->route('admin.companies.branches.people.index', [$company, $branch])
            ->with('status', 'Personal actualizado correctamente.');
    }

    public function destroy(Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);
        $person->delete();

        return redirect()
            ->route('admin.companies.branches.people.index', [$company, $branch])
            ->with('status', 'Personal eliminado correctamente.');
    }

    public function updatePassword(Request $request, Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $person->user;
        if (!$user) {
            return redirect()
                ->route('admin.companies.branches.people.index', [$company, $branch])
                ->with('status', 'La persona no tiene usuario asociado.');
        }

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('admin.companies.branches.people.index', [$company, $branch])
            ->with('status', 'Contraseña actualizada correctamente.');
    }

    private function validatePerson(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['nullable', 'date'],
            'genero' => ['nullable', 'string', 'max:30'],
            'person_type' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'document_number' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);
    }

    private function validateRoles(Request $request): array
    {
        $validated = $request->validate([
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
        ]);

        return array_values(array_unique(array_map('intval', $validated['roles'] ?? [])));
    }

    private function validateUserData(Request $request, bool $hasUserRole, ?Person $person): array
    {
        if (!$hasUserRole) {
            return [];
        }

        $rules = [
            'user_name' => ['required', 'string', 'max:255'],
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
        ];

        if ($person && $person->user) {
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        return $request->validate($rules);
    }

    private function syncRoles(Person $person, array $roleIds, int $branchId): void
    {
        $syncData = [];
        foreach ($roleIds as $roleId) {
            $syncData[$roleId] = ['branch_id' => $branchId];
        }
        $person->roles()->sync($syncData);
    }

    private function resolveBranch(Company $company, Branch $branch): Branch
    {
        if ($branch->company_id !== $company->id) {
            abort(404);
        }

        return $branch;
    }

    private function resolvePerson(Branch $branch, Person $person): Person
    {
        if ($person->branch_id !== $branch->id) {
            abort(404);
        }

        return $person;
    }

    private function getLocationData(?Person $person = null): array
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

        $selectedDistrictId = $person?->location_id;
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
}
