<?php
use App\Http\Controllers\PostController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LoginApprovalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

 Route::get('/user', function (Request $request) {
     return $request->user();
 })->middleware('auth:sanctum');

Route::apiResource('posts',PostController::class);


Route::post('/register',[AuthController::class,'register']);
Route::post('/login',[AuthController::class,'login']);
Route::post('/logout',[AuthController::class,'logout'])->middleware('auth:sanctum');



Route::get('/login-attempt', [LoginApprovalController::class, 'handle']);
Route::post('verify-otp', [LoginApprovalController::class, 'verifyOtp']);
Route::get('/login-status', [LoginApprovalController::class, 'checkStatus']);
