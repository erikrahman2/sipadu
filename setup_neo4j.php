<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Neo4j ReBAC Setup for SiPadu ===\n\n";

// Baca config dari .env
$host = env('NEO4J_HOST', 'localhost');
$port = env('NEO4J_PORT', 7687);
$username = env('NEO4J_USERNAME', 'neo4j');
$password = env('NEO4J_PASSWORD', 'neo4jSecret1');
$database = env('NEO4J_DATABASE', 'neo4j');

echo "📋 Configuration:\n";
echo "   Host: bolt://{$host}:{$port}\n";
echo "   Username: {$username}\n";
echo "   Password: " . str_repeat('*', strlen($password)) . "\n";
echo "   Database: {$database}\n\n";

try {
    $graphService = app(\App\Services\GraphService::class);
    
    // Step 1: Test Connection
    echo "🔌 Step 1: Testing Connection...\n";
    $result = $graphService->run('RETURN 1 AS test');
    echo "   ✅ Connected successfully!\n\n";
    
    // Step 2: Create Constraints
    echo "🔐 Step 2: Creating Unique Constraints...\n";
    
    $constraints = [
        "CREATE CONSTRAINT user_mysql_id IF NOT EXISTS FOR (u:User) REQUIRE u.mysql_id IS UNIQUE",
        "CREATE CONSTRAINT institution_mysql_id IF NOT EXISTS FOR (i:Institution) REQUIRE i.mysql_id IS UNIQUE",
        "CREATE CONSTRAINT case_mysql_id IF NOT EXISTS FOR (c:Case) REQUIRE c.mysql_id IS UNIQUE",
        "CREATE CONSTRAINT document_mysql_id IF NOT EXISTS FOR (d:Document) REQUIRE d.mysql_id IS UNIQUE",
    ];
    
    foreach ($constraints as $cypher) {
        try {
            $graphService->run($cypher);
            echo "   ✅ " . substr($cypher, 18, 30) . "...\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "   ⏭️  " . substr($cypher, 18, 30) . "... (already exists)\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n📊 Step 3: Creating Indexes...\n";
    
    $indexes = [
        "CREATE INDEX user_email IF NOT EXISTS FOR (u:User) ON (u.email)",
        "CREATE INDEX case_tracking_token IF NOT EXISTS FOR (c:Case) ON (c.tracking_token)",
        "CREATE INDEX case_status IF NOT EXISTS FOR (c:Case) ON (c.status)",
        "CREATE INDEX institution_type IF NOT EXISTS FOR (i:Institution) ON (i.type)",
    ];
    
    foreach ($indexes as $cypher) {
        try {
            $graphService->run($cypher);
            echo "   ✅ " . substr($cypher, 13, 30) . "...\n";
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'An equivalent') !== false) {
                echo "   ⏭️  " . substr($cypher, 13, 30) . "... (already exists)\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Step 4: Check current data
    echo "\n📈 Step 4: Current Graph Status...\n";
    
    $nodeCount = $graphService->run('MATCH (n) RETURN count(n) AS total');
    $total = $nodeCount[0]['total'] ?? 0;
    echo "   Total nodes: {$total}\n";
    
    if ($total > 0) {
        $labels = $graphService->run('
            MATCH (n)
            RETURN labels(n)[0] AS label, count(n) AS count
            ORDER BY count DESC
        ');
        
        echo "\n   Breakdown by label:\n";
        foreach ($labels as $row) {
            echo "   - " . str_pad($row['label'] ?? 'Unknown', 15) . ": " . $row['count'] . "\n";
        }
    } else {
        echo "   ⚠️  Graph is empty - need initial sync from MySQL\n";
    }
    
    echo "\n✅ Neo4j Setup Complete!\n\n";
    echo "Next steps:\n";
    echo "1. Run: php sync_mysql_to_neo4j.php    (to sync data from MySQL)\n";
    echo "2. Test ReBAC policies via dashboard\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ Setup Failed!\n";
    echo "   Error: " . $e->getMessage() . "\n\n";
    
    echo "Troubleshooting:\n";
    echo "1. Pastikan Neo4j Desktop sudah RUNNING\n";
    echo "2. Buka Neo4j Browser (http://localhost:7474)\n";
    echo "3. Login pertama kali dengan neo4j/neo4j, lalu set password baru\n";
    echo "4. Update NEO4J_PASSWORD di .env sesuai password yang Anda set\n";
    echo "5. Restart script ini\n\n";
    
    exit(1);
}
