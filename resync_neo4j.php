<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$graph = app(\App\Services\GraphService::class);

echo "🗑️  Clearing Neo4j database...\n";
try {
    $graph->run('MATCH (n) DETACH DELETE n');
    echo "✅ Database cleared\n\n";
} catch (\Throwable $e) {
    echo "❌ Error clearing database: {$e->getMessage()}\n";
    exit(1);
}

echo "📊 Re-syncing data from MySQL...\n\n";

// Users
$users = \App\Models\User::all();
echo "👥 Syncing {$users->count()} users:\n";
foreach ($users as $user) {
    $graph->upsertUser([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'institution_id' => $user->institution_id,
    ]);
    echo "  ✓ {$user->name} ({$user->email})\n";
}

// Institutions
$institutions = \App\Models\Institution::all();
echo "\n🏢 Syncing {$institutions->count()} institutions:\n";
foreach ($institutions as $inst) {
    $graph->upsertInstitution([
        'id' => $inst->id,
        'code' => $inst->code,
        'name' => $inst->name,
        'type' => $inst->type,
    ]);
    echo "  ✓ [{$inst->code}] {$inst->name}\n";
}

// Cases
$cases = \App\Models\CaseModel::all();
echo "\n📂 Syncing {$cases->count()} cases:\n";
foreach ($cases as $case) {
    $graph->upsertCase([
        'id' => $case->id,
        'case_number' => $case->case_number,
        'tracking_token' => $case->tracking_token,
        'status' => $case->status,
    ]);
    echo "  ✓ {$case->case_number} - Status: {$case->status}\n";
}

// Documents
$documents = \App\Models\Document::all();
echo "\n📄 Syncing {$documents->count()} documents:\n";
foreach ($documents as $doc) {
    $graph->upsertDocument([
        'id' => $doc->id,
        'document_type' => $doc->document_type,
        'status' => $doc->status,
        'case_id' => $doc->case_id,
    ]);
    echo "  ✓ Doc #{$doc->id} - Type: {$doc->document_type}\n";
}

echo "\n🔗 Creating relationships...\n";

// WORKS_AT: User -> Institution
foreach ($users as $user) {
    if ($user->institution_id) {
        $graph->linkUserToInstitution($user->id, $user->institution_id);
        echo "  ✓ {$user->name} WORKS_AT {$user->institution->name}\n";
    }
}

// MANAGES: Institution -> Case
foreach ($cases as $case) {
    if ($case->institution_id) {
        $graph->linkInstitutionToCase($case->institution_id, $case->id);
        echo "  ✓ {$case->institution->name} MANAGES {$case->case_number}\n";
    }
}

// SUBMITTED: User -> Case
foreach ($cases as $case) {
    if ($case->created_by) {
        $graph->linkUserToCase($case->created_by, $case->id, 'SUBMITTED');
        echo "  ✓ User #{$case->created_by} SUBMITTED {$case->case_number}\n";
    }
}

// ISSUES: Institution -> Case
foreach ($cases as $case) {
    if ($case->institution_id) {
        $graph->linkUserToCase($case->institution_id, $case->id, 'ISSUES');
        echo "  ✓ Institution #{$case->institution_id} ISSUES {$case->case_number}\n";
    }
}

// HAS: Case -> Document
foreach ($documents as $doc) {
    if ($doc->case_id) {
        $graph->linkCaseToDocument($doc->case_id, $doc->id);
        echo "  ✓ Case #{$doc->case_id} HAS Document #{$doc->id}\n";
    }
}

echo "\n✅ Re-sync complete!\n";
echo "\n🔍 Verifying data...\n";

// Check nodes with properties
$result = $graph->run("
    MATCH (u:User)
    RETURN u.mysql_id AS id, u.name AS name, u.email AS email
    LIMIT 3
");

echo "\n📊 Sample User Nodes:\n";
foreach ($result as $row) {
    echo "  - ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}\n";
}

$result = $graph->run("
    MATCH ()-[r]->()
    RETURN type(r) AS rel_type, COUNT(r) AS count
");

echo "\n🔗 Relationships:\n";
foreach ($result as $row) {
    echo "  - {$row['rel_type']}: {$row['count']}\n";
}

echo "\n✨ Done!\n";
