<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use Illuminate\Support\Facades\Route;

Route::get('/books', [BookController::class, 'index']);
Route::post('/books', [BookController::class, 'store']);
Route::get('/books/{book}', [BookController::class, 'show']);

Route::post('/members', [MemberController::class, 'store']);
Route::get('/members/{member}', [MemberController::class, 'show']);
Route::get('/members/{member}/loans', [MemberController::class, 'loans']);

Route::post('/loans', [LoanController::class, 'store']);
Route::post('/loans/{loan}/return', [LoanController::class, 'returnBook']);
