CREATE DATABASE IF NOT EXISTS appverk_tasks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE appverk_tasks;

GRANT ALL PRIVILEGES ON appverk_tasks.* TO 'appverk_user'@'%';
FLUSH PRIVILEGES;

