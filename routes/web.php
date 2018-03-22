<?php

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

Route::post('/yingyan/entity/add', "YingYanController@entityAdd");
Route::post('/yingyan/distance/byorderid', "YingYanController@getDistanceByOrderId");
Route::post('/yingyan/add/point', "YingYanController@addPoint");
Route::post('/yingyan/getdistance', "YingYanController@getDistance");
