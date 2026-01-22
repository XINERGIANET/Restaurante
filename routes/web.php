<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CashBoxController;
use App\Http\Controllers\CashCloseController;
use App\Http\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


require __DIR__ . '/auth.php';

Route::get('/storage', function () {
    Artisan::call('storage:link');
});

Route::get('/', function () {
    return view('auth.login');
})->middleware('guest');

// Ruta de prueba para renderizar la vista de prueba que incluye el componente Delivery

Route::group(['middleware' => 'auth'], function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');


    Route::get('/sunat/consultar', [SaleController::class, 'consultarSunat']);
    Route::get('/restaurante/orders/{mesaId}', [SaleController::class, 'showRestauranteOrders'])->name('restaurante.orders');
    Route::post('/update-cantidad-personas', [SaleController::class, 'updateCantidadPersonas'])->name('sales.updateCantidadPersonas');
    Route::get('/restaurante/pago/{mesaId}', [SaleController::class, 'restaurantePago'])->name('sales.restaurantePago');
    Route::post('/restaurante/completar-venta', [SaleController::class, 'completarVentaRestaurante'])->name('restaurante.completar-venta');
    Route::post('/user/set-turno', [UserController::class, 'setTurno'])->name('user.setTurno');

    Route::view('/prueba', 'prueba')->name('prueba');
    Route::get('/sales/pdf', [SaleController::class, 'pdf'])->name('sales.pdf');
    Route::get('/sales/excel', [SaleController::class, 'excel'])->name('sales.excel');
    Route::post('/sales/addOrders/{mesaId}', [SaleController::class, 'addOrders'])->name('sales.addOrders');
    Route::get('/sales/getOrdersByTable/{mesaId}', [SaleController::class, 'getOrdersByTable'])->name('sales.getOrdersByTable');

    //Productos
    Route::get('/api/search-products-r/', [ProductController::class, 'searchrs'])->name('products.searchrs');
    Route::get('/api/search-products-v/', [ProductController::class, 'searchpv'])->name('products.searchpv');

    Route::resource('products', ProductController::class);


    Route::resource('sizes', SizeController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('areas', AreaController::class);
    Route::get('tables/getByArea', [TableController::class, 'getTables'])->name('tables.get');
    Route::resource('tables', TableController::class);
    Route::resource('payment_methods', PaymentMethodController::class);

    Route::get('/api/search-client/', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class);


    Route::post('/employees/validar-pin', [EmployeeController::class, 'validarPin'])->name('employees.validarPin');
    Route::post('/employees/validar-pin-motorizado', [EmployeeController::class, 'validarPinMotorizado'])->name('employees.validarPinMotorizado');
    Route::resource('employees', EmployeeController::class);
    Route::resource('roles', RolController::class);
    Route::resource('users', UserController::class);
    Route::resource('storages', StorageController::class);

    Route::resource('expenses', ExpenseController::class);
    Route::resource('cash_boxes', CashBoxController::class);


    Route::post('/api/save-supplier/', [SupplierController::class, 'save_ajax'])->name('suppliers.saveSupplier');
    Route::get('/api/search-supplier/', [SupplierController::class, 'search'])->name('suppliers.search');


    Route::get('/purchases/pdf/product', [PurchaseController::class, 'generatePDFProduct'])->name('purchases.pdfProduct');
    Route::get('/purchases/pdf/allproducts', [PurchaseController::class, 'generatePDFAllProducts'])->name('purchases.pdfAllProducts');
    Route::get('/purchases/pdf_report', [PurchaseController::class, 'pdf'])->name('purchases.pdf');
    Route::get('/purchases/pdf_report_general', [PurchaseController::class, 'pdf_general'])->name('purchases.pdfGeneral');
    Route::get('purchases/excel', [PurchaseController::class, 'excel'])->name('purchases.excel');
    Route::resource('purchases', PurchaseController::class);
    Route::get('/buscar-supplier', [PurchaseController::class, 'buscarSuppliers'])->name('buscar.suppliers');
    Route::get('/buscar-product', [PurchaseController::class, 'buscarProducts'])->name('buscar.products');
    
    Route::get('/mesas/preaccount', [SaleController::class, 'precuenta'])->name('mesas.precuenta');
    Route::post('/mesas/abrir/{id}', [SaleController::class, 'abrirMesa'])->name('mesas.abrir');
    Route::get('/mesas/pedido/{id}', [SaleController::class, 'verPedido'])->name('mesas.pedido');
    Route::post('/mesas/{id}/cerrar', [SaleController::class, 'cerrarMesa'])->name('mesas.cerrar');
    Route::post('/order-details/update-quantity', [SaleController::class, 'updateQuantityAccount'])->name('order.updateQuantity');


    Route::post('/sales/orders/{orderId}/products', [SaleController::class, 'addProductToOrder'])->name('orders.addProduct');
    Route::post('/sales/orders/{orderId}/products/remove', [SaleController::class, 'removeProduct'])->name('orders.removeProduct');
    // Mover líneas seleccionadas a una nueva cuenta (separar cuentas)
    Route::post('/orders/{orderId}/split', [SaleController::class, 'splitOrder'])->name('orders.split');
    Route::post('/orders/confirm', [SaleController::class, 'confirmarPedido'])->name('orders.confirm');
    Route::get('/orders/preaccount', [SaleController::class, 'precuenta'])->name('orders.preaccount');
    Route::get('/orders/separate', [SaleController::class, 'separar'])->name('orders.separate');


    Route::get('/payments/listar', [PaymentController::class, 'listar'])->name('payment.listar');
    Route::get('anticipated/excel', [SaleController::class, 'anticipatedExcel'])->name('anticipated.excel');
    Route::get('anticipated/pdf', [SaleController::class, 'anticipatedPdf'])->name('anticipated.pdf');
    Route::post('anticipated_print', [SaleController::class, 'anticipated_print'])->name('anticipated_print');

    //Ventas
    Route::get('/sunat/consultar', [SaleController::class, 'consultarSunat']);
    Route::get('delivery/excel', [SaleController::class, 'deliveryExcel'])->name('delivery.excel');
    Route::get('delivery/pdf', [SaleController::class, 'deliveryPdf'])->name('delivery.pdf');
    Route::post('/sales/confirmar-entrega', [SaleController::class, 'confirmarEntrega'])->name('sales.entregar');
    Route::post('sales/subirFoto', [SaleController::class, 'subirFoto'])->name('sales.subirFoto');
    Route::post('sales/updateDetails', [SaleController::class, 'updateDetails'])->name('sales.updateDetails');
    Route::post('sales/registrar-pago', [SaleController::class, 'registrarPago'])->name('sales.registrar_pago');
    Route::post('/sales/generar-comprobante', [SaleController::class, 'generarComprobanteAnticipado'])->name('sales.generar_comprobante');
    Route::post('sales/delivery', [SaleController::class, 'delivery'])->name('sales.delivery');
    Route::get('sales/anticipated', [SaleController::class, 'anticipated'])->name('sales.anticipated');
    Route::get('/restaurante/mozo', [SaleController::class, 'restauranteAnt'])->name('sales.mozo');
    Route::get('restaurante', [SaleController::class, 'nuevo_pdv'])->name('sales.restaurante');
    Route::get('/api/products-by-category/{categoryId}', [SaleController::class, 'getProductsByCategory'])->name('sales.getProductsByCategory');
    Route::get('/api/all-products', [SaleController::class, 'getAllProducts'])->name('sales.getAllProducts');
    Route::get('sales/details', [SaleController::class, 'details'])->name('sales.details');
    Route::get('/ventas/anular', [SaleController::class, 'anular'])->name('sales.anular');
    Route::get('sales/getVoucherData', [SaleController::class, 'getVoucherData'])->name('sales.getVoucherData');
    Route::get('/sales/historic', [SaleController::class, 'historic'])->name('sales.historic');
    Route::resource('sales', SaleController::class);

    Route::get('attendance/check', [AttendanceController::class, 'check'])->name('attendance.check');
    Route::resource('attendance', AttendanceController::class);

    Route::resource('cash_close', CashCloseController::class);
});
