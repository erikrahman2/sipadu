<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$sub = \App\Models\PublicSubmission::latest()->first();

if ($sub) {
    echo "\n=== PENGAJUAN STATUS ===\n\n";
    echo "ID: " . $sub->id . "\n";
    echo "Token: " . $sub->tracking_token . "\n";
    echo "Status: " . $sub->status . "\n";
    echo "Documents: " . $sub->documents()->count() . "\n";
    echo "Petitioner: " . $sub->petitioner_name . "\n";
    echo "Created: " . $sub->created_at . "\n";
    echo "\n";
}
