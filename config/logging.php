<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;
use Monolog\Processor\PsrLogMessageProcessor;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */
    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Deprecations Log Channel
    |--------------------------------------------------------------------------
    */
    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace'   => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */
    'channels' => [

        // ── Default application stack ──────────────────────────────────
        'stack' => [
            'driver'            => 'stack',
            'channels'          => ['single'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/laravel.log'),
            'level'  => env('LOG_LEVEL', 'debug'),
            'days'   => env('LOG_DAILY_DAYS', 14),
            'replace_placeholders' => true,
        ],

        'stderr' => [
            'driver'    => 'monolog',
            'level'     => env('LOG_LEVEL', 'debug'),
            'handler'   => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with'      => ['stream' => 'php://stderr'],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level'  => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level'  => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver'  => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // ── Domain-specific channels ───────────────────────────────────

        /**
         * OCR Service channel
         * Logs: OCR dispatch, processing steps, confidence scores, failures
         */
        'ocr' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/ocr/ocr.log'),
            'level'  => env('LOG_LEVEL_OCR', 'debug'),
            'days'   => 30,
            'replace_placeholders' => true,
        ],

        /**
         * Graph (Neo4j) sync channel
         * Logs: Cypher queries, outbox processing, sync success/failure
         */
        'graph' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/graph/graph.log'),
            'level'  => env('LOG_LEVEL_GRAPH', 'debug'),
            'days'   => 30,
            'replace_placeholders' => true,
        ],

        /**
         * ReBAC Policy channel
         * Logs: policy decisions (permit/deny), cache hits, path traversals
         */
        'policy' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/policy/policy.log'),
            'level'  => env('LOG_LEVEL_POLICY', 'info'),
            'days'   => 60,
            'replace_placeholders' => true,
        ],

        /**
         * Audit trail channel
         * Logs: all user actions recorded to audit_logs table
         * Kept longer for compliance
         */
        'audit' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/audit/audit.log'),
            'level'  => env('LOG_LEVEL_AUDIT', 'info'),
            'days'   => 365,
            'replace_placeholders' => true,
        ],

        /**
         * Workflow state machine channel
         * Logs: state transitions, role assertions, rejections
         */
        'workflow' => [
            'driver' => 'daily',
            'path'   => storage_path('logs/workflow/workflow.log'),
            'level'  => env('LOG_LEVEL_WORKFLOW', 'debug'),
            'days'   => 60,
            'replace_placeholders' => true,
        ],

    ],

];
