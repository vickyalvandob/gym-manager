<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPhotoController;
use App\Http\Controllers\UpdateMemberStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified', 'gym'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('members/{member}/photo', MemberPhotoController::class)
        ->whereNumber('member')
        ->name('members.photo');
    Route::patch('members/{member}/status', UpdateMemberStatusController::class)
        ->whereNumber('member')
        ->name('members.status.update');
    Route::resource('members', MemberController::class)
        ->except('destroy')
        ->whereNumber('member');
});

require __DIR__.'/settings.php';
