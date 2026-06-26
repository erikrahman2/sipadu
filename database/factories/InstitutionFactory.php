<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $types = ['PA', 'DISDUKCAPIL'];
        
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement($types),
            'address' => $this->faker->address(),
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->companyEmail(),
            'active' => true,
        ];
    }

    public function asPA(): self
    {
        return $this->state(function (array $attributes) {
            return ['type' => 'PA'];
        });
    }

    public function asDisdukcapil(): self
    {
        return $this->state(function (array $attributes) {
            return ['type' => 'DISDUKCAPIL'];
        });
    }

    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return ['active' => false];
        });
    }
}
