<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'nik' => $this->faker->numerify('################'),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'status' => 'ACTIVE',
            'institution_id' => null,
        ];
    }

    public function withInstitution($institutionId): self
    {
        return $this->state(function (array $attributes) use ($institutionId) {
            return ['institution_id' => $institutionId];
        });
    }

    public function asSubmitter(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'ACTIVE'];
        });
    }
}
