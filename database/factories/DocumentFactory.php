<?php

namespace Database\Factories;

use App\Models\CaseModel;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $statuses = ['PENDING', 'PROCESSING', 'PROCESSED', 'VALIDATED', 'REJECTED'];
        $types = ['KTP', 'KTP_SUAMI', 'KTP_ISTRI', 'KK', 'AKTA_CERAI', 'PUTUSAN_PA', 'AKTA_NIKAH', 'SURAT_PENGANTAR', 'OTHER'];
        
        return [
            'case_id' => CaseModel::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => $this->faker->word() . '.pdf',
            'stored_name' => $this->faker->uuid() . '.pdf',
            'disk' => 'local',
            'path' => 'documents/' . $this->faker->uuid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1000, 5000000),
            'document_type' => $this->faker->randomElement($types),
            'status' => $this->faker->randomElement($statuses),
            'checksum' => hash('sha256', $this->faker->uuid()),
        ];
    }

    public function forCase($case): self
    {
        return $this->state(function (array $attributes) use ($case) {
            $caseId = is_object($case) ? $case->id : $case;
            return ['case_id' => $caseId];
        });
    }

    public function uploadedBy($user): self
    {
        return $this->state(function (array $attributes) use ($user) {
            $userId = is_object($user) ? $user->id : $user;
            return ['uploaded_by' => $userId];
        });
    }

    public function uploaded(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'PENDING'];
        });
    }

    public function processing(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'PROCESSING'];
        });
    }

    public function processed(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'PROCESSED'];
        });
    }

    public function failed(): self
    {
        return $this->state(function (array $attributes) {
            return ['status' => 'REJECTED'];
        });
    }

    public function asPdf(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'original_name' => $this->faker->word() . '.pdf',
                'stored_name' => $this->faker->uuid() . '.pdf',
                'mime_type' => 'application/pdf',
            ];
        });
    }

    public function asImage(): self
    {
        return $this->state(function (array $attributes) {
            $extension = $this->faker->randomElement(['jpg', 'png', 'jpeg']);
            return [
                'original_name' => $this->faker->word() . '.' . $extension,
                'stored_name' => $this->faker->uuid() . '.' . $extension,
                'mime_type' => 'image/' . ($extension === 'jpg' ? 'jpeg' : $extension),
            ];
        });
    }
}
