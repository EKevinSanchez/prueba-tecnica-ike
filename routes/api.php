<?php

use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/register-user', [AuthUserController::class, 'registerUser'])->name('register-user');
Route::post('/login', [AuthUserController::class, 'generateAuthToken'])->name('generate-token');


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/update-user', [UserController::class, 'updateUser'])->name('update-user');
});
