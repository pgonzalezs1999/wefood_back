<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\FavouriteController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ImageController;

Route::middleware('auth:sanctum') -> get('/user', function (Request $request) {
    return $request->user();
});

Route::group(['middleware' => 'api', 'prefix' => 'auth'], function($router) {
    Route::post('/signIn', [UserController::class, 'signin']); // only for customers
    Route::post('/signout', [UserController::class, 'signout']);
    Route::get('/getProfile', [UserController::class, 'getProfile']);
    Route::post('/updateRealName', [UserController::class, 'updateRealName']);
    Route::post('/updateUsername', [UserController::class, 'updateUsername']);
    Route::post('/updatePassword', [UserController::class, 'updatePassword']);
    Route::post('/updateEmail', [UserController::class, 'updateEmail']);
    Route::post('/updatePhone', [UserController::class, 'updatePhone']);
    Route::post('/updateSex', [UserController::class, 'updateSex']);
    Route::post('/verifyEmail', [UserController::class, 'verifyEmail']);
    Route::post('/checkUsernameAvailability', [UserController::class, 'checkUsernameAvailability']);
    Route::post('/checkEmailAvailability', [UserController::class, 'checkEmailAvailability']);
    Route::post('/checkPhoneAvailability', [UserController::class, 'checkPhoneAvailability']);
    
    Route::post('/login', [AuthController::class, 'login']) -> name('login');
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/addAdmin', [AuthController::class, 'addAdmin']) -> middleware('admin');
    Route::post('/removeAdmin', [AuthController::class, 'removeAdmin']) -> middleware('admin');
    
    Route::post('/createBusiness', [BusinessController::class, 'createBusiness']); // business signin
    Route::get('/getSessionBusiness', [BusinessController::class, 'getSessionBusiness']) -> middleware('business');
    Route::post('/getBusiness', [BusinessController::class, 'getBusiness']);
    Route::get('/getAllBusinesses', [BusinessController::class, 'getAllBusinesses']);
    Route::post('/deleteBusiness', [BusinessController::class, 'deleteBusiness']) -> middleware('business');
    Route::post('/validateBusiness', [BusinessController::class, 'validateBusiness']) -> middleware('admin');
    Route::post('/refuseBusiness', [BusinessController::class, 'refuseBusiness']) -> middleware('admin');
    Route::post('/updateBusinessName', [BusinessController::class, 'updateBusinessName']) -> middleware('business');
    Route::post('/updateBusinessDescription', [BusinessController::class, 'updateBusinessDescription']) -> middleware('business');
    Route::post('/updateBusinessDirections', [BusinessController::class, 'updateBusinessDirections']) -> middleware('business');
    Route::post('/addBusinessCurrency', [BusinessController::class, 'addBusinessCurrency']) -> middleware('business');
    Route::post('/removeBusinessCurrency', [BusinessController::class, 'removeBusinessCurrency']) -> middleware('business');
    Route::post('/checkTaxIdAvailability', [BusinessController::class, 'checkTaxIdAvailability']);
    Route::post('/checkValidity', [BusinessController::class, 'checkValidity']);
    Route::post('/cancelValidation', [BusinessController::class, 'cancelValidation']);
    Route::get('/businessProductsResume', [BusinessController::class, 'businessProductsResume']) -> middleware('business');
    Route::get('/getValidatableBusinesses', [BusinessController::class, 'getValidatableBusinesses']) -> middleware('admin');
    Route::post('/getNearbyBusinesses', [BusinessController::class, 'getNearbyBusinesses']); // For the map
    
    Route::get('/getProduct/{id}', [ProductController::class, 'getProduct']);
    Route::post('/createProduct', [ProductController::class, 'createProduct']) -> middleware('business');
    Route::post('/deleteProduct', [ProductController::class, 'deleteProduct']) -> middleware('business');
    Route::post('/updateProduct', [ProductController::class, 'updateProduct']) -> middleware('business');
    Route::post('/addProductImage', [ProductController::class, 'addProductImage']) -> middleware('business');
    Route::post('/deleteProductImage', [ProductController::class, 'deleteProductImage']) -> middleware('business');
    Route::post('/searchItemsByFilters', [ProductController::class, 'searchItemsByFilters']);
    Route::post('/searchItemsByText', [ProductController::class, 'searchItemsByText']);
    
    Route::post('/getRecommendedItems', [ItemController::class, 'getRecommendedItems']);
    Route::post('/getNearbyItems', [ItemController::class, 'getNearbyItems']); // For the explore page
    Route::get('/getItem/{id}', [ItemController::class, 'getItem']);
    
    Route::post('/orderItem', [OrderController::class, 'orderItem']);
    Route::get('/getPendingOrdersCustomer', [OrderController::class, 'getPendingOrdersCustomer']);
    Route::get('/getPendingOrdersBusiness', [OrderController::class, 'getPendingOrdersBusiness']) -> middleware('business');
    Route::get('/getOrderHistoryCustomer', [OrderController::class, 'getOrderHistoryCustomer']);
    Route::get('/getOrderHistoryBusiness', [OrderController::class, 'getOrderHistoryBusiness']) -> middleware('business');
    Route::post('/completeOrderCustomer', [OrderController::class, 'completeOrderCustomer']);
    Route::post('/completeOrderBusiness', [OrderController::class, 'completeOrderBusiness']) -> middleware('business');
    
    Route::post('/addFavourite', [FavouriteController::class, 'addFavourite']);
    Route::post('/removeFavourite', [FavouriteController::class, 'removeFavourite']);
    Route::get('/getFavouriteItems', [FavouriteController::class, 'getFavouriteItems']);
    
    Route::post('/addComment', [CommentController::class, 'addComment']);
    Route::post('/deleteComment', [CommentController::class, 'deleteComment']);
    Route::post('/getCommentsFromBusiness', [CommentController::class, 'getCommentsFromBusiness']);
    
    Route::get('/getAllCountries', [CountryController::class, 'getAllCountries']);

    Route::post('/uploadImage', [ImageController::class, 'uploadImage']);
    Route::post('/getImage', [ImageController::class, 'getImage']);
    Route::post('/removeImage', [ImageController::class, 'removeImage']);
});