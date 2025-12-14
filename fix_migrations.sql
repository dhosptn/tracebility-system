-- Insert migration records manually
INSERT INTO migrations (migration, batch) VALUES
('0001_01_01_000000_create_users_table', 1),
('0001_01_01_000001_create_cache_table', 1),
('0001_01_01_000002_create_jobs_table', 1),
('2024_01_01_000000_create_master_data_tables', 1),
('2025_12_13_102303_create_production_monitoring_tables', 1)
ON DUPLICATE KEY UPDATE batch=batch;
