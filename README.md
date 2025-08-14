# Homework Service (MySQL version)

## Requirements
- PHP 8+ with PDO MySQL enabled
- MySQL 5.7+ / MariaDB 10.3+
- Web server (Apache/Nginx) with write permission on `/uploads`

## Setup
1. Create a MySQL database, e.g. `homework_service` with `utf8mb4` charset.
2. Edit **config.php** and set `host`, `port`, `dbname`, `username`, `password`.
3. Deploy files to your web root and ensure `/uploads` is writable.
4. Open `init_db.php` in your browser **once** to create tables and seed the admin:
   - Admin: `admin@example.com` / `admin123`
5. Go to `index.php`. Users can register/login, submit jobs, attach up to 10 files.
6. Admin uses `admin.php` to manage jobs and change statuses (pending / in_progress / done).

## Notes
- Reviews use `ON DUPLICATE KEY UPDATE` so each job has at most one review.
- Status badges:
  - pending = red
  - in_progress = orange
  - done = green
- Floating Facebook bubble links to the provided profile and includes a pulse animation.
