<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('platform:promote-admin {email : Email user yang akan diberi akses platform}')]
#[Description('Promosikan user yang sudah ada menjadi Platform Super Admin')]
class PromotePlatformAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $this->error("User dengan email {$email} tidak ditemukan.");

            return self::FAILURE;
        }

        $user->forceFill(['is_platform_admin' => true])->save();
        $this->info("{$user->name} sekarang menjadi Platform Super Admin.");

        return self::SUCCESS;
    }
}
