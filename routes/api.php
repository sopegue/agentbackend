<?php

use App\Http\Controllers\Agence\AgenceController;
use App\Http\Controllers\Property\PropertyController;
use App\Http\Controllers\Property\SaveController;
use App\Http\Controllers\User\AdminController;
use App\Http\Controllers\User\AgentController;
use App\Http\Controllers\User\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
Route::apiResources([
    'client' => ClientController::class,
    'admin' => AdminController::class,
    'agent' => AgentController::class,
    'agence' => AgenceController::class,
    'property' => PropertyController::class,
    'save' => SaveController::class
]);

Route::post('client/existence', [ClientController::class, 'isEmailFreeApi']);
Route::post('client/role', [ClientController::class, 'checkClientRole']);
Route::post('client/infos/update', [ClientController::class, 'updateInfosApi']);
Route::post('client/pwd/update', [ClientController::class, 'updatePwdApi']);
Route::post('client/pwd/existence', [ClientController::class, 'pwdExistence']);

Route::post('agent/existence', [AgentController::class, 'isEmailFreeApi']);

Route::post('agence/existence', [AgenceController::class, 'isEmailFreeApi']);

Route::post('properties/search', [PropertyController::class, 'searchApi']);
Route::get('properties/bytype/{type}', [PropertyController::class, 'showByType']);
Route::get('properties/bytype/skip/{type}', [PropertyController::class, 'showByTypeSkip']);
Route::get('property/agent/{show}', [PropertyController::class, 'showOwn']);
Route::get('property/{mark}/{as}/{id}', [PropertyController::class, 'soldOrRent']);
Route::post('property/update', [PropertyController::class, 'updateApi']);
Route::get('property/visit/{id}', [PropertyController::class, 'visitApi']);

Route::get('saved/{prop}/{what}/{id}/{email}', [SaveController::class, 'savedManage']);
