<?php

use App\Http\Controllers\ActivateMemberPortalController;
use App\Http\Controllers\AssignMemberMembershipController;
use App\Http\Controllers\AssignTrainerMemberController;
use App\Http\Controllers\CancelPtSessionController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CompletePtSessionController;
use App\Http\Controllers\CreateMembershipPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GymLogoController;
use App\Http\Controllers\GymSettingsController;
use App\Http\Controllers\MarkPaymentPaidController;
use App\Http\Controllers\MarkPtSessionNoShowController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberPhotoController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\MemberPortalPhotoController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlatformBillingController;
use App\Http\Controllers\PlatformDashboardController;
use App\Http\Controllers\PlatformGymController;
use App\Http\Controllers\PlatformGymStatusController;
use App\Http\Controllers\PlatformSubscriptionController;
use App\Http\Controllers\PlatformSubscriptionPaymentController;
use App\Http\Controllers\PlatformUserController;
use App\Http\Controllers\PlatformUserStatusController;
use App\Http\Controllers\PlatformUserSubscriptionController;
use App\Http\Controllers\PtPackageController;
use App\Http\Controllers\PtSessionController;
use App\Http\Controllers\PurchaseMemberPtPackageController;
use App\Http\Controllers\RemoveTrainerMemberController;
use App\Http\Controllers\RenewMemberMembershipController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReschedulePtSessionController;
use App\Http\Controllers\SaasPlanController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubscribedGymController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SubscriptionPaymentProofController;
use App\Http\Controllers\SwitchCurrentGymController;
use App\Http\Controllers\TrainerController;
use App\Http\Controllers\TrainerMemberController;
use App\Http\Controllers\UpdateMembershipPlanStatusController;
use App\Http\Controllers\UpdateMemberStatusController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

