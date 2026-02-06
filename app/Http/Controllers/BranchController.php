<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Location;
use App\Models\Module;
use App\Models\Operation;
use App\Models\Profile;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BranchController extends Controller
{
    public function index(Request $request, Company $company)
    {
        $search = $request->input('search');
        $viewId = $request->input('view_id');
        $branchId = $request->session()->get('branch_id');
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $operaciones = collect();
        if ($viewId) {
            $request->session()->put('branch_view_id', $viewId);
        }

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
            'operaciones' => $operaciones,
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

        $params = [];
        if ($request->filled('view_id')) {
            $params['view_id'] = $request->input('view_id');
        }
        if ($request->filled('company_view_id')) {
            $params['company_view_id'] = $request->input('company_view_id');
        }
        if ($request->filled('icon')) {
            $params['icon'] = $request->input('icon');
        }
        $redirectParams = !empty($params) ? array_merge([$company], $params) : $company;

        return redirect()
            ->route('admin.companies.branches.index', $redirectParams)
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
        if ($request->filled('view_id')) {
            $request->session()->put('profile_view_id', $request->input('view_id'));
        }
        $viewId = $request->input('view_id');
        $branchId = $request->session()->get('branch_id');
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
            'operaciones' => $operaciones,
        ]);
    }

    public function viewsIndex(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $search = $request->input('search');

        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $assignedViewIds = DB::table('view_branch')
            ->where('branch_id', $branch->id)
            ->whereNull('deleted_at')
            ->pluck('view_id')
            ->all();

        $assignedViews = View::query()
            ->whereIn('id', $assignedViewIds)
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('abbreviation', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $allViews = View::query()
            ->orderBy('name')
            ->get(['id', 'name', 'abbreviation', 'status']);

        return view('branches.views.index', [
            'company' => $company,
            'branch' => $branch,
            'assignedViews' => $assignedViews,
            'allViews' => $allViews,
            'assignedViewIds' => $assignedViewIds,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function updateViews(Request $request, Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $data = $request->validate([
            'views' => ['nullable', 'array'],
            'views.*' => ['integer', 'exists:views,id'],
        ]);

        $viewIds = array_values(array_unique(array_map('intval', $data['views'] ?? [])));

        DB::transaction(function () use ($branch, $viewIds) {
            DB::table('view_branch')
                ->where('branch_id', $branch->id)
                ->whereNull('deleted_at')
                ->whereNotIn('view_id', $viewIds)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            foreach ($viewIds as $viewId) {
                $existing = DB::table('view_branch')
                    ->where('branch_id', $branch->id)
                    ->where('view_id', $viewId)
                    ->first();

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        DB::table('view_branch')
                            ->where('branch_id', $branch->id)
                            ->where('view_id', $viewId)
                            ->update(['deleted_at' => null, 'updated_at' => now()]);
                    }
                    continue;
                }

                DB::table('view_branch')->insert([
                    'branch_id' => $branch->id,
                    'view_id' => $viewId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.companies.branches.views.index', [$company, $branch])
            ->with('status', 'Vistas asignadas correctamente.');
    }

    public function removeViewAssignment(Company $company, Branch $branch, View $view)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureViewAssignedToBranch($view->id, $branch->id);

        $viewBranch = DB::table('view_branch')
            ->where('branch_id', $branch->id)
            ->where('view_id', $view->id)
            ->whereNull('deleted_at')
            ->first();

        DB::table('view_branch')
            ->where('branch_id', $branch->id)
            ->where('view_id', $view->id)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        return redirect()
            ->route('admin.companies.branches.views.index', [$company, $branch])
            ->with('status', 'Vista desasignada correctamente.')
            ->with('viewBranch', $viewBranch);
    }

    public function viewOperationsIndex(Request $request, Company $company, Branch $branch, View $view)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureViewAssignedToBranch($view->id, $branch->id);

        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $operations = DB::table('branch_operation')
            ->join('operations', 'operations.id', '=', 'branch_operation.operation_id')
            ->where('branch_operation.branch_id', $branch->id)
            ->whereNull('branch_operation.deleted_at')
            ->whereNull('operations.deleted_at')
            ->where('operations.view_id', $view->id)
            ->when($search, function ($query) use ($search) {
                $query->where('operations.name', 'like', "%{$search}%");
            })
            ->orderBy('operations.name')
            ->select([
                'branch_operation.id',
                'branch_operation.status',
                'operations.name',
                'operations.icon',
                'operations.action',
                'operations.color',
            ])
            ->paginate($perPage)
            ->withQueryString();

        $assignedOperationIds = DB::table('branch_operation')
            ->where('branch_id', $branch->id)
            ->whereNull('deleted_at')
            ->pluck('operation_id')
            ->all();

        $availableOperations = DB::table('operations')
            ->where('view_id', $view->id)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'action', 'color']);

        return view('branches.views.operations', [
            'company' => $company,
            'branch' => $branch,
            'view' => $view,
            'operations' => $operations,
            'availableOperations' => $availableOperations,
            'assignedOperationIds' => $assignedOperationIds,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function assignViewOperations(Request $request, Company $company, Branch $branch, View $view)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureViewAssignedToBranch($view->id, $branch->id);

        $data = $request->validate([
            'operations' => ['nullable', 'array'],
            'operations.*' => ['integer', 'exists:operations,id'],
        ]);

        $operationIds = array_values(array_unique(array_map('intval', $data['operations'] ?? [])));

        DB::transaction(function () use ($branch, $view, $operationIds) {
            DB::table('branch_operation')
                ->where('branch_id', $branch->id)
                ->whereIn('operation_id', function ($query) use ($view) {
                    $query->select('id')
                        ->from('operations')
                        ->where('view_id', $view->id)
                        ->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->whereNotIn('operation_id', $operationIds)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            foreach ($operationIds as $operationId) {
                $operation = DB::table('operations')
                    ->where('id', $operationId)
                    ->where('view_id', $view->id)
                    ->whereNull('deleted_at')
                    ->first();

                if (!$operation) {
                    continue;
                }

                $existing = DB::table('branch_operation')
                    ->where('branch_id', $branch->id)
                    ->where('operation_id', $operationId)
                    ->first();

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        DB::table('branch_operation')
                            ->where('branch_id', $branch->id)
                            ->where('operation_id', $operationId)
                            ->update(['deleted_at' => null, 'updated_at' => now()]);
                    }
                    continue;
                }

                DB::table('branch_operation')->insert([
                    'branch_id' => $branch->id,
                    'operation_id' => $operationId,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.companies.branches.views.operations.index', [$company, $branch, $view])
            ->with('status', 'Operaciones asignadas correctamente.');
    }

    public function profileOperationsIndex(Request $request, Company $company, Branch $branch, Profile $profile)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureProfileAssignedToBranch($profile->id, $branch->id);

        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $operations = DB::table('operation_profile_branch')
            ->join('operations', 'operations.id', '=', 'operation_profile_branch.operation_id')
            ->join('branch_operation', function ($join) use ($branch) {
                $join->on('branch_operation.operation_id', '=', 'operation_profile_branch.operation_id')
                    ->where('branch_operation.branch_id', $branch->id)
                    ->whereNull('branch_operation.deleted_at');
            })
            ->where('operation_profile_branch.branch_id', $branch->id)
            ->where('operation_profile_branch.profile_id', $profile->id)
            ->whereNull('operation_profile_branch.deleted_at')
            ->whereNull('operations.deleted_at')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('operations.name', 'like', "%{$search}%")
                        ->orWhere('operations.action', 'like', "%{$search}%");
                });
            })
            ->orderBy('operations.name')
            ->select([
                'operations.id',
                'operation_profile_branch.status',
                'operations.name',
                'operations.icon',
                'operations.action',
                'operations.color',
            ])
            ->paginate($perPage)
            ->withQueryString();

        $assignedOperationIds = DB::table('operation_profile_branch')
            ->where('branch_id', $branch->id)
            ->where('profile_id', $profile->id)
            ->whereNull('deleted_at')
            ->pluck('operation_id')
            ->all();

        $availableOperations = DB::table('branch_operation')
            ->join('operations', 'operations.id', '=', 'branch_operation.operation_id')
            ->where('branch_operation.branch_id', $branch->id)
            ->whereNull('branch_operation.deleted_at')
            ->whereNull('operations.deleted_at')
            ->orderBy('operations.name')
            ->select([
                'operations.id',
                'operations.name',
                'operations.icon',
                'operations.action',
                'operations.color',
            ])
            ->get();

        return view('branches.profiles.operations', [
            'company' => $company,
            'branch' => $branch,
            'profile' => $profile,
            'operations' => $operations,
            'availableOperations' => $availableOperations,
            'assignedOperationIds' => $assignedOperationIds,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function assignProfileOperations(Request $request, Company $company, Branch $branch, Profile $profile)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureProfileAssignedToBranch($profile->id, $branch->id);

        $data = $request->validate([
            'operations' => ['nullable', 'array'],
            'operations.*' => ['integer', 'exists:operations,id'],
        ]);

        $operationIds = array_values(array_unique(array_map('intval', $data['operations'] ?? [])));

        DB::transaction(function () use ($branch, $profile, $operationIds) {
            DB::table('operation_profile_branch')
                ->where('branch_id', $branch->id)
                ->where('profile_id', $profile->id)
                ->whereIn('operation_id', function ($query) use ($branch) {
                    $query->select('operation_id')
                        ->from('branch_operation')
                        ->where('branch_id', $branch->id)
                        ->whereNull('deleted_at');
                })
                ->whereNull('deleted_at')
                ->whereNotIn('operation_id', $operationIds)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);

            foreach ($operationIds as $operationId) {
                $allowed = DB::table('branch_operation')
                    ->where('branch_id', $branch->id)
                    ->where('operation_id', $operationId)
                    ->whereNull('deleted_at')
                    ->exists();

                if (!$allowed) {
                    continue;
                }

                $existing = DB::table('operation_profile_branch')
                    ->where('branch_id', $branch->id)
                    ->where('profile_id', $profile->id)
                    ->where('operation_id', $operationId)
                    ->first();

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        DB::table('operation_profile_branch')
                            ->where('branch_id', $branch->id)
                            ->where('profile_id', $profile->id)
                            ->where('operation_id', $operationId)
                            ->update(['deleted_at' => null, 'updated_at' => now()]);
                    }
                    continue;
                }

                DB::table('operation_profile_branch')->insert([
                    'branch_id' => $branch->id,
                    'profile_id' => $profile->id,
                    'operation_id' => $operationId,
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.companies.branches.profiles.operations.index', [$company, $branch, $profile])
            ->with('status', 'Operaciones asignadas correctamente.');
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

        $modules = Module::query()
            ->where('status', 1)
            ->orderBy('order_num')
            ->with(['menuOptions' => function ($query) {
                $query->whereNull('menu_option.deleted_at')
                    ->where('menu_option.status', 1)
                    ->orderBy('menu_option.name')
                    ->select([
                        'menu_option.id',
                        'menu_option.name',
                        'menu_option.icon',
                        'menu_option.action',
                        'menu_option.module_id',
                        'menu_option.status',
                    ]);
            }])
            ->get(['id', 'name', 'icon', 'order_num', 'status']);

        $modules = $modules->filter(fn ($module) => $module->menuOptions->isNotEmpty())->values();

        $assignedMenuOptionIds = DB::table('user_permission')
            ->where('profile_id', $profile->id)
            ->where('branch_id', $branch->id)
            ->whereNull('deleted_at')
            ->pluck('menu_option_id')
            ->all();

        return view('branches.profiles.permissions.index', [
            'company' => $company,
            'branch' => $branch,
            'profile' => $profile,
            'permissions' => $permissions,
            'modules' => $modules,
            'assignedMenuOptionIds' => $assignedMenuOptionIds,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function assignProfilePermissions(Request $request, Company $company, Branch $branch, Profile $profile)
    {
        $branch = $this->resolveBranch($company, $branch);
        $this->ensureProfileAssignedToBranch($profile->id, $branch->id);

        $data = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:menu_option,id'],
        ]);

        $selectedIds = array_values(array_unique(array_map('intval', $data['permissions'] ?? [])));

        $allowedIds = DB::table('menu_option')
            ->join('modules', 'modules.id', '=', 'menu_option.module_id')
            ->whereNull('menu_option.deleted_at')
            ->whereNull('modules.deleted_at')
            ->where('menu_option.status', 1)
            ->where('modules.status', 1)
            ->pluck('menu_option.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $allowedIds = array_values(array_unique($allowedIds));
        $selectedIds = array_values(array_intersect($selectedIds, $allowedIds));

        $menuOptionNames = DB::table('menu_option')
            ->whereIn('id', $selectedIds)
            ->pluck('name', 'id');

        DB::transaction(function () use ($branch, $profile, $allowedIds, $selectedIds, $menuOptionNames) {
            if (!empty($allowedIds)) {
                $deleteQuery = DB::table('user_permission')
                    ->where('profile_id', $profile->id)
                    ->where('branch_id', $branch->id)
                    ->whereNull('deleted_at')
                    ->whereIn('menu_option_id', $allowedIds);

                if (!empty($selectedIds)) {
                    $deleteQuery->whereNotIn('menu_option_id', $selectedIds);
                }

                $deleteQuery->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($selectedIds as $menuOptionId) {
                $existing = DB::table('user_permission')
                    ->where('profile_id', $profile->id)
                    ->where('branch_id', $branch->id)
                    ->where('menu_option_id', $menuOptionId)
                    ->first();

                $name = $menuOptionNames[$menuOptionId] ?? '';

                if ($existing) {
                    if ($existing->deleted_at !== null) {
                        DB::table('user_permission')
                            ->where('id', $existing->id)
                            ->update([
                                'deleted_at' => null,
                                'status' => true,
                                'name' => $name ?: $existing->name,
                                'updated_at' => now(),
                            ]);
                    } elseif ($name && $existing->name !== $name) {
                        DB::table('user_permission')
                            ->where('id', $existing->id)
                            ->update([
                                'name' => $name,
                                'updated_at' => now(),
                            ]);
                    }

                    continue;
                }

                DB::table('user_permission')->insert([
                    'id' => (string) Str::uuid(),
                    'name' => $name,
                    'profile_id' => $profile->id,
                    'menu_option_id' => $menuOptionId,
                    'branch_id' => $branch->id,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        return redirect()
            ->route('admin.companies.branches.profiles.permissions.index', [$company, $branch, $profile])
            ->with('status', 'Permisos asignados correctamente.');
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

        $params = [];
        if ($request->filled('view_id')) {
            $params['view_id'] = $request->input('view_id');
        }
        if ($request->filled('company_view_id')) {
            $params['company_view_id'] = $request->input('company_view_id');
        }
        if ($request->filled('icon')) {
            $params['icon'] = $request->input('icon');
        }
        $redirectParams = !empty($params) ? array_merge([$company], $params) : $company;

        return redirect()
            ->route('admin.companies.branches.index', $redirectParams)
            ->with('status', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Company $company, Branch $branch)
    {
        $branch = $this->resolveBranch($company, $branch);
        $branch->delete();

        $params = [];
        if (request()->filled('view_id')) {
            $params['view_id'] = request()->input('view_id');
        }
        if (request()->filled('company_view_id')) {
            $params['company_view_id'] = request()->input('company_view_id');
        }
        if (request()->filled('icon')) {
            $params['icon'] = request()->input('icon');
        }
        $redirectParams = !empty($params) ? array_merge([$company], $params) : $company;

        return redirect()
            ->route('admin.companies.branches.index', $redirectParams)
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

    private function ensureViewAssignedToBranch(int $viewId, int $branchId): void
    {
        $assigned = DB::table('view_branch')
            ->where('view_id', $viewId)
            ->where('branch_id', $branchId)
            ->whereNull('deleted_at')
            ->exists();

        if (!$assigned) {
            abort(404);
        }
    }
}
