<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\GraphService;

echo "=== Fix WORKS_AT Relationships ===\n\n";

$graph = app(GraphService::class);

try {
    // Recreate WORKS_AT relationships from MySQL data
    echo "🔗 Step 1: Creating WORKS_AT relationships from MySQL...\n";
    
    $users = User::whereNotNull('institution_id')->get();
    $created = 0;
    
    foreach ($users as $user) {
        try {
            $result = $graph->run('
                MATCH (u:User {mysql_id: $user_id})
                MATCH (i:Institution {mysql_id: $inst_id})
                MERGE (u)-[:WORKS_AT]->(i)
                RETURN u.name AS user_name, i.name AS inst_name
            ', [
                'user_id' => $user->id,
                'inst_id' => $user->institution_id,
            ]);
            
            if (!empty($result)) {
                echo "   ✅ {$user->name} → WORKS_AT → {$result[0]['inst_name']}\n";
                $created++;
            }
        } catch (\Exception $e) {
            echo "   ❌ Failed for user {$user->name}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n   Total created: {$created} WORKS_AT relationships\n\n";
    
    // Now create MANAGES relationships
    echo "🔗 Step 2: Creating MANAGES relationships...\n";
    
    $manages = $graph->run('
        MATCH (u:User)-[:WORKS_AT]->(i:Institution)-[:ISSUES]->(c:Case)
        MERGE (u)-[:MANAGES]->(c)
        RETURN count(*) AS created
    ');
    
    $managesCount = $manages[0]['created'] ?? 0;
    echo "   ✅ Created {$managesCount} MANAGES relationships\n\n";
    
    // Verify
    echo "🔍 Step 3: Verification...\n\n";
    
    $relTypes = $graph->run('
        MATCH ()-[r]->()
        RETURN type(r) AS relationship, count(r) AS count
        ORDER BY count DESC
    ');
    
    echo "   All relationships:\n";
    foreach ($relTypes as $row) {
        echo "   - " . str_pad($row['relationship'], 15) . ": " . $row['count'] . "\n";
    }
    
    // Show sample access paths
    echo "\n   Sample access paths:\n";
    $paths = $graph->run('
        MATCH path = (u:User)-[*1..3]->(c:Case)
        WHERE u.email <> "admin@sipadu.go.id"
        RETURN 
            u.name AS user,
            [rel IN relationships(path) | type(rel)] AS path_types,
            c.case_number AS case_number
        LIMIT 5
    ');
    
    foreach ($paths as $row) {
        $pathTypes = [];
        foreach ($row['path_types'] as $type) {
            $pathTypes[] = $type;
        }
        $pathStr = implode(' → ', $pathTypes);
        echo "   {$row['user']} → {$pathStr} → {$row['case_number']}\n";
    }
    
    echo "\n✅ Relationships Fixed!\n\n";
    echo "Now run: php test_rebac.php\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Failed!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n\n";
    exit(1);
}
