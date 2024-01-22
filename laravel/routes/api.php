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
    Route::post('/signin', [UserController::class, 'signin']);
    Route::post('/signout', [UserController::class, 'signout']);
    
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/getProfileInfo', [AuthController::class, 'getProfileInfo']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    Route::get('/getAllCountries', [CountryController::class, 'getAllCountries']);

    Route::get('/getAllBusiness', [BusinessController::class, 'getAllBusiness']);
    Route::post('/createBusiness', [BusinessController::class, 'createBusiness']);
});