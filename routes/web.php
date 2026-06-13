<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExamController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/admin', 'app');
Route::view('/admin/{section}', 'app')->whereIn('section', ['students', 'teachers', 'questions', 'result', 'notice']);
Route::view('/login', 'app');
Route::view('/register', 'app');
Route::view('/notice', 'app');
Route::view('/result', 'app');
Route::view('/exam', 'app');
Route::view('/exam-completed', 'app');

Route::prefix('api')->group(function () {
    Route::get('/session', [AuthController::class, 'session']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/students', [AuthController::class, 'registerStudent']);

    Route::get('/public-stats', [ExamController::class, 'publicStats']);
    Route::get('/dashboard', [ExamController::class, 'dashboard']);
    Route::get('/notices', [ExamController::class, 'notices']);
    Route::get('/exam', [ExamController::class, 'exam']);
    Route::post('/exam/submit', [ExamController::class, 'submit']);

    Route::get('/admin/overview', [AdminController::class, 'overview']);
    Route::post('/admin/students', [AdminController::class, 'storeStudent']);
    Route::put('/admin/students/{student}', [AdminController::class, 'updateStudent']);
    Route::get('/admin/questions', [AdminController::class, 'questions']);
    Route::post('/admin/questions', [AdminController::class, 'storeQuestion']);
    Route::put('/admin/questions/{question}', [AdminController::class, 'updateQuestion']);
    Route::delete('/admin/questions/{question}', [AdminController::class, 'deleteQuestion']);
    Route::post('/admin/notices', [AdminController::class, 'storeNotice']);
    Route::put('/admin/notices/{notice}', [AdminController::class, 'updateNotice']);
    Route::post('/admin/results', [AdminController::class, 'storeResult']);
    Route::put('/admin/results/{result}', [AdminController::class, 'updateResult']);
    Route::post('/admin/exam-date', [AdminController::class, 'setExamDate']);
    Route::post('/admin/result-date', [AdminController::class, 'setResultDate']);
    Route::post('/admin/teachers', [AdminController::class, 'storeTeacher']);
    Route::put('/admin/teachers/{teacher}', [AdminController::class, 'updateTeacher']);
});
