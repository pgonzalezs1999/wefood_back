<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BusinessController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function($router) {
    Route::post('/signin', [UserController::class, 'signin']); // only for customers
    Route::post('/signout', [UserController::class, 'signout']);
    
    Route::post('/login', [AuthController::class, 'login']) -> name('login');
    Route::get('/getProfile', [UserController::class, 'getProfile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::post('/createBusiness', [BusinessController::class, 'createBusiness']); // business' user signin
    Route::get('/getSessionBusiness', [BusinessController::class, 'getSessionBusiness']);
    Route::get('/getAllBusinesses', [BusinessController::class, 'getAllBusinesses']);
    Route::post('/deleteBusiness', [BusinessController::class, 'deleteBusiness']);
    
    Route::get('/getAllCountries', [CountryController::class, 'getAllCountries']);
});