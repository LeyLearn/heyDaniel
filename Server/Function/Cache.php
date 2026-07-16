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
 * Fixed-window rate limiter. Meant as a cheap first line of defense against
 * brute-force/abuse (login, register, checkout) on a single box — not a
 * hard security boundary, so it fails open on any error rather than
 * blocking legitimate traffic.
 *
 * Prefers APCu (no I/O) when available, but this environment's PHP build
 * doesn't have it loaded, so a small DB-backed counter (RateLimits table)
 * is the fallback that actually works today. Written to prefer APCu
 * automatically the moment it's installed, with no call-site changes.
 */
class RateLimiter
{
    /**
     * Records one attempt for $key and reports whether it has exceeded
     * $maxAttempts within the current $windowSeconds window. $db is only
     * used for the fallback path and may be null if APCu is available.
     */
    public static function tooManyAttempts(?\PDO $db, string $key, int $maxAttempts, int $windowSeconds): bool
    {
        if (extension_loaded('apcu')) {
            $cacheKey = "ratelimit:{$key}";

            // Only seeds the counter if this is the first attempt in the
            // window; a concurrent request that already created it is a no-op.
            apcu_add($cacheKey, 0, $windowSeconds);
            $count = apcu_inc($cacheKey);

            return $count !== false && $count > $maxAttempts;
        }

        if ($db === null) {
            return false;
        }

        try {
            // A window "expires" by resetting the count to 1 the first time
            // it's touched after WindowStart ages past $windowSeconds,
            // rather than needing a separate cleanup/cron job.
            $stmt = $db->prepare("
                INSERT INTO RateLimits (RateKey, AttemptCount, WindowStart)
                VALUES (?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    AttemptCount = IF(WindowStart < NOW() - INTERVAL ? SECOND, 1, AttemptCount + 1),
                    WindowStart  = IF(WindowStart < NOW() - INTERVAL ? SECOND, NOW(), WindowStart)
            ");
            $stmt->execute([$key, $windowSeconds, $windowSeconds]);

            $stmt = $db->prepare("SELECT AttemptCount FROM RateLimits WHERE RateKey = ? LIMIT 1");
            $stmt->execute([$key]);

            return (int)$stmt->fetchColumn() > $maxAttempts;
        } catch (\PDOException $e) {
            error_log("DB Error in RateLimiter: " . $e->getMessage());
            return false;
        }
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
