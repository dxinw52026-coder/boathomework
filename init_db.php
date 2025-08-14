<?php
require_once 'db.php';

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  surname VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS jobs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  service_type ENUM('research','presentation','writing','system_analysis','web_dev') NOT NULL,
  details TEXT NOT NULL,
  due_at DATETIME NOT NULL,
  price DECIMAL(10,2) NULL,
  facebook_link VARCHAR(255) NULL,
  facebook_name VARCHAR(190) NULL,
  line_id VARCHAR(100) NULL,
  status ENUM('pending','in_progress','done') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_jobs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS job_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_files_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS job_result_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  stored_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_results_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL UNIQUE,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_job FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;

$pdo->exec($sql);

// Try upgrade if existing
try {
  $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS facebook_link VARCHAR(255) NULL");
  $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS facebook_name VARCHAR(190) NULL");
  $pdo->exec("ALTER TABLE jobs ADD COLUMN IF NOT EXISTS line_id VARCHAR(100) NULL");
} catch (Exception $e) {
  try {
    $db = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $cols = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='{$db}' AND TABLE_NAME='jobs'")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('facebook_link', $cols)) { $pdo->exec("ALTER TABLE jobs ADD COLUMN facebook_link VARCHAR(255) NULL"); }
    if (!in_array('facebook_name', $cols)) { $pdo->exec("ALTER TABLE jobs ADD COLUMN facebook_name VARCHAR(190) NULL"); }
    if (!in_array('line_id', $cols)) { $pdo->exec("ALTER TABLE jobs ADD COLUMN line_id VARCHAR(100) NULL"); }
  } catch (Exception $e2) {}
}

// seed admin
if ((int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() === 0) {
  $hash = password_hash('admin123', PASSWORD_BCRYPT);
  $pdo->prepare("INSERT INTO users(name,surname,email,password_hash,role) VALUES(?,?,?,?,?)")
      ->execute(['Admin','User','admin@example.com',$hash,'admin']);
}

echo "<h2>Database initialized / upgraded ✅</h2>";
echo "<p>Admin login: admin@example.com / admin123</p>";
echo '<p><a href=\"index.php\">Go to site</a></p>';
