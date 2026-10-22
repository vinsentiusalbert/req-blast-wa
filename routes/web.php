<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsApp\BroadcastReviewController;
use App\Http\Controllers\Admin\WhatsApp\TemplateReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\WhatsApp\BroadcastController;
use App\Http\Controllers\User\WhatsApp\TemplateController;
use App\Http\Controllers\WhatsAppReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/whatsapp/reports', [WhatsAppReportController::class, 'index'])->name('whatsapp.reports.index');
        Route::get('/whatsapp/reports/{broadcast}', [WhatsAppReportController::class, 'show'])->name('whatsapp.reports.show');
        Route::get('/whatsapp/reports/{broadcast}/export', [WhatsAppReportController::class, 'export'])->name('whatsapp.reports.export');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::prefix('whatsapp/broadcasts')->name('whatsapp.broadcasts.')->group(function () {
            Route::get('/', [BroadcastReviewController::class, 'index'])->name('index');
            Route::get('/{broadcast}', [BroadcastReviewController::class, 'show'])->name('show');
            Route::patch('/{broadcast}/review', [BroadcastReviewController::class, 'update'])->name('review');
        });
        Route::prefix('whatsapp/templates')->name('whatsapp.templates.')->group(function () {
            Route::get('/', [TemplateReviewController::class, 'index'])->name('index');
            Route::get('/{template}', [TemplateReviewController::class, 'show'])->name('show');
            Route::patch('/{template}/review', [TemplateReviewController::class, 'update'])->name('review');
            Route::get('/{template}/asset', [TemplateController::class, 'asset'])->name('asset');
        });
    });

    Route::prefix('user')->name('user.')->middleware('role:user')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');
        Route::get('/whatsapp/reports', [WhatsAppReportController::class, 'index'])->name('whatsapp.reports.index');
        Route::get('/whatsapp/reports/{broadcast}', [WhatsAppReportController::class, 'show'])->name('whatsapp.reports.show');
        Route::get('/whatsapp/reports/{broadcast}/export', [WhatsAppReportController::class, 'export'])->name('whatsapp.reports.export');

        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/templates/{template}/asset', [TemplateController::class, 'asset'])->name('templates.asset');
            Route::resource('templates', TemplateController::class)->except('destroy');
            Route::post('broadcasts/preview-csv', [BroadcastController::class, 'previewCsv'])->name('broadcasts.preview-csv');
            Route::resource('broadcasts', BroadcastController::class)->except('destroy');
        });
    });
});
