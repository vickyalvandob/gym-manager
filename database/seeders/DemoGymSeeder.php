<?php

namespace Database\Seeders;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Enums\MemberGender;
use App\Enums\MemberPtPackageStatus;
use App\Enums\MembershipDurationUnit;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PaymentType;
use App\Enums\PtSessionStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TrainerStatus;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MemberPtPackage;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\PtPackage;
use App\Models\PtSession;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\Trainer;
use App\Models\TrainerMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoGymSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(SaasPlanSeeder::class);

        $gym = Gym::query()->updateOrCreate(
            ['slug' => 'gymflow-demo'],
            [
                'name' => 'GymFlow Demo',
                'status' => GymStatus::Active,
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'onboarding_completed_at' => now(),
                'membership_expiry_warning_days' => 7,
            ],
        );

        $platformAdmin = User::query()->updateOrCreate(
            ['email' => 'platform@gym.test'],
            [
                'name' => 'Platform Admin GymFlow',
                'email_verified_at' => now(),
                'password' => 'password',
            ],
        );
        $platformAdmin->forceFill(['is_platform_admin' => true])->save();

        $accounts = [
            ['name' => 'Owner GymFlow', 'email' => 'owner@gym.test', 'role' => GymRole::Owner],
            ['name' => 'Front Desk GymFlow', 'email' => 'frontdesk@gym.test', 'role' => GymRole::Admin],
            ['name' => 'Andi Pratama', 'email' => 'andi@gym.test', 'role' => GymRole::Trainer],
            ['name' => 'Budi Santoso', 'email' => 'budi@gym.test', 'role' => GymRole::Trainer],
            ['name' => 'Rina Maharani', 'email' => 'rina@gym.test', 'role' => GymRole::Trainer],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );

            $gym->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'role' => $account['role']->value,
                    'status' => GymUserStatus::Active->value,
                ],
            ]);
        }

        $owner = User::query()->where('email', 'owner@gym.test')->firstOrFail();
        $starterPlan = SaasPlan::query()->where('slug', 'starter')->firstOrFail();
        $subscription = Subscription::query()->updateOrCreate(
            ['subscriber_id' => $owner->getKey()],
            [
                'saas_plan_id' => $starterPlan->getKey(),
                'status' => SubscriptionStatus::Active,
                'started_at' => now()->subMonth(),
                'trial_ends_at' => null,
                'current_period_starts_at' => now()->startOfMonth(),
                'current_period_ends_at' => now()->addMonth(),
                'suspended_at' => null,
                'cancelled_at' => null,
            ],
        );
        $gym->forceFill(['subscription_id' => $subscription->getKey()])->save();

        $members = [
            ['name' => 'Aditya Pratama', 'phone' => '081234560001', 'email' => 'aditya@example.test', 'gender' => MemberGender::Male, 'status' => MemberStatus::Active],
            ['name' => 'Nadia Permata', 'phone' => '081234560002', 'email' => 'nadia@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Rizky Mahendra', 'phone' => '081234560003', 'email' => null, 'gender' => MemberGender::Male, 'status' => MemberStatus::Active],
            ['name' => 'Salsabila Putri', 'phone' => '081234560004', 'email' => 'salsabila@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Fajar Nugroho', 'phone' => '081234560005', 'email' => null, 'gender' => MemberGender::Male, 'status' => MemberStatus::Inactive],
            ['name' => 'Maya Lestari', 'phone' => '081234560006', 'email' => 'maya@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Dimas Saputra', 'phone' => '081234560007', 'email' => 'dimas@example.test', 'gender' => MemberGender::Male, 'status' => MemberStatus::Inactive],
            ['name' => 'Ayu Wulandari', 'phone' => '081234560008', 'email' => null, 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
        ];

        foreach ($members as $index => $memberData) {
            Member::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_number' => sprintf('MBR-%06d', $index + 1),
                ],
                [
                    ...$memberData,
                    'birth_date' => now()->subYears(20 + $index)->subMonths($index)->toDateString(),
                    'address' => 'Jakarta',
                    'emergency_contact' => '08129999000'.($index + 1),
                    'notes' => null,
                ],
            );
        }

        $gym->forceFill([
            'next_member_sequence' => max($gym->next_member_sequence, count($members) + 1),
        ])->save();

        $trainerProfiles = [
            [
                'trainer_code' => 'TRN-000001',
                'name' => 'Andi Pratama',
                'phone' => '081288880001',
                'email' => 'andi@gym.test',
                'specialization' => 'Strength & Conditioning',
                'bio' => 'Berfokus pada kekuatan dasar, teknik angkat beban, dan progres yang terukur.',
                'status' => TrainerStatus::Active,
                'joined_at' => today()->subYears(2),
                'notes' => 'Trainer utama untuk program kekuatan dasar.',
                'user_id' => User::query()->where('email', 'andi@gym.test')->value('id'),
            ],
            [
                'trainer_code' => 'TRN-000002',
                'name' => 'Budi Santoso',
                'phone' => '081288880002',
                'email' => 'budi@gym.test',
                'specialization' => 'Functional Training',
                'bio' => 'Mendampingi latihan fungsional dan peningkatan mobilitas untuk aktivitas harian.',
                'status' => TrainerStatus::Active,
                'joined_at' => today()->subYear(),
                'notes' => null,
                'user_id' => User::query()->where('email', 'budi@gym.test')->value('id'),
            ],
            [
                'trainer_code' => 'TRN-000003',
                'name' => 'Rina Maharani',
                'phone' => '081288880003',
                'email' => 'rina@gym.test',
                'specialization' => 'Weight Loss',
                'bio' => 'Program penurunan berat badan yang memadukan latihan beban dan conditioning.',
                'status' => TrainerStatus::Active,
                'joined_at' => today()->subMonths(8),
                'notes' => null,
                'user_id' => User::query()->where('email', 'rina@gym.test')->value('id'),
            ],
        ];

        foreach ($trainerProfiles as $trainerProfile) {
            Trainer::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'email' => $trainerProfile['email'],
                ],
                $trainerProfile,
            );
        }

        $primaryTrainer = $gym->trainers()->where('email', 'andi@gym.test')->firstOrFail();
        $functionalTrainer = $gym->trainers()->where('email', 'budi@gym.test')->firstOrFail();
        $weightLossTrainer = $gym->trainers()->where('email', 'rina@gym.test')->firstOrFail();
        foreach ([
            'MBR-000001' => $primaryTrainer,
            'MBR-000002' => $primaryTrainer,
            'MBR-000004' => $functionalTrainer,
            'MBR-000006' => $weightLossTrainer,
        ] as $memberNumber => $trainer) {
            TrainerMember::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_id' => $gym->members()->where('member_number', $memberNumber)->firstOrFail()->getKey(),
                    'is_active' => true,
                ],
                [
                    'trainer_id' => $trainer->getKey(),
                    'assigned_at' => now(),
                    'assigned_by' => $owner->getKey(),
                    'ended_at' => null,
                    'ended_by' => null,
                ],
            );
        }

        $gym->forceFill([
            'next_trainer_sequence' => max($gym->next_trainer_sequence, 4),
        ])->save();

        $membershipPlans = [
            ['name' => 'Daily Pass', 'duration' => 1, 'duration_unit' => MembershipDurationUnit::Day, 'price' => '50000.00', 'description' => 'Akses gym selama satu hari.', 'is_active' => true],
            ['name' => 'Monthly', 'duration' => 1, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '250000.00', 'description' => 'Paket membership bulanan.', 'is_active' => true],
            ['name' => '3 Months', 'duration' => 3, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '650000.00', 'description' => 'Paket tiga bulan dengan harga lebih hemat.', 'is_active' => true],
            ['name' => '6 Months', 'duration' => 6, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '1200000.00', 'description' => 'Paket membership enam bulan.', 'is_active' => true],
            ['name' => '12 Months', 'duration' => 12, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '2200000.00', 'description' => 'Paket membership tahunan.', 'is_active' => false],
        ];

        foreach ($membershipPlans as $membershipPlanData) {
            MembershipPlan::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'name' => $membershipPlanData['name'],
                ],
                $membershipPlanData,
            );
        }

        $monthlyPlan = $gym->membershipPlans()->where('name', 'Monthly')->firstOrFail();
        $threeMonthPlan = $gym->membershipPlans()->where('name', '3 Months')->firstOrFail();
        $aditya = $gym->members()->where('member_number', 'MBR-000001')->firstOrFail();
        $nadia = $gym->members()->where('member_number', 'MBR-000002')->firstOrFail();
        $rizky = $gym->members()->where('member_number', 'MBR-000003')->firstOrFail();
        $salsabila = $gym->members()->where('member_number', 'MBR-000004')->firstOrFail();
        $today = today($gym->timezone);

        $adityaPreviousStart = $today->copy()->subMonthNoOverflow();
        $adityaPrevious = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $aditya->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $adityaPreviousStart,
                'end_date' => $adityaPreviousStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );
        $adityaRenewalStart = $adityaPrevious->end_date->copy()->addDay();

        $adityaRenewal = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $aditya->getKey(),
                'renewed_from_id' => $adityaPrevious->getKey(),
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $adityaRenewalStart,
                'end_date' => $adityaRenewalStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $nadiaStart = $today->copy()->subMonthNoOverflow()->addDays(3);
        $nadiaMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $nadia->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $nadiaStart,
                'end_date' => $nadiaStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $rizkyStart = $today->copy()->subMonthsNoOverflow(4);
        $rizkyMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $rizky->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $threeMonthPlan->getKey(),
                'plan_name' => $threeMonthPlan->name,
                'duration' => $threeMonthPlan->duration,
                'duration_unit' => $threeMonthPlan->duration_unit,
                'price' => $threeMonthPlan->price,
                'start_date' => $rizkyStart,
                'end_date' => $rizkyStart->copy()->addMonthsNoOverflow(3)->subDay(),
            ],
        );

        $salsabilaStart = $today->copy()->addWeek();
        $salsabilaMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $salsabila->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $salsabilaStart,
                'end_date' => $salsabilaStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $invoiceMonth = $today->format('Ym');
        $paymentExamples = [
            [$adityaPrevious, PaymentStatus::Paid, PaymentMethod::Cash, $today->copy()->subWeeks(3), 'Pembayaran periode sebelumnya.'],
            [$adityaRenewal, PaymentStatus::Pending, null, null, null],
            [$nadiaMembership, PaymentStatus::Paid, PaymentMethod::BankTransfer, $today->copy()->subWeeks(2), 'Transfer terverifikasi.'],
            [$rizkyMembership, PaymentStatus::Paid, PaymentMethod::DebitCard, $today->copy()->subMonths(3), null],
            [$salsabilaMembership, PaymentStatus::Pending, null, null, null],
        ];

        foreach ($paymentExamples as $index => [$membership, $status, $method, $paidAt, $notes]) {
            Payment::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_membership_id' => $membership->getKey(),
                ],
                [
                    'member_id' => $membership->member_id,
                    'type' => PaymentType::Membership,
                    'member_pt_package_id' => null,
                    'invoice_number' => sprintf('INV-%s-%06d', $invoiceMonth, $index + 1),
                    'amount' => $membership->price,
                    'status' => $status,
                    'method' => $method,
                    'paid_at' => $paidAt,
                    'notes' => $notes,
                    'received_by_id' => $status === PaymentStatus::Paid
                        ? $owner->getKey()
                        : null,
                ],
            );
        }

        $gym->forceFill([
            'next_invoice_sequence' => max($gym->next_invoice_sequence, count($paymentExamples) + 1),
        ])->save();

        $checkInExamples = [
            [$aditya, $adityaRenewal, now($gym->timezone)->subHour()->startOfHour()],
            [$nadia, $nadiaMembership, now($gym->timezone)->subHours(2)->startOfHour()],
            [$aditya, $adityaPrevious, now($gym->timezone)->subDay()->startOfDay()->addHours(17)],
        ];

        foreach ($checkInExamples as [$member, $membership, $checkedInAt]) {
            CheckIn::query()->firstOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_id' => $member->getKey(),
                    'checked_in_at' => $checkedInAt->copy()->utc(),
                ],
                [
                    'member_membership_id' => $membership->getKey(),
                    'created_by' => $owner->getKey(),
                ],
            );
        }

        $ptPackages = [
            ['name' => 'PT Trial', 'session_count' => 1, 'validity_days' => 14, 'price' => '150000.00', 'description' => 'Satu sesi untuk asesmen dan pengenalan program.', 'is_active' => true],
            ['name' => 'PT Starter', 'session_count' => 4, 'validity_days' => 45, 'price' => '550000.00', 'description' => 'Empat sesi pendampingan untuk membangun kebiasaan latihan.', 'is_active' => true],
            ['name' => 'PT Regular', 'session_count' => 8, 'validity_days' => 90, 'price' => '1000000.00', 'description' => 'Delapan sesi progresif dengan trainer pilihan.', 'is_active' => true],
        ];

        foreach ($ptPackages as $ptPackageData) {
            PtPackage::query()->updateOrCreate(
                ['gym_id' => $gym->getKey(), 'name' => $ptPackageData['name']],
                $ptPackageData,
            );
        }

        $ptRegular = $gym->ptPackages()->where('name', 'PT Regular')->firstOrFail();
        $ptStarter = $gym->ptPackages()->where('name', 'PT Starter')->firstOrFail();
        $ptExamples = [
            [$aditya, $primaryTrainer, $ptRegular, 2, $today->copy()->subWeeks(2), $today->copy()->addDays(75)],
            [$nadia, $primaryTrainer, $ptStarter, 0, $today->copy()->subWeek(), $today->copy()->addDays(38)],
            [$salsabila, $functionalTrainer, $ptRegular, 0, $today->copy(), $today->copy()->addDays(89)],
        ];
        $memberPtPackages = [];

        foreach ($ptExamples as $index => [$member, $trainer, $package, $usedSessions, $startDate, $expiresAt]) {
            $memberPtPackage = MemberPtPackage::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_id' => $member->getKey(),
                    'pt_package_id' => $package->getKey(),
                ],
                [
                    'trainer_id' => $trainer->getKey(),
                    'total_sessions' => $package->session_count,
                    'used_sessions' => $usedSessions,
                    'start_date' => $startDate,
                    'expires_at' => $expiresAt,
                    'price' => $package->price,
                    'status' => MemberPtPackageStatus::Active,
                    'payment_status' => PaymentStatus::Paid,
                    'notes' => 'Data demo Personal Training.',
                    'created_by' => $owner->getKey(),
                ],
            );
            $memberPtPackages[] = $memberPtPackage;

            Payment::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_pt_package_id' => $memberPtPackage->getKey(),
                ],
                [
                    'member_id' => $member->getKey(),
                    'type' => PaymentType::PersonalTraining,
                    'member_membership_id' => null,
                    'invoice_number' => sprintf('INV-%s-%06d', $invoiceMonth, $index + 6),
                    'amount' => $package->price,
                    'method' => $index === 0 ? PaymentMethod::BankTransfer : PaymentMethod::Cash,
                    'status' => PaymentStatus::Paid,
                    'paid_at' => now()->subDays(4 - $index),
                    'notes' => 'Pembayaran paket PT demo.',
                    'received_by_id' => $owner->getKey(),
                ],
            );
        }

        [$adityaPt, $nadiaPt, $salsabilaPt] = $memberPtPackages;
        $sessionExamples = [
            [$adityaPt, $today->copy()->subDays(8)->setTime(18, 0), PtSessionStatus::Completed, true, $today->copy()->subDays(8)->setTime(19, 0), null],
            [$adityaPt, $today->copy()->subDays(3)->setTime(18, 0), PtSessionStatus::NoShow, true, null, null],
            [$adityaPt, now($gym->timezone)->addHours(2)->startOfHour(), PtSessionStatus::Scheduled, false, null, null],
            [$nadiaPt, $today->copy()->subDays(2)->setTime(17, 0), PtSessionStatus::Cancelled, false, null, 'Member meminta perubahan jadwal.'],
            [$nadiaPt, $today->copy()->addDay()->setTime(17, 0), PtSessionStatus::Scheduled, false, null, null],
            [$salsabilaPt, $today->copy()->addDays(3)->setTime(19, 0), PtSessionStatus::Scheduled, false, null, null],
        ];

        foreach ($sessionExamples as [$memberPtPackage, $scheduledAt, $status, $quotaConsumed, $completedAt, $cancellationReason]) {
            PtSession::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_pt_package_id' => $memberPtPackage->getKey(),
                    'status' => $status,
                ],
                [
                    'member_id' => $memberPtPackage->member_id,
                    'trainer_id' => $memberPtPackage->trainer_id,
                    'scheduled_at' => $scheduledAt->copy()->utc(),
                    'duration_minutes' => 60,
                    'completed_at' => $completedAt?->copy()->utc(),
                    'notes' => 'Sesi demo Personal Training.',
                    'cancellation_reason' => $cancellationReason,
                    'quota_consumed' => $quotaConsumed,
                    'created_by' => $owner->getKey(),
                ],
            );
        }

        $gym->forceFill([
            'next_invoice_sequence' => max($gym->next_invoice_sequence, 9),
        ])->save();
    }
}
