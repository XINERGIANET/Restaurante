<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('modules')
            || ! Schema::hasTable('menu_option')
            || ! Schema::hasTable('views')
        ) {
            return;
        }

        $module = DB::table('modules')
            ->whereRaw('LOWER(TRIM(name)) = ?', ['pedidos'])
            ->first();

        if (! $module) {
            return;
        }

        $viewId = DB::table('menu_option')
            ->where('module_id', $module->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('view_id');

        if (! $viewId) {
            $viewId = DB::table('views')->orderBy('id')->value('id');
        }

        if (! $viewId) {
            $viewId = DB::table('views')->insertGetId([
                'name' => 'Vista General',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $existing = DB::table('menu_option')
            ->where('module_id', $module->id)
            ->where(function ($query) {
                $query->where('action', 'orders.commands.index')
                    ->orWhereRaw('LOWER(TRIM(name)) = ?', ['comandas']);
            })
            ->first();

        if ($existing) {
            DB::table('menu_option')
                ->where('id', $existing->id)
                ->update([
                    'name' => 'Comandas',
                    'icon' => 'ri-file-list-3-line',
                    'action' => 'orders.commands.index',
                    'view_id' => $existing->view_id ?: $viewId,
                    'module_id' => $module->id,
                    'status' => 1,
                    'quick_access' => false,
                    'deleted_at' => null,
                    'updated_at' => now(),
                ]);
            $menuOptionId = (int) $existing->id;
        } else {
            $menuOptionId = (int) DB::table('menu_option')->insertGetId([
                'name' => 'Comandas',
                'icon' => 'ri-file-list-3-line',
                'action' => 'orders.commands.index',
                'view_id' => $viewId,
                'module_id' => $module->id,
                'status' => 1,
                'quick_access' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! Schema::hasTable('user_permission')) {
            return;
        }

        $sourceMenuIds = DB::table('menu_option')
            ->where('module_id', $module->id)
            ->where('id', '!=', $menuOptionId)
            ->whereNull('deleted_at')
            ->pluck('id');

        if ($sourceMenuIds->isEmpty()) {
            return;
        }

        $permissionTargets = DB::table('user_permission')
            ->select('profile_id', 'branch_id')
            ->whereIn('menu_option_id', $sourceMenuIds)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->distinct()
            ->get();

        foreach ($permissionTargets as $target) {
            $exists = DB::table('user_permission')
                ->where('profile_id', $target->profile_id)
                ->where('branch_id', $target->branch_id)
                ->where('menu_option_id', $menuOptionId)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('user_permission')->insert([
                'id' => (string) Str::uuid(),
                'name' => 'Comandas',
                'profile_id' => $target->profile_id,
                'menu_option_id' => $menuOptionId,
                'branch_id' => $target->branch_id,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menu_option')) {
            return;
        }

        $ids = DB::table('menu_option')
            ->where('action', 'orders.commands.index')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        if (Schema::hasTable('user_permission')) {
            DB::table('user_permission')
                ->whereIn('menu_option_id', $ids)
                ->delete();
        }

        DB::table('menu_option')
            ->whereIn('id', $ids)
            ->delete();
    }
};