Route::middleware(['auth', 'verified', 'platform_admin'])
    ->prefix('platform')
    ->name('platform.')
    ->group(function () {
        Route::get('/', PlatformDashboardController::class)->name('dashboard');
        Route::get('billing', [PlatformBillingController::class, 'index'])->name('billing.index');
        Route::patch('billing', [PlatformBillingController::class, 'update'])->name('billing.update');
        Route::get('gyms', [PlatformGymController::class, 'index'])->name('gyms.index');
        Route::get('gyms/{gym}', [PlatformGymController::class, 'show'])
            ->whereNumber('gym')
            ->name('gyms.show');
        Route::patch('gyms/{gym}/status', PlatformGymStatusController::class)
            ->whereNumber('gym')
            ->name('gyms.status.update');
        Route::put('gyms/{gym}/subscription', [PlatformSubscriptionController::class, 'update'])
            ->whereNumber('gym')
            ->name('gyms.subscription.update');
        Route::get('users', [PlatformUserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [PlatformUserController::class, 'show'])
            ->whereNumber('user')
            ->name('users.show');
        Route::patch('users/{user}/status', PlatformUserStatusController::class)
            ->whereNumber('user')
            ->name('users.status.update');
        Route::put('users/{user}/subscription', [PlatformUserSubscriptionController::class, 'update'])
            ->whereNumber('user')
            ->name('users.subscription.update');
        Route::patch('subscription-payments/{subscription_payment}', PlatformSubscriptionPaymentController::class)
            ->whereNumber('subscription_payment')
            ->name('subscription-payments.update');
        Route::patch('saas-plans/{saas_plan}/status', [SaasPlanController::class, 'updateStatus'])
            ->whereNumber('saas_plan')
            ->name('saas-plans.status.update');
        Route::resource('saas-plans', SaasPlanController::class)
            ->except(['show', 'destroy'])
            ->whereNumber('saas_plan');
    });

Route::middleware(['auth', 'verified', 'gym', 'workspace:management'])->prefix('app')->group(function () {
    Route::get('onboarding', [OnboardingController::class, 'edit'])->name('onboarding.edit');
    Route::put('onboarding', [OnboardingController::class, 'update'])->name('onboarding.update');
});

Route::middleware(['auth', 'verified', 'gym:billing', 'workspace:management'])->prefix('app')->group(function () {
    Route::get('subscription', SubscriptionController::class)->name('subscription.show');
    Route::post('subscription/payments', [SubscriptionPaymentController::class, 'store'])
        ->name('subscription.payments.store');
    Route::redirect('gyms', '/app/outlets');
    Route::get('outlets', [SubscribedGymController::class, 'index'])->name('gyms.index');
    Route::post('outlets', [SubscribedGymController::class, 'store'])->name('gyms.store');
});

Route::middleware(['auth', 'verified'])
    ->get('subscription-payments/{subscription_payment}/proof', SubscriptionPaymentProofController::class)
    ->whereNumber('subscription_payment')
    ->name('subscription-payments.proof');

Route::middleware(['auth', 'verified'])
    ->put('gyms/{gym}/switch', SwitchCurrentGymController::class)
    ->whereNumber('gym')
    ->name('gyms.switch');

Route::middleware(['auth', 'verified'])
    ->prefix('portal-member')
    ->name('member-portal.')
    ->group(function () {
        Route::get('/', [MemberPortalController::class, 'index'])->name('index');
        Route::get('{member}', [MemberPortalController::class, 'show'])
            ->whereNumber('member')
            ->name('show');
        Route::get('{member}/check-ins', [MemberPortalController::class, 'checkIns'])
            ->whereNumber('member')
            ->name('check-ins');
        Route::get('{member}/payments', [MemberPortalController::class, 'payments'])
            ->whereNumber('member')
            ->name('payments');
        Route::get('{member}/personal-training', [MemberPortalController::class, 'personalTraining'])
            ->whereNumber('member')
            ->name('personal-training');
        Route::get('{member}/settings', [MemberPortalController::class, 'settings'])
            ->whereNumber('member')
            ->name('settings');
        Route::patch('{member}/contact', [MemberPortalController::class, 'update'])
            ->whereNumber('member')
            ->name('contact.update');
        Route::get('{member}/photo', MemberPortalPhotoController::class)
            ->whereNumber('member')
            ->name('photo');
    });

Route::middleware(['auth', 'verified', 'member_workspace'])->prefix('my')->name('my.')->group(function () {
    Route::get('/', [MemberPortalController::class, 'show'])->name('show');
    Route::get('qr', [MemberPortalController::class, 'qr'])->name('qr');
    Route::get('membership', [MemberPortalController::class, 'membership'])->name('membership');
    Route::get('check-ins', [MemberPortalController::class, 'checkIns'])->name('check-ins');
    Route::get('payments', [MemberPortalController::class, 'payments'])->name('payments');
    Route::get('personal-training', [MemberPortalController::class, 'personalTraining'])->name('personal-training');
    Route::get('profile', [MemberPortalController::class, 'settings'])->name('settings');
    Route::get('security', [MemberPortalController::class, 'settings'])->name('security');
    Route::patch('profile', [MemberPortalController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'verified', 'gym', 'workspace:management', 'onboarded', 'subscription_active'])->prefix('app')->group(function () {
    Route::get('/', DashboardController::class)->name('app.home');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('reports', ReportController::class)->name('reports.index');

    Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
    Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
    Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
    Route::get('staff/{user}/edit', [StaffController::class, 'edit'])
        ->whereNumber('user')
        ->name('staff.edit');
    Route::patch('staff/{user}', [StaffController::class, 'update'])
        ->whereNumber('user')
        ->name('staff.update');

    Route::get('settings/gym', [GymSettingsController::class, 'edit'])
        ->name('gym-settings.edit');
    Route::patch('settings/gym', [GymSettingsController::class, 'update'])
        ->name('gym-settings.update');
    Route::delete('settings/gym/logo', [GymSettingsController::class, 'destroyLogo'])
        ->name('gym-settings.logo.destroy');
    Route::get('gym-logo', GymLogoController::class)->name('gym-logo.show');

    Route::get('members/{member}/photo', MemberPhotoController::class)
        ->whereNumber('member')
        ->name('members.photo');
    Route::post('members/{member}/portal-access', ActivateMemberPortalController::class)
        ->middleware('throttle:3,1')
        ->whereNumber('member')
        ->name('members.portal-access.store');
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

    Route::get('check-ins', [CheckInController::class, 'index'])
        ->name('check-ins.index');
    Route::post('check-ins/scan', [CheckInController::class, 'scan'])->name('check-ins.scan');
    Route::post('members/{member}/check-ins', [CheckInController::class, 'store'])
        ->whereNumber('member')
        ->name('members.check-ins.store');
    Route::post('members/{member}/pt-packages', PurchaseMemberPtPackageController::class)
        ->whereNumber('member')
        ->name('members.pt-packages.store');

    Route::post('trainers/{trainer}/members', AssignTrainerMemberController::class)
        ->whereNumber('trainer')
        ->name('trainers.members.store');
    Route::delete('trainers/{trainer}/members/{member}', RemoveTrainerMemberController::class)
        ->whereNumber(['trainer', 'member'])
        ->name('trainers.members.destroy');
    Route::resource('trainers', TrainerController::class)
        ->except('destroy')
        ->whereNumber('trainer');

    Route::resource('pt-packages', PtPackageController::class)
        ->except('destroy')
        ->whereNumber('pt_package');
    Route::get('pt-sessions', [PtSessionController::class, 'index'])
        ->name('pt-sessions.index');
    Route::post('pt-sessions', [PtSessionController::class, 'store'])
        ->name('pt-sessions.store');
    Route::get('pt-sessions/{pt_session}', [PtSessionController::class, 'show'])
        ->whereNumber('pt_session')
        ->name('pt-sessions.show');
    Route::patch('pt-sessions/{pt_session}/reschedule', ReschedulePtSessionController::class)
        ->whereNumber('pt_session')
        ->name('pt-sessions.reschedule');
    Route::patch('pt-sessions/{pt_session}/cancel', CancelPtSessionController::class)
        ->whereNumber('pt_session')
        ->name('pt-sessions.cancel');
    Route::patch('pt-sessions/{pt_session}/complete', CompletePtSessionController::class)
        ->whereNumber('pt_session')
        ->name('pt-sessions.complete');
    Route::patch('pt-sessions/{pt_session}/no-show', MarkPtSessionNoShowController::class)
        ->whereNumber('pt_session')
        ->name('pt-sessions.no-show');

    Route::patch('membership-plans/{membership_plan}/status', UpdateMembershipPlanStatusController::class)
        ->whereNumber('membership_plan')
        ->name('membership-plans.status.update');
    Route::resource('membership-plans', MembershipPlanController::class)
        ->whereNumber('membership_plan');
});

Route::middleware(['auth', 'verified', 'gym', 'workspace:trainer', 'onboarded', 'subscription_active'])
    ->prefix('trainer')->group(function () {
        Route::get('/', DashboardController::class)->name('trainer.dashboard');
        Route::redirect('dashboard', '/trainer');
        Route::get('clients', [TrainerMemberController::class, 'index'])->name('trainer-members.index');
        Route::get('clients/{member}', [TrainerMemberController::class, 'show'])
            ->whereNumber('member')->name('trainer-members.show');
        Route::get('schedule', [PtSessionController::class, 'index'])
            ->defaults('scope', 'upcoming')->name('trainer.schedule');
        Route::get('history', [PtSessionController::class, 'index'])
            ->defaults('scope', 'history')->name('trainer.history');
        Route::get('sessions', [PtSessionController::class, 'index'])->name('trainer.pt-sessions.index');
        Route::post('sessions', [PtSessionController::class, 'store'])->name('trainer.pt-sessions.store');
        Route::get('sessions/{pt_session}', [PtSessionController::class, 'show'])
            ->whereNumber('pt_session')->name('trainer.pt-sessions.show');
        Route::patch('sessions/{pt_session}/reschedule', ReschedulePtSessionController::class)
            ->whereNumber('pt_session')->name('trainer.pt-sessions.reschedule');
        Route::patch('sessions/{pt_session}/cancel', CancelPtSessionController::class)
            ->whereNumber('pt_session')->name('trainer.pt-sessions.cancel');
        Route::patch('sessions/{pt_session}/complete', CompletePtSessionController::class)
            ->whereNumber('pt_session')->name('trainer.pt-sessions.complete');
        Route::patch('sessions/{pt_session}/no-show', MarkPtSessionNoShowController::class)
            ->whereNumber('pt_session')->name('trainer.pt-sessions.no-show');
        Route::get('profile', [ProfileController::class, 'edit'])
            ->name('trainer.profile');
    });

require __DIR__.'/settings.php';
