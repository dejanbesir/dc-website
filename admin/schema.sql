-- Database schema for Dubrovnik Coast admin property manager.

CREATE TABLE IF NOT EXISTS properties (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  category ENUM('villa','apartment','other') NOT NULL DEFAULT 'villa',
  name VARCHAR(190) NOT NULL,
  headline VARCHAR(255) DEFAULT NULL,
  summary TEXT DEFAULT NULL,
  description LONGTEXT DEFAULT NULL,
  base_rate DECIMAL(10,2) DEFAULT NULL,
  contact_phone VARCHAR(50) DEFAULT NULL,
  page_title VARCHAR(190) DEFAULT NULL,
  meta_description TEXT DEFAULT NULL,
  canonical_url VARCHAR(255) DEFAULT NULL,
  robots_directives VARCHAR(60) DEFAULT 'index,follow',
  og_title VARCHAR(190) DEFAULT NULL,
  og_description TEXT DEFAULT NULL,
  og_image VARCHAR(255) DEFAULT NULL,
  twitter_card VARCHAR(32) DEFAULT 'summary_large_image',
  address_line VARCHAR(255) DEFAULT NULL,
  city VARCHAR(120) DEFAULT NULL,
  region VARCHAR(120) DEFAULT NULL,
  postal_code VARCHAR(30) DEFAULT NULL,
  country VARCHAR(120) DEFAULT NULL,
  latitude DECIMAL(10,6) DEFAULT NULL,
  longitude DECIMAL(10,6) DEFAULT NULL,
  map_embed TEXT DEFAULT NULL,
  floorplan_notes TEXT DEFAULT NULL,
  hero_image VARCHAR(255) DEFAULT NULL,
  hero_alt VARCHAR(255) DEFAULT NULL,
  hero_caption TEXT DEFAULT NULL,
  floorplan_image VARCHAR(255) DEFAULT NULL,
  floorplan_alt VARCHAR(255) DEFAULT NULL,
  floorplan_caption TEXT DEFAULT NULL,
  schema_json LONGTEXT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_quick_facts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  value VARCHAR(120) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_quick_facts_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_amenities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  label VARCHAR(160) NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_amenities_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_seasons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  label VARCHAR(120) NOT NULL,
  date_range VARCHAR(160) DEFAULT NULL,
  nightly_rate DECIMAL(10,2) DEFAULT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_seasons_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS property_gallery (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  property_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  alt_text VARCHAR(255) DEFAULT NULL,
  caption TEXT DEFAULT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  CONSTRAINT fk_gallery_property FOREIGN KEY (property_id)
    REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_quick_facts_property ON property_quick_facts(property_id);
CREATE INDEX idx_amenities_property ON property_amenities(property_id);
CREATE INDEX idx_seasons_property ON property_seasons(property_id);
CREATE INDEX idx_gallery_property ON property_gallery(property_id);
