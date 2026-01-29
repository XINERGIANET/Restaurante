<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $profiles = Profile::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('profiles.index', [
            'profiles' => $profiles,
            'search' => $search,
            'perPage' => $perPage,
            'title' => 'Perfiles',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProfile($request);

        DB::transaction(function () use ($data) {
            $profile = Profile::create($data);
            $branchIds = Branch::query()->pluck('id');

            if ($branchIds->isNotEmpty()) {
                $now = now();
                $rows = $branchIds->map(fn ($branchId) => [
                    'profile_id' => $profile->id,
                    'branch_id' => $branchId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('profile_branch')->insert($rows->all());
            }
        });

        return redirect()->route('admin.profiles.index')
            ->with('status', 'Perfil creado correctamente.');
    }

    public function edit(Profile $profile)
    {
        return view('profiles.edit', [
            'profile' => $profile,
            'title' => 'Perfiles',
        ]);
    }

    public function update(Request $request, Profile $profile)
    {
        $data = $this->validateProfile($request);
        $profile->update($data);

        return redirect()->route('admin.profiles.index')
            ->with('status', 'Perfil actualizado correctamente.');
    }

    public function destroy(Profile $profile)
    {
        $profile->delete();

        return redirect()->route('admin.profiles.index')
            ->with('status', 'Perfil eliminado correctamente.');
    }

    private function validateProfile(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
        ]);
    }
}
