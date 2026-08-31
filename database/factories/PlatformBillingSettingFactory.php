<?php

namespace Database\Factories;

use App\Models\PlatformBillingSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformBillingSetting>
 */
class PlatformBillingSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'bank_name' => 'Bank Central Asia',
            'account_name' => 'PT GymFlow Indonesia',
            'account_number' => fake()->numerify('##########'),
            'instructions' => 'Cantumkan nama subscriber pada berita transfer.',
        ];
    }
}
