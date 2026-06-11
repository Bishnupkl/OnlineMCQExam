<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');

Route::prefix('api')->group(function () {
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/students', [AuthController::class, 'registerStudent']);

    Route::get('/dashboard', [ExamController::class, 'dashboard']);
    Route::get('/notices', [ExamController::class, 'notices']);
    Route::get('/exam', [ExamController::class, 'exam']);
    Route::post('/exam/submit', [ExamController::class, 'submit']);

    Route::get('/admin/overview', [AdminController::class, 'overview']);
    Route::get('/admin/questions', [AdminController::class, 'questions']);
    Route::post('/admin/questions', [AdminController::class, 'storeQuestion']);
    Route::delete('/admin/questions/{question}', [AdminController::class, 'deleteQuestion']);
    Route::post('/admin/notices', [AdminController::class, 'storeNotice']);
    Route::post('/admin/exam-date', [AdminController::class, 'setExamDate']);
    Route::post('/admin/teachers', [AdminController::class, 'storeTeacher']);
});
