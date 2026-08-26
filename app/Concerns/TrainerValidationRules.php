<?php

namespace App\Concerns;

use App\Enums\TrainerStatus;
use App\Models\Trainer;
use App\Support\GymContext;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait TrainerValidationRules
{
    /** @return array<string, array<mixed>> */
    protected function trainerRules(GymContext $gymContext, ?int $ignoreId = null): array
    {
        $gymId = $gymContext->gymId();
        $uniqueEmail = Rule::unique(Trainer::class, 'email')
            ->where(fn (Builder $query): Builder => $query->where('gym_id', $gymId));
        if ($ignoreId !== null) {
            $uniqueEmail->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255', $uniqueEmail],
            'specialization' => ['nullable', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(TrainerStatus::class)],
            'joined_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareTrainerForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'phone' => Str::squish((string) $this->input('phone')),
            'email' => $this->filled('email')
                ? Str::of((string) $this->input('email'))->trim()->lower()->toString()
                : null,
            'specialization' => $this->filled('specialization')
                ? Str::squish((string) $this->input('specialization'))
                : null,
            'bio' => $this->filled('bio')
                ? Str::of((string) $this->input('bio'))->trim()->toString()
                : null,
            'notes' => $this->filled('notes')
                ? Str::of((string) $this->input('notes'))->trim()->toString()
                : null,
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor telepon hanya boleh berisi angka, spasi, dan simbol telepon.',
            'email.unique' => 'Email sudah digunakan oleh profil PT atau akun login lain.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return $this->trainerAttributes();
    }

    /** @return array<string, string> */
    protected function trainerAttributes(): array
    {
        return [
            'name' => 'nama trainer',
            'phone' => 'nomor telepon',
            'email' => 'email',
            'specialization' => 'spesialisasi',
            'bio' => 'bio',
            'status' => 'status',
            'joined_at' => 'tanggal bergabung',
            'notes' => 'catatan',
        ];
    }
}
