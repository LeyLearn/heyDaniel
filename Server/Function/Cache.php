<?php
declare(strict_types=1);

/**
 * Caching layer using APCu (in-memory cache)
 * Fast for cache hits, graceful fallback to DB on misses
 */
class QueryCache
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const ENABLED = true;

    /**
     * Retrieve cached value
     */
    public static function get(string $key): ?array
    {
        if (!self::ENABLED || !extension_loaded('apcu')) {
            return null;
        }

        $cached = apcu_fetch($key);
        if ($cached === false) {
            return null;
        }

        return json_decode($cached, true);
    }

    /**
     * Store value in cache
     */
    public static function set(string $key, array $value, int $ttl = self::DEFAULT_TTL): void
    {
        if (!self::ENABLED || !extension_loaded('apcu')) {
            return;
        }

        apcu_store($key, json_encode($value), $ttl);
    }

    /**
     * Delete cached value
     */
    public static function delete(string $key): void
    {
        if (!self::ENABLED || !extension_loaded('apcu')) {
            return;
        }

        apcu_delete($key);
    }

    /**
     * Invalidate all cache entries matching pattern
     */
    public static function invalidatePattern(string $pattern): void
    {
        if (!self::ENABLED || !extension_loaded('apcu')) {
            return;
        }

        apcu_delete(new APCUIterator($pattern));
    }

    /**
     * Get or fetch pattern: try cache first, fallback to callable
     */
    public static function remember(string $key, callable $fetch, int $ttl = self::DEFAULT_TTL): ?array
    {
        $cached = self::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $result = $fetch();
        if ($result !== null) {
            self::set($key, $result, $ttl);
        }

        return $result;
    }
}

/**
 * Redis caching for distributed/larger datasets
 * Use for cross-server caching and session data
 */
class RedisCache
{
    private static ?Redis $redis = null;
    private const ENABLED = false; // Enable when Redis is installed
    private const HOST = 'localhost';
    private const PORT = 6379;
    private const DB = 0;

    /**
     * Get Redis connection (lazy loaded)
     */
    private static function connect(): ?Redis
    {
        if (!self::ENABLED) {
            return null;
        }

        if (self::$redis === null) {
            try {
                self::$redis = new Redis();
                self::$redis->connect(self::HOST, self::PORT);
                self::$redis->select(self::DB);
                self::$redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
            } catch (Exception $e) {
                error_log("Redis connection failed: " . $e->getMessage());
                return null;
            }
        }

        return self::$redis;
    }

    /**
     * Get from Redis
     */
    public static function get(string $key): ?array
    {
        $redis = self::connect();
        if (!$redis) {
            return null;
        }

        try {
            return $redis->get($key);
        } catch (Exception $e) {
            error_log("Redis get failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Set in Redis with TTL
     */
    public static function set(string $key, array $value, int $ttl = 3600): void
    {
        $redis = self::connect();
        if (!$redis) {
            return;
        }

        try {
            $redis->setex($key, $ttl, $value);
        } catch (Exception $e) {
            error_log("Redis set failed: " . $e->getMessage());
        }
    }

    /**
     * Delete from Redis
     */
    public static function delete(string $key): void
    {
        $redis = self::connect();
        if (!$redis) {
            return;
        }

        try {
            $redis->del($key);
        } catch (Exception $e) {
            error_log("Redis delete failed: " . $e->getMessage());
        }
    }
}
