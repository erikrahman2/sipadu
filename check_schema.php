<?php
// Quick database schema check
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->handle(
    $request = \Illuminate\Http\Request::capture()
);

$columns = \DB::select("DESCRIBE public_submissions");

echo "\n=== PUBLIC_SUBMISSIONS TABLE STRUCTURE ===\n\n";
echo str_pad("Field", 30) . " | " . str_pad("Type", 20) . " | " . str_pad("Null", 8) . " | Default\n";
echo str_repeat("-", 100) . "\n";

foreach ($columns as $col) {
    $null = $col->Null === 'YES' ? 'YES' : 'NO';
    echo str_pad($col->Field, 30) . " | " . str_pad($col->Type, 20) . " | " . str_pad($null, 8) . " | " . ($col->Default ?? 'NULL') . "\n";
}

echo "\n";
