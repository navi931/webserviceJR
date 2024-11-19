<?php

use Illuminate\Http\Request;
use \Illuminate\Support\Facades\URL;
use \App\Conversiones;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::get('/getPage',['uses'=>'IndexController@getPage','as'=>'Index.getPage']);
Route::get('/prueba',['uses'=>'IndexController@prueba','as'=>'Index.prueba']);
Route::get('/getPlaza',['uses'=>'IndexController@getPlaza','as'=>'Index.getPlaza']);
Route::get('/getHorario',['uses'=>'IndexController@getHorario','as'=>'Index.getHorario']);
Route::get('/getGrupoCarros',['uses'=>'IndexController@getGrupoCarros','as'=>'Index.getGrupoCarros']);
Route::get('/getEdades',['uses'=>'IndexController@getEdades','as'=>'Index.getEdades']);
Route::get('/validarUsuario',['uses'=>'IndexController@validarUsuario','as'=>'IndexController.validarUsuario']);
Route::get('/buscarCodigoDescuento',['uses'=>'IndexController@buscarCodigoDescuento','as'=>'IndexController.buscarCodigoDescuento']);
Route::get('/getSubtarifas',['uses'=>'IndexController@getSubtarifas','as'=>'IndexController.getSubtarifas']);

Route::get('/getPrecios',['uses'=>'Page2Controller@getPrecios','as'=>'Page2Controller.getPrecios']);

Route::get('/getSoloSeguros',['uses'=>'Page3Controller@getSoloSeguros','as'=>'Page3Controller.getSoloSeguros']);
Route::get('/guardarCotizacion',['uses'=>'Page3Controller@guardarCotizacion','as'=>'Page3Controller.guardarCotizacion']);
Route::get('/modificarCotizacion',['uses'=>'Page3Controller@modificarCotizacion','as'=>'Page3Controller.modificarCotizacion']);

Route::get('/getPrecioReal',['uses'=>'Page4Controller@getPrecioReal','as'=>'Page4Controller.getPrecioReal']);
Route::get('/insertarReserva',['uses'=>'Page4Controller@insertarReserva','as'=>'Page4Controller.insertarReserva']);
Route::get('/cancelarReserva',['uses'=>'Page4Controller@cancelarReserva','as'=>'Page4Controller.cancelarReserva']);
Route::get('/getDomicilioOficina',['uses'=>'Page4Controller@getDomicilioOficina','as'=>'Page4Controller.getDomicilioOficina']);
Route::get('/getReserva',['uses'=>'Page4Controller@getReserva','as'=>'Page4Controller.getReserva']);
Route::get('/activarPrepago',['uses'=>'Page4Controller@activarPrepago','as'=>'Page4Controller.activarPrepago']);
Route::get('/activarPrepagoStripe',['uses'=>'Page4Controller@activarPrepagoStripe','as'=>'Page4Controller.activarPrepagoStripe']);
Route::get('/moverCotizacion',['uses'=>'Page4Controller@moverCotizacion','as'=>'Page4Controller.moverCotizacion']);

Route::get('/getState',['uses'=>'Control@getState','as'=>'Control.getState']);
Route::get('/setState',['uses'=>'Control@setState','as'=>'Control.setState']);
Route::get('/getPersonas',['uses'=>'Control@getPersonas','as'=>'Control.getPersonas']);
Route::get('/getToken',['uses'=>'Control@getToken','as'=>'Control.getToken']);

Route::post('/recibirReserva',['uses'=>'Control@recibirReserva','as'=>'Control.recibirReserva']);


Route::post('/pagoStripe',['uses'=>'StripeController@pagoStripe','as'=>'Control.pagoStripe']);

//Eliminar despues
Route::get('/enviarCorreo',['uses'=>'Control@enviarCorreo','as'=>'Control.enviarCorreo']);


// Route::get('/prueba2', function (Request $request) {
//    return Response::json('validate',201);
// });
