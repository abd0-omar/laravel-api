<?php

use App\Http\Controllers\api\v1\CustomerController;
use App\Http\Controllers\api\v1\InvoiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// api/v1/customers (endpoint)

Route::prefix('v1')->namespace('App\Http\Controllers\api\v1')->group(function () {
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);

    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']);
});

// or
// Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers\api\v1'], function () {

//     Route::apiResource('customers', CustomerController::class);
//     Route::apiResource('invoices', InvoiceController::class);
// });
