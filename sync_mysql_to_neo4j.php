<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Institution;
use App\Models\CaseModel;
use App\Models\Document;
use App\Services\GraphService;

echo "=== Sync MySQL to Neo4j for ReBAC ===\n\n";

$graph = app(GraphService::class);
$stats = [
    'users' => 0,
    'institutions' => 0,
    'cases' => 0,
    'documents' => 0,
    'relationships' => 0,
];

try {
    // 1. Sync Institutions
    echo "🏢 Step 1: Syncing Institutions...\n";
    $institutions = Institution::all();
    
    foreach ($institutions as $inst) {
        $graph->upsertInstitution([
            'id' => $inst->id,
            'code' => $inst->code,
            'name' => $inst->name,
            'type' => $inst->type,
        ]);
        $stats['institutions']++;
    }
    echo "   ✅ Synced {$stats['institutions']} institutions\n\n";
    
    // 2. Sync Users + WORKS_AT relationships
    echo "👥 Step 2: Syncing Users...\n";
    $users = User::with('institution')->get();
    
    foreach ($users as $user) {
        $graph->upsertUser([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'institution_id' => $user->institution_id,
        ]);
        
        // Create WORKS_AT relationship
        if ($user->institution_id) {
            $graph->run('
                MATCH (u:User {mysql_id: $user_id})
                MATCH (i:Institution {mysql_id: $inst_id})
                MERGE (u)-[:WORKS_AT]->(i)
            ', [
                'user_id' => $user->id,
                'inst_id' => $user->institution_id,
            ]);
            $stats['relationships']++;
        }
        
        $stats['users']++;
    }
    echo "   ✅ Synced {$stats['users']} users\n";
    echo "   ✅ Created {$stats['relationships']} WORKS_AT relationships\n\n";
    
    // 3. Sync Cases + relationships
    echo "📂 Step 3: Syncing Cases...\n";
    $cases = CaseModel::with(['institution', 'submitter'])->get();
    
    foreach ($cases as $case) {
        $graph->upsertCase([
            'id' => $case->id,
            'case_number' => $case->case_number,
            'tracking_token' => $case->tracking_token,
            'status' => $case->status,
            'institution_id' => $case->institution_id,
        ]);
        
        // Create SUBMITTED relationship (User -> Case)
        if ($case->submitter_id) {
            $graph->run('
                MATCH (u:User {mysql_id: $user_id})
                MATCH (c:Case {mysql_id: $case_id})
                MERGE (u)-[:SUBMITTED]->(c)
            ', [
                'user_id' => $case->submitter_id,
                'case_id' => $case->id,
            ]);
            $stats['relationships']++;
        }
        
        // Create ISSUES relationship (Institution -> Case)
        if ($case->institution_id) {
            $graph->run('
                MATCH (i:Institution {mysql_id: $inst_id})
                MATCH (c:Case {mysql_id: $case_id})
                MERGE (i)-[:ISSUES]->(c)
            ', [
                'inst_id' => $case->institution_id,
                'case_id' => $case->id,
            ]);
            $stats['relationships']++;
        }
        
        $stats['cases']++;
    }
    echo "   ✅ Synced {$stats['cases']} cases\n\n";
    
    // 4. Sync Documents
    echo "📄 Step 4: Syncing Documents...\n";
    $documents = Document::all();
    
    foreach ($documents as $doc) {
        $graph->upsertDocument([
            'id' => $doc->id,
            'document_type' => $doc->document_type,
            'status' => $doc->status,
            'case_id' => $doc->case_id,
        ]);
        
        // Create HAS relationship (Case -> Document)
        if ($doc->case_id) {
            $graph->run('
                MATCH (c:Case {mysql_id: $case_id})
                MATCH (d:Document {mysql_id: $doc_id})
                MERGE (c)-[:HAS]->(d)
            ', [
                'case_id' => $doc->case_id,
                'doc_id' => $doc->id,
            ]);
            $stats['relationships']++;
        }
        
        $stats['documents']++;
    }
    echo "   ✅ Synced {$stats['documents']} documents\n\n";
    
    // 5. Verify sync
    echo "🔍 Step 5: Verifying Sync...\n";
    
    $result = $graph->run('
        MATCH (n)
        RETURN labels(n)[0] AS label, count(n) AS count
        ORDER BY count DESC
    ');
    
    $totalNodes = 0;
    foreach ($result as $row) {
        $count = $row['count'];
        $totalNodes += $count;
        echo "   - " . str_pad($row['label'], 15) . ": {$count} nodes\n";
    }
    
    $relResult = $graph->run('MATCH ()-[r]->() RETURN count(r) AS count');
    $totalRels = $relResult[0]['count'] ?? 0;
    
    echo "\n📊 Summary:\n";
    echo "   Total Nodes: {$totalNodes}\n";
    echo "   Total Relationships: {$totalRels}\n\n";
    
    echo "✅ Sync Complete!\n\n";
    echo "Next: Test ReBAC policies\n";
    echo "   - Login ke dashboard (http://localhost:8000)\n";
    echo "   - Coba akses kasus dengan user berbeda\n";
    echo "   - ReBAC akan otomatis filter data sesuai relasi graph\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Sync Failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}
