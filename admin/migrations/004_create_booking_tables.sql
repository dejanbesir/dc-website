-- Booking and calendar tables for Dubrovnik Coast.

CREATE TABLE IF NOT EXISTS bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  reference VARCHAR(24) NOT NULL UNIQUE,
  status ENUM('awaiting_email','pending_payment','payment_processing','confirmed','cancelled','expired') NOT NULL DEFAULT 'awaiting_email',
  arrival_date DATE NOT NULL,
  departure_date DATE NOT NULL,
  nights SMALLINT UNSIGNED NOT NULL,
  adults SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  children SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  infants SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  email VARCHAR(190) NOT NULL,
  email_verified_at DATETIME DEFAULT NULL,
  email_token CHAR(64) DEFAULT NULL,
  stripe_session_id VARCHAR(255) DEFAULT NULL,
  stripe_payment_intent VARCHAR(255) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bookings_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_contacts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  full_name VARCHAR(190) NOT NULL,
  address_line VARCHAR(255) NOT NULL,
  city VARCHAR(120) NOT NULL,
  region VARCHAR(120) DEFAULT NULL,
  postal_code VARCHAR(30) NOT NULL,
  country VARCHAR(120) NOT NULL,
  phone VARCHAR(60) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_contacts_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_travellers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  traveller_type ENUM('adult','child','infant') NOT NULL DEFAULT 'adult',
  age SMALLINT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_travellers_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS booking_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  actor_type ENUM('system','guest','admin','webhook') NOT NULL DEFAULT 'system',
  actor_identifier VARCHAR(120) DEFAULT NULL,
  event_type VARCHAR(60) NOT NULL,
  details JSON DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_booking_events_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calendar_blocks (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED DEFAULT NULL,
  source ENUM('internal_booking','manual_block','external_ics','pending') NOT NULL DEFAULT 'pending',
  external_uid VARCHAR(190) DEFAULT NULL,
  title VARCHAR(190) DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_calendar_blocks_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE,
  CONSTRAINT fk_calendar_blocks_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE SET NULL,
  INDEX idx_calendar_blocks_range (property_id, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stripe_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  session_id VARCHAR(255) NOT NULL,
  payment_intent VARCHAR(255) DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'EUR',
  status ENUM('requires_payment','requires_action','succeeded','cancelled','refunded','failed') NOT NULL DEFAULT 'requires_payment',
  payload JSON DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_stripe_payments_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(id) ON DELETE CASCADE,
  INDEX idx_stripe_payments_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_calendars (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  export_token CHAR(40) NOT NULL UNIQUE,
  airbnb_feed_url VARCHAR(255) DEFAULT NULL,
  booking_feed_url VARCHAR(255) DEFAULT NULL,
  custom_feed_url VARCHAR(255) DEFAULT NULL,
  last_sync_at DATETIME DEFAULT NULL,
  last_export_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_property_calendars_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_bookings_property_dates ON bookings(property_id, arrival_date, departure_date);
CREATE INDEX idx_bookings_status ON bookings(status);
