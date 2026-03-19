<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Category extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description',
        'abbreviation',
        'image',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function scopeForBranchMenu($query, int $branchId, string $menuType)
    {
        return $query->whereExists(function ($sub) use ($branchId, $menuType) {
            $sub->select(DB::raw(1))
                ->from('category_branch')
                ->whereColumn('category_branch.category_id', 'categories.id')
                ->where('category_branch.branch_id', $branchId)
                ->whereIn('category_branch.menu_type', [$menuType, 'GENERAL'])
                ->whereNull('category_branch.deleted_at');
        });
    }
}
