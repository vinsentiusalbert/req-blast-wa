<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WhatsApp\CampaignController;
use App\Http\Controllers\Admin\WhatsApp\CampaignScheduleController;
use App\Http\Controllers\Admin\WhatsApp\SenderController;
use App\Http\Controllers\Admin\WhatsApp\TemplateReviewController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\User\WhatsApp\BroadcastController;
use App\Http\Controllers\User\WhatsApp\TemplateController;
use App\Http\Controllers\WhatsApp\DeliveryReportController;
use App\Http\Controllers\WhatsApp\RecipientExportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');
        Route::get('/whatsapp/campaigns', [CampaignController::class, 'index'])->name('whatsapp.campaigns.index');
        Route::get('/whatsapp/senders', [SenderController::class, 'index'])->name('whatsapp.senders.index');
        Route::post('/whatsapp/senders', [SenderController::class, 'store'])->name('whatsapp.senders.store');
        Route::patch('/whatsapp/senders/{sender}', [SenderController::class, 'update'])->name('whatsapp.senders.update');
        Route::put('/whatsapp/campaigns/{broadcast}/schedules', [CampaignScheduleController::class, 'update'])->name('whatsapp.campaigns.schedules.update');
        Route::get('/whatsapp/campaigns/{broadcast}', [CampaignController::class, 'show'])->name('whatsapp.campaigns.show');
        Route::get('/whatsapp/campaigns/{broadcast}/dlr/export', [DeliveryReportController::class, 'export'])->name('whatsapp.campaigns.dlr.export');
        Route::get('/whatsapp/campaigns/{broadcast}/recipients/export', RecipientExportController::class)->name('whatsapp.campaigns.recipients.export');
        Route::patch('/whatsapp/campaigns/{broadcast}', [CampaignController::class, 'update'])->name('whatsapp.campaigns.update');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::prefix('whatsapp/templates')->name('whatsapp.templates.')->group(function () {
            Route::get('/', [TemplateReviewController::class, 'index'])->name('index');
            Route::get('/{template}', [TemplateReviewController::class, 'show'])->name('show');
            Route::patch('/{template}/review', [TemplateReviewController::class, 'update'])->name('review');
            Route::get('/{template}/asset', [TemplateController::class, 'asset'])->name('asset');
        });
    });

    Route::prefix('user')->name('user.')->middleware('role:user')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'user'])->name('dashboard');

        Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
            Route::get('/templates/{template}/asset', [TemplateController::class, 'asset'])->name('templates.asset');
            Route::resource('templates', TemplateController::class)->except('destroy');
            Route::resource('broadcasts', BroadcastController::class)->except('destroy');
            Route::get('/broadcasts/{broadcast}/dlr/export', [DeliveryReportController::class, 'export'])->name('broadcasts.dlr.export');
            Route::get('/broadcasts/{broadcast}/recipients/export', RecipientExportController::class)->name('broadcasts.recipients.export');
        });
    });
});
