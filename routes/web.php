<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Property\PropertyController;

Route::get('properties/fav/{key}/{sort}', [PropertyController::class, 'favPropApi']);
Route::get('properties/agfirst/{key}/{exclude}', [PropertyController::class, 'propAgCountApi']);
Route::get('properties/ag/{key}/{exclude}', [PropertyController::class, 'propAgApi']);
Route::get('properties/villesfirst/{key}/{id}', [PropertyController::class, 'propVilleFirstApi']);
Route::get('properties/villes/{key}/{id}', [PropertyController::class, 'propVilleApi']);
Route::get('properties/search/{key}', [PropertyController::class, 'searchKeyApi']);
Route::post('properties/search', [PropertyController::class, 'searchApi']);
Route::get('properties/bytype/{type}', [PropertyController::class, 'showByType']);
Route::get('properties/bytype/skip/{type}', [PropertyController::class, 'showByTypeSkip']);
Route::get('property/agent/{show}', [PropertyController::class, 'showOwn']);
Route::get('property/{mark}/{as}/{id}', [PropertyController::class, 'soldOrRent']);
Route::get('property/visit/{id}', [PropertyController::class, 'visitApi']);
Route::post('property/update', [PropertyController::class, 'updateApi']);
Route::post('properties/viewed', [PropertyController::class, 'viewedApi']);


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

Route::get('/', function () {
    return view('welcome');
});
