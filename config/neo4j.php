<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Neo4j Configuration
    |--------------------------------------------------------------------------
    */

    'default' => env('NEO4J_DATABASE', 'neo4j'),

    'connections' => [
        'default' => [
            'scheme'   => 'bolt',
            'host'     => env('NEO4J_HOST', '127.0.0.1'),
            'port'     => env('NEO4J_PORT', 7687),
            'username' => env('NEO4J_USERNAME', 'neo4j'),
            'password' => env('NEO4J_PASSWORD', 'neo4jSecret1'),
            'database' => env('NEO4J_DATABASE', 'neo4j'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Graph node labels
    |--------------------------------------------------------------------------
    */

    'labels' => [
        'user'        => 'User',
        'institution' => 'Institution',
        'document'    => 'Document',
        'case'        => 'Case',
        'role'        => 'Role',
    ],

    /*
    |--------------------------------------------------------------------------
    | Graph relationship types
    |--------------------------------------------------------------------------
    */

    'relationships' => [
        'works_at'    => 'WORKS_AT',
        'issues'      => 'ISSUES',
        'verifies'    => 'VERIFIES',
        'manages'     => 'MANAGES',
        'same_case'   => 'SAME_CASE',
        'related_to'  => 'RELATED_TO',
        'has'         => 'HAS',
        'member_of'   => 'MEMBER_OF',
        'submitted'   => 'SUBMITTED',
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy cache TTL (seconds)
    |--------------------------------------------------------------------------
    */

    'policy_cache_ttl' => 300,
];
