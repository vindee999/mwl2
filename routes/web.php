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
Route::get('/search', 'StffController@search');
Route::get('/searchyear', 'StffController@searchyear'); 
Route::get('/searchargs', 'StffController@searchYearArgs');

Route::get('staff', 'StaffController@staff');

Route::get('month', 'MonthController@index');
Route::get('month_graph', 'MonthController@graph');

Route::get('ratio', 'RatioController@index');