<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Layan;

class UpdateLayanIcons extends Command
{
    protected $signature = 'layan:update-icons';
    protected $description = 'Update icons for Layan records';

    public function handle()
    {
        $icons = [
            1 => 'fas fa-file-circle-xmark',
            2 => 'fas fa-heart',
            3 => 'fas fa-id-card',
            4 => 'fas fa-landmark',
        ];

        $count = 0;
        foreach (Layan::orderBy('urut')->get() as $l) {
            $idx = (int) $l->urut;
            $l->icon = $icons[$idx] ?? 'fas fa-file-alt';
            $l->save();
            $count++;
        }

        echo "Updated {$count} Layan records with icons." . PHP_EOL;
    }
}
