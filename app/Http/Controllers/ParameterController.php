<?php

namespace App\Http\Controllers;

use App\Models\Parameters;
use Illuminate\Http\Request;

class ParameterController extends Controller
{
    public function index(){
        $parameters = Parameters::all();
        
        return view('parameters.index', ['title' => 'Parametros', 'parameters' => $parameters]);
    }
}
