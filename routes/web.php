<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EntityController;
use App\Http\Controllers\RecordController;
use App\Http\Controllers\RecordTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'loginScreen'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('operator')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/records', [RecordController::class, 'index'])->name('records.index');
    Route::get('/records/new', [RecordController::class, 'create'])->name('records.create');
    Route::post('/records', [RecordController::class, 'store'])->name('records.store');
    Route::get('/records/{record}', [RecordController::class, 'show'])->name('records.show');
    Route::get('/records/{record}/edit', [RecordController::class, 'edit'])->name('records.edit');
    Route::put('/records/{record}', [RecordController::class, 'update'])->name('records.update');
    Route::delete('/records/{record}', [RecordController::class, 'destroy'])->name('records.destroy');
    Route::get('/records/{record}/renew', [RecordController::class, 'renew'])->name('records.renew');

    Route::post('/records/{record}/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/types', [RecordTypeController::class, 'index'])->name('types.index');
    Route::post('/types', [RecordTypeController::class, 'store'])->name('types.store');
    Route::put('/types/{type}', [RecordTypeController::class, 'update'])->name('types.update');

    Route::get('/entities/{entity}', [EntityController::class, 'show'])->name('entities.show');
});
