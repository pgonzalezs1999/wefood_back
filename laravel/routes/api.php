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
    Route::get('/getProfile', [UserController::class, 'getProfile']);
    Route::post('/updateRealName', [UserController::class, 'updateRealName']);
    Route::post('/updateUsername', [UserController::class, 'updateUsername']);
    Route::post('/updatePassword', [UserController::class, 'updatePassword']);
    Route::post('/updateEmail', [UserController::class, 'updateEmail']);
    Route::post('/updatePhone', [UserController::class, 'updatePhone']);
    Route::post('/updateSex', [UserController::class, 'updateSex']);
    
    Route::post('/login', [AuthController::class, 'login']) -> name('login');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/addAdmin', [AuthController::class, 'addAdmin']) -> middleware('admin');
    Route::post('/removeAdmin', [AuthController::class, 'removeAdmin']) -> middleware('admin');
    
    Route::post('/createBusiness', [BusinessController::class, 'createBusiness']); // business' user signin
    Route::get('/getSessionBusiness', [BusinessController::class, 'getSessionBusiness']);
    Route::get('/getAllBusinesses', [BusinessController::class, 'getAllBusinesses']);
    Route::post('/deleteBusiness', [BusinessController::class, 'deleteBusiness']);
    Route::post('/validateBusiness', [BusinessController::class, 'validateBusiness']) -> middleware('admin');
    Route::post('/updateBusinessName', [BusinessController::class, 'updateBusinessName']);
    Route::post('/updateBusinessDescription', [BusinessController::class, 'updateBusinessDescription']);
    Route::post('/updateBusinessDirections', [BusinessController::class, 'updateBusinessDirections']);
    Route::post('/addBusinessCurrency', [BusinessController::class, 'addBusinessCurrency']);
    Route::post('/removeBusinessCurrency', [BusinessController::class, 'removeBusinessCurrency']);
    
    Route::get('/getAllCountries', [CountryController::class, 'getAllCountries']);
});