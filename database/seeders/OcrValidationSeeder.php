<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OcrValidationSeeder extends Seeder
{
    public function run(): void
    {
        if (isset($this->command)) {
            $this->command->info('OCR validation demo seed disabled. No sample cases were created.');
        }
    }
}
