<?php

use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::post('/categories', [CategoryController::class, 'store']);
