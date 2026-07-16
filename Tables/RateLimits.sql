-- Backing store for RateLimiter::tooManyAttempts() when APCu isn't
-- available (see Server/Function/Cache.php). One row per rate-limited key
-- (e.g. "login:1.2.3.4", "checkout:42"); the window resets in place rather
-- than needing a cleanup job.
CREATE TABLE RateLimits (
  RateKey      VARCHAR(191) PRIMARY KEY NOT NULL,
  AttemptCount INT(11) NOT NULL DEFAULT 1,
  WindowStart  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
