<?php

namespace Database\Seeders;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Models\Gym;
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
    }
}
