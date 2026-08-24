<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private readonly Request $request) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'gym_name' => ['required', 'string', 'max:120'],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $gym = Gym::create([
                'name' => $input['gym_name'],
                'slug' => $this->uniqueSlug($input['gym_name']),
            ]);

            $gym->users()->attach($user, [
                'role' => GymRole::Owner->value,
                'status' => GymUserStatus::Active->value,
            ]);

            ActivityLog::create([
                'gym_id' => $gym->getKey(),
                'user_id' => $user->getKey(),
                'event' => 'user.created',
                'subject_type' => User::class,
                'subject_id' => $user->getKey(),
                'properties' => ['source' => 'registration'],
                'ip_address' => $this->request->ip(),
            ]);

            return $user;
        });
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'gym';

        return Str::limit($baseSlug, 120, '').'-'.Str::lower(Str::random(6));
    }
}
