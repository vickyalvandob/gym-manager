<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPhotoController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\UpdateMembershipPlanStatusController;
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

    Route::patch('membership-plans/{membership_plan}/status', UpdateMembershipPlanStatusController::class)
        ->whereNumber('membership_plan')
        ->name('membership-plans.status.update');
    Route::resource('membership-plans', MembershipPlanController::class)
        ->whereNumber('membership_plan');
});

require __DIR__.'/settings.php';
