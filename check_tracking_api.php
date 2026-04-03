<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

use App\Models\PublicSubmission;
use App\Models\PublicSubmissionDocument;

$token = 'PUB-MQMQPX8OD0JKTAKKKM8Z';
$sub = PublicSubmission::where('tracking_token', $token)->with('documents')->first();

echo "=== Tracking Token Check ===\n";
echo "Token: {$token}\n";

if (!$sub) {
    echo "Submission NOT FOUND in database\n";
    exit(1);
}

echo "Found Submission:\n";
echo "  Tracking Token: {$sub->tracking_token}\n";
echo "  Status: {$sub->status}\n";
echo "  Petitioner: {$sub->petitioner_name}\n";
echo "  Documents Count: {$sub->documents->count()}\n";
echo "\nDocuments:\n";

foreach ($sub->documents as $doc) {
    $url = 'storage/' . $doc->stored_path;
    $filePath = storage_path('app/public/' . $doc->stored_path);
    $fileExists = file_exists($filePath);
    
    echo "\n  Type: {$doc->document_type}\n";
    echo "  Label: " . (PublicSubmissionDocument::$typeLabels[$doc->document_type] ?? $doc->document_type) . "\n";
    echo "  Stored Path: {$doc->stored_path}\n";
    echo "  Generated URL (asset): {$url}\n";
    echo "  Full File Path: {$filePath}\n";
    echo "  File Exists: " . ($fileExists ? 'YES' : 'NO') . "\n";
    if ($fileExists) {
        echo "  File Size: " . filesize($filePath) . " bytes\n";
    }
}
