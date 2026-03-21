<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GraphService;

echo "=== Fix Missing Relationships in Neo4j ===\n\n";

$graph = app(GraphService::class);

try {
    // 1. Create MANAGES relationship for management users
    echo "🔗 Step 1: Creating MANAGES relationships...\n";
    
    // Find cases and create MANAGES relationship through institution
    $result = $graph->run('
        MATCH (u:User)-[:WORKS_AT]->(i:Institution)-[:ISSUES]->(c:Case)
        WHERE NOT (u)-[:MANAGES]->(c)
        MERGE (u)-[:MANAGES]->(c)
        RETURN count(*) AS created
    ');
    
    $created = $result[0]['created'] ?? 0;
    echo "   ✅ Created {$created} MANAGES relationships\n\n";
    
    // 2. Verify all relationships
    echo "🔍 Step 2: Verifying Current Relationships...\n";
    
    $relTypes = $graph->run('
        MATCH ()-[r]->()
        RETURN type(r) AS relationship, count(r) AS count
        ORDER BY count DESC
    ');
    
    echo "   Relationship types:\n";
    foreach ($relTypes as $row) {
        echo "   - " . str_pad($row['relationship'], 15) . ": " . $row['count'] . "\n";
    }
    
    // 3. Show user → case access paths
    echo "\n🗺️  Step 3: User Access Paths to Cases...\n";
    
    $paths = $graph->run('
        MATCH path = (u:User)-[*1..3]->(c:Case)
        WHERE u.email <> "admin@sipadu.go.id"
        RETURN 
            u.name AS user, 
            [rel IN relationships(path) | type(rel)] AS path_types,
            c.case_number AS case_number
        LIMIT 10
    ');
    
    if (!empty($paths)) {
        foreach ($paths as $row) {
            $pathTypes = [];
            foreach ($row['path_types'] as $type) {
                $pathTypes[] = $type;
            }
            $pathStr = implode(' → ', $pathTypes);
            echo "   {$row['user']} → {$pathStr} → {$row['case_number']}\n";
        }
    } else {
        echo "   ⚠️  No access paths found\n";
        echo "   This means users are not connected to cases via relationships.\n";
    }
    
    echo "\n✅ Relationship Fix Complete!\n\n";
    echo "Run: php test_rebac.php (to verify)\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Fix Failed!\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
