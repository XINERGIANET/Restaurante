<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\ParameterCategoriesController;
use App\Models\ParameterCategories;

Route::prefix('restaurante')->name('restaurant.')->group(function () {
    Route::view('/', 'restaurant.home', ['title' => 'Xinergia Restaurante'])->name('home');
    Route::view('/menu', 'restaurant.menu', ['title' => 'Menu'])->name('menu');
    Route::view('/reservas', 'restaurant.reservations', ['title' => 'Reservas'])->name('reservations');
    Route::view('/historia', 'restaurant.about', ['title' => 'Historia'])->name('about');
    Route::view('/eventos', 'restaurant.events', ['title' => 'Eventos'])->name('events');
    Route::view('/galeria', 'restaurant.gallery', ['title' => 'Galeria'])->name('gallery');
    Route::view('/contacto', 'restaurant.contact', ['title' => 'Contacto'])->name('contact');
    Route::view('/sucursales', 'restaurant.locations', ['title' => 'Sucursales'])->name('locations');
});


Route::get('/signin', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::post('/signin', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('login.store');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::view('/signup', 'pages.auth.signup', ['title' => 'Sign Up'])
    ->middleware('guest')
    ->name('signup');

Route::middleware('auth')->group(function () {
    Route::resource('/admin/empresas', CompanyController::class)->names('admin.companies');

    // dashboard pages
    Route::get('/', function () {
        return view('pages.dashboard.ecommerce', ['title' => 'E-commerce Dashboard']);
    })->name('dashboard');

    // calender pages
    Route::get('/calendar', function () {
        return view('pages.calender', ['title' => 'Calendar']);
    })->name('calendar');

    // profile pages
    Route::get('/profile', function () {
        return view('pages.profile', ['title' => 'Profile']);
    })->name('profile');

    // form pages
    Route::get('/form-elements', function () {
        return view('pages.form.form-elements', ['title' => 'Form Elements']);
    })->name('form-elements');

    // tables pages
    Route::get('/basic-tables', function () {
        return view('pages.tables.basic-tables', ['title' => 'Basic Tables']);
    })->name('basic-tables');

    // pages
    Route::get('/blank', function () {
        return view('pages.blank', ['title' => 'Blank']);
    })->name('blank');

    // error pages
    Route::get('/error-404', function () {
        return view('pages.errors.error-404', ['title' => 'Error 404']);
    })->name('error-404');

    // chart pages
    Route::get('/line-chart', function () {
        return view('pages.chart.line-chart', ['title' => 'Line Chart']);
    })->name('line-chart');

    Route::get('/bar-chart', function () {
        return view('pages.chart.bar-chart', ['title' => 'Bar Chart']);
    })->name('bar-chart');

    // ui elements pages
    Route::get('/alerts', function () {
        return view('pages.ui-elements.alerts', ['title' => 'Alerts']);
    })->name('alerts');

    Route::get('/avatars', function () {
        return view('pages.ui-elements.avatars', ['title' => 'Avatars']);
    })->name('avatars');

    Route::get('/badge', function () {
        return view('pages.ui-elements.badges', ['title' => 'Badges']);
    })->name('badges');

    Route::get('/buttons', function () {
        return view('pages.ui-elements.buttons', ['title' => 'Buttons']);
    })->name('buttons');

    Route::get('/image', function () {
        return view('pages.ui-elements.images', ['title' => 'Images']);
    })->name('images');

    Route::get('/videos', function () {
        return view('pages.ui-elements.videos', ['title' => 'Videos']);
    })->name('videos');

    // Modulos administrativos
    Route::view('/admin/herramientas/usuarios', 'pages.blank', ['title' => 'Usuarios']);
    Route::view('/admin/herramientas/roles', 'pages.blank', ['title' => 'Roles y permisos']);
    Route::view('/admin/herramientas/sucursales', 'pages.blank', ['title' => 'Sucursales']);

    // Modulos
    Route::get('/admin/herramientas/modulos', [ModulesController::class, 'index'])->name('admin.modules.index');
    Route::post('/admin/herramientas/modulos', [ModulesController::class, 'store'])->name('admin.modules.store');
        
    Route::view('/admin/pedidos/ordenes', 'pages.blank', ['title' => 'Ordenes activas']);
    Route::view('/admin/pedidos/cocina', 'pages.blank', ['title' => 'Cocina']);
    Route::view('/admin/pedidos/delivery', 'pages.blank', ['title' => 'Delivery']);
    //parametros
    Route::get('/admin/herramientas/parametros/categorias', [ParameterCategoriesController::class, 'index'])->name('admin.parameters.categories');
    Route::view('/admin/ventas/pos', 'pages.blank', ['title' => 'POS']);
    Route::view('/admin/ventas/facturacion', 'pages.blank', ['title' => 'Facturacion']);
    Route::view('/admin/ventas/reportes', 'pages.blank', ['title' => 'Reportes']);

    Route::view('/admin/compras/proveedores', 'pages.blank', ['title' => 'Proveedores']);
    Route::view('/admin/compras/ordenes', 'pages.blank', ['title' => 'Ordenes de compra']);
    Route::view('/admin/compras/recepciones', 'pages.blank', ['title' => 'Recepciones']);

    Route::view('/admin/almacen/inventario', 'pages.blank', ['title' => 'Inventario']);
    Route::view('/admin/almacen/insumos', 'pages.blank', ['title' => 'Insumos']);
    Route::view('/admin/almacen/movimientos', 'pages.blank', ['title' => 'Movimientos']);

    Route::view('/admin/caja/aperturas', 'pages.blank', ['title' => 'Apertura y cierre']);
    Route::view('/admin/caja/arqueos', 'pages.blank', ['title' => 'Arqueos']);
    Route::view('/admin/caja/gastos', 'pages.blank', ['title' => 'Gastos']);

    Route::view('/admin/configuracion/parametros', 'pages.blank', ['title' => 'Parametros']);
    Route::view('/admin/configuracion/menu', 'pages.blank', ['title' => 'Menu y recetas']);
    Route::view('/admin/configuracion/impuestos', 'pages.blank', ['title' => 'Impuestos']);
});
