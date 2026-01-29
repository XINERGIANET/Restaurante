<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    public function index(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $people = $branch->people()
            ->with('location')
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
        ] + $this->getLocationData());
    }

    public function store(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $data = $this->validatePerson($request);
        $data['branch_id'] = $branch->id;

        $branch->people()->create($data);

        return redirect()
            ->route('admin.companies.branches.people.index', [$company, $branch])
            ->with('status', 'Personal creado correctamente.');
    }

    public function edit(Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);

        return view('branches.people.edit', [
            'company' => $company,
            'branch' => $branch,
            'person' => $person,
        ] + $this->getLocationData($person));
    }

    public function update(Request $request, Company $company, Branch $branch, Person $person)
    {
        $branch = $this->resolveBranch($company, $branch);
        $person = $this->resolvePerson($branch, $person);
        $data = $this->validatePerson($request);

        $person->update($data);

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
