<?php

namespace Database\Seeders;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Enums\MemberGender;
use App\Enums\MemberStatus;
use App\Models\Gym;
use App\Models\Member;
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

        $gym = Gym::query()->updateOrCreate(
            ['slug' => 'gymflow-demo'],
            [
                'name' => 'GymFlow Demo',
                'status' => GymStatus::Active,
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'membership_expiry_warning_days' => 7,
            ],
        );

        $accounts = [
            ['name' => 'Owner GymFlow', 'email' => 'owner@gym.test', 'role' => GymRole::Owner],
            ['name' => 'Front Desk GymFlow', 'email' => 'frontdesk@gym.test', 'role' => GymRole::Admin],
            ['name' => 'Trainer GymFlow', 'email' => 'trainer@gym.test', 'role' => GymRole::Trainer],
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
    }
}
