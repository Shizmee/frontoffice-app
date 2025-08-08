DROP TABLE IF EXISTS buggy_log;
CREATE TABLE IF NOT EXISTS buggy_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    time TIME NOT NULL,
    call_from VARCHAR(100) NOT NULL,
    caller_name VARCHAR(100) NOT NULL,
    guest_request TEXT NOT NULL,
    concern_department VARCHAR(100) NOT NULL,
    comments TEXT,
    status_followup VARCHAR(100),
    time_done TIME,
    fup_with_guest VARCHAR(100),
    fup_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;