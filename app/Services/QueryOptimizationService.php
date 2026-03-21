<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QueryOptimizationService
{
    /**
     * Get model with eager loading and caching.
     */
    public static function getWithCache(string $modelClass, array $relations = [], int $ttl = 3600)
    {
        $cacheKey = 'model_' . $modelClass . '_' . md5(json_encode($relations));
        
        return Cache::remember($cacheKey, $ttl, function () use ($modelClass, $relations) {
            $query = $modelClass::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            return $query->get();
        });
    }

    /**
     * Get paginated results with caching.
     */
    public static function getPaginatedWithCache(
        string $modelClass,
        int $perPage = 15,
        array $relations = [],
        int $ttl = 3600
    ) {
        $page = request()->get('page', 1);
        $cacheKey = 'paginated_' . $modelClass . '_p' . $page . '_' . md5(json_encode($relations));
        
        return Cache::remember($cacheKey, $ttl, function () use ($modelClass, $perPage, $relations) {
            $query = $modelClass::query();
            
            if (!empty($relations)) {
                $query->with($relations);
            }
            
            return $query->paginate($perPage);
        });
    }

    /**
     * Clear cache for a model.
     */
    public static function clearCache(string $modelClass): void
    {
        $keys = Cache::many([
            'model_' . $modelClass . '*',
            'paginated_' . $modelClass . '*',
        ]);
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Execute query with connection timeout.
     */
    public static function executeWithTimeout(callable $callback, int $timeout = 5)
    {
        try {
            set_time_limit($timeout);
            return $callback();
        } catch (\Exception $e) {
            logger()->error('Query timeout or error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Chunk large results for memory efficiency.
     */
    public static function chunkResults(string $modelClass, callable $callback, int $chunkSize = 1000): void
    {
        $modelClass::query()
            ->orderBy('id')
            ->chunk($chunkSize, $callback);
    }
}
