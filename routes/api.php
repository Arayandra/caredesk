<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\MedicalRecordController;
use App\Http\Controllers\Api\PasienController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard', DashboardController::class);
    Route::apiResource('pasiens', PasienController::class);

    Route::get('/doctors', [DoctorController::class, 'index']);
    Route::apiResource('appointments', AppointmentController::class)
        ->only(['index', 'store', 'update', 'destroy']);

    Route::get('/records', [MedicalRecordController::class, 'index']);
    Route::post('/records', [MedicalRecordController::class, 'store']);

    Route::get('/reports', ReportController::class);
});
