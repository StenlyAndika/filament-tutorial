<?php

use App\Http\Controllers\FrontController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontController::class, 'index'])->name('front.index');
Route::get('/browse/{category:slug}', [FrontController::class, 'category'])->name('front.category');
Route::get('/details/{shoe:slug}', [FrontController::class, 'details'])->name('front.details');
Route::get('/order/begin/{shoe:slug}', [OrderController::class, 'saveOrder'])->name('front.save_order');
Route::get('/order/booking', [OrderController::class, 'booking'])->name('front.booking');
Route::get('/order/booking/customer-data', [OrderController::class, 'customerData'])->name('front.customer_data');
Route::get('/order/booking/customer-data/save', [OrderController::class, 'saveCustomerData'])->name('front.save_customer_data');
Route::get('/order/payment', [OrderController::class, 'payment'])->name('front.payment');
Route::get('/order/payment/confirm', [OrderController::class, 'paymentConfirm'])->name('front.payment_confirm');
Route::get('/order/finisher/{productTransaction:id}', [OrderController::class, 'orderFinished'])->name('front.order_finished');
