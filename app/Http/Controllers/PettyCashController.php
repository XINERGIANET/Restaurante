<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        return view('petty_cash.index');
    }
}
