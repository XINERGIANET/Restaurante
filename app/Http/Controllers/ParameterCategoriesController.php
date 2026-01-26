<?php

namespace App\Http\Controllers;

use App\Models\ParameterCategories;
use Illuminate\Http\Request;

class ParameterCategoriesController extends Controller
{
    public function index(){
        $parameterCategories = ParameterCategories::all();
        return view('parameters.categories', ['parameterCategories' => $parameterCategories]);
    }
}
