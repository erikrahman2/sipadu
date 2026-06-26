<?php

namespace Database\Factories;

use App\Models\CaseModel;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CaseModelFactory extends Factory
{
    protected $model = CaseModel::class;

    public function definition(): array
    {
        $statuses = ['DRAFT', 'SUBMITTED', 'OCR_PROCESSED', 'PA_REVIEW', 'DISDUKCAPIL_VALIDATION', 'COMPLETED', 'ARCHIVED', 'REJECTED'];
        
        return [
            'submitter_id' => User::factory(),
            'institution_id' => Institution::factory(),
            'case_number' => 'CASE-' . now()->format('Ymd') . '-' . strtoupper($this->faker->lexify('????????')),
            'tracking_token' => 'TRK' . strtoupper($this->faker->lexify('????????????????????????????????????')),
            'public_submission_id' => null,
            'source_type' => $this->faker->randomElement(['internal', 'public']),
            'petitioner_nik' => $this->faker->numerify('################'),
            'petitioner_name' => $this->faker->name(),
            'petitioner_phone' => $this->faker->phoneNumber(),
            'petitioner_alamat' => $this->faker->address(),
            'petitioner_rt_rw' => '001/002',
            'petitioner_kelurahan' => $this->faker->word(),
            'petitioner_kecamatan' => $this->faker->word(),
            'spouse_nik' => $this->faker->numerify('################'),
            'spouse_name' => $this->faker->name(),
            'spouse_alamat' => $this->faker->address(),
            'spouse_rt_rw' => '001/002',
            'spouse_kelurahan' => $this->faker->word(),
            'spouse_kecamatan' => $this->faker->word(),
            'divorce_date' => $this->faker->date(),
            'verdict_number' => $this->faker->numerify('###/Pdt.G/####/PA.###'),
            'status' => $this->faker->randomElement($statuses),
            'notes' => $this->faker->sentence(),
            'submitted_at' => now(),
            'completed_at' => null,
        ];
    }

    public function draft(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'DRAFT'];
        });
    }

    public function submitted(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'SUBMITTED'];
        });
    }

    public function disdukcapilValidation(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'DISDUKCAPIL_VALIDATION'];
        });
    }

    public function ocrProcessed(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'OCR_PROCESSED'];
        });
    }

    public function completed(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'COMPLETED',
                'completed_at' => now(),
            ];
        });
    }

    public function forSubmitter(User $user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            return ['submitter_id' => $user->id];
        });
    }

    public function forInstitution($institution): self
    {
        return $this->state(function (array $attributes) use ($institution) {
            $institutionId = is_object($institution) ? $institution->id : $institution;
            return ['institution_id' => $institutionId];
        });
    }
}
