CREATE TABLE IF NOT EXISTS guest_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_agency VARCHAR(100),
    arrival_date DATE,
    departure_date DATE,
    num_nights INT,
    interaction_time TIME,
    house_status VARCHAR(50),
    guest_comments TEXT,
    associate_name VARCHAR(100),
    incident TEXT,
    department VARCHAR(100),
    follow_up_by VARCHAR(100),
    recovery_action TEXT,
    guest_satisfaction_level VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);