<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class OptimizeApplication extends Command
{
    protected $signature = 'optimize:all';
    protected $description = 'Run all performance optimization commands';

    public function handle(): int
    {
        $this->info('🚀 Starting full application optimization...');
        $this->newLine();

        try {
            // 1. Clear all caches
            $this->info('📦 Clearing caches...');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');
            Artisan::call('config:clear');
            $this->line('✓ Caches cleared');

            // 2. Optimize autoloader
            $this->info('📚 Optimizing autoloader...');
            Artisan::call('optimize');
            $this->line('✓ Autoloader optimized');

            // 3. Cache configuration
            $this->info('⚙️  Caching configuration...');
            Artisan::call('config:cache');
            $this->line('✓ Configuration cached');

            // 4. Cache routes
            $this->info('🛣️  Caching routes...');
            Artisan::call('route:cache');
            $this->line('✓ Routes cached');

            // 5. Cache views
            $this->info('👁️  Precompiling views...');
            Artisan::call('view:cache');
            $this->line('✓ Views cached');

            // 6. Run migrations if needed
            if ($this->confirm('Run database migrations?', false)) {
                Artisan::call('migrate', ['--force' => true]);
                $this->line('✓ Migrations executed');
            }

            // 7. Stats
            $this->newLine();
            $this->info('✅ Optimization complete!');
            $this->line('Performance improvements applied:');
            $this->line('  • Configuration cached');
            $this->line('  • Routes cached');
            $this->line('  • Views precompiled');
            $this->line('  • Autoloader optimized');
            $this->line('  • All caches cleared');

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Optimization failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
