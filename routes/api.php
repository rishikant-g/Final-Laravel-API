<?php

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


Route::post('login', 'UserController@login');
Route::post('refresh-token', 'UserController@refreshToken');


Route::group([
    'middleware' => 'auth:api'
], function () {
    Route::get('logout', 'UserController@logout');
    Route::get('/user',function(){
        return App\User::all();
    });
});