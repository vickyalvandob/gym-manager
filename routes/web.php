<?php

use App\Http\Controllers\AssignMemberMembershipController;
use App\Http\Controllers\CreateMembershipPaymentController;
use App\Http\Controllers\MarkPaymentPaidController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPhotoController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RenewMemberMembershipController;
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
    Route::post('members/{member}/memberships', AssignMemberMembershipController::class)
        ->whereNumber('member')
        ->name('members.memberships.store');
    Route::post(
        'members/{member}/memberships/{member_membership}/renew',
        RenewMemberMembershipController::class,
    )
        ->whereNumber(['member', 'member_membership'])
        ->name('members.memberships.renew');
    Route::resource('members', MemberController::class)
        ->except('destroy')
        ->whereNumber('member');

    Route::post(
        'member-memberships/{member_membership}/payment',
        CreateMembershipPaymentController::class,
    )
        ->whereNumber('member_membership')
        ->name('member-memberships.payment.store');
    Route::patch('payments/{payment}/paid', MarkPaymentPaidController::class)
        ->whereNumber('payment')
        ->name('payments.paid');
    Route::get('payments', [PaymentController::class, 'index'])
        ->name('payments.index');

    Route::patch('membership-plans/{membership_plan}/status', UpdateMembershipPlanStatusController::class)
        ->whereNumber('membership_plan')
        ->name('membership-plans.status.update');
    Route::resource('membership-plans', MembershipPlanController::class)
        ->whereNumber('membership_plan');
});

require __DIR__.'/settings.php';
