CREATE TABLE IF NOT EXISTS request_limits (
    bucket_key CHAR(64) NOT NULL PRIMARY KEY,
    bucket BIGINT NOT NULL,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_request_limit_expiry (bucket)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
