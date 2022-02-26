<?php

use Illuminate\Support\Facades\Route;

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
//    $output = Artisan::call('migrate:fresh --seed');
    $output = Artisan::call('db:seed --class=JsonSeeder');
    echo $output;
//    return view('welcome');
});

Route::get('/convert', [\App\Http\Controllers\LocalizationsController::class, 'convert']);
