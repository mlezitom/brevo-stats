-- Schema for the tables this tool reads/writes. Run this once on a fresh database.

CREATE TABLE IF NOT EXISTS brevo_weekly_stats (
    id INT(11) NOT NULL AUTO_INCREMENT,
    week_start DATE NOT NULL,
    week_end DATE NOT NULL,
    requests INT(11) DEFAULT 0,
    delivered INT(11) DEFAULT 0,
    opens INT(11) DEFAULT 0,
    clicks INT(11) DEFAULT 0,
    hard_bounces INT(11) DEFAULT 0,
    soft_bounces INT(11) DEFAULT 0,
    blocked INT(11) DEFAULT 0,
    spam_reports INT(11) DEFAULT 0,
    unsubscribes INT(11) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY uniq_week (week_start, week_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS brevo_monthly_stats (
    id INT(11) NOT NULL AUTO_INCREMENT,
    month_start DATE NOT NULL,
    month_end DATE NOT NULL,
    requests INT(11) DEFAULT 0,
    delivered INT(11) DEFAULT 0,
    opens INT(11) DEFAULT 0,
    clicks INT(11) DEFAULT 0,
    hard_bounces INT(11) DEFAULT 0,
    soft_bounces INT(11) DEFAULT 0,
    blocked INT(11) DEFAULT 0,
    spam_reports INT(11) DEFAULT 0,
    unsubscribes INT(11) DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT current_timestamp(),
    PRIMARY KEY (id),
    UNIQUE KEY uniq_month (month_start, month_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
