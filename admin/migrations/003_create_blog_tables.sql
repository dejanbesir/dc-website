-- Blog tables for Dubrovnik Coast admin property manager.

CREATE TABLE IF NOT EXISTS blog_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_tags (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  slug VARCHAR(190) NOT NULL UNIQUE,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(190) NOT NULL UNIQUE,
  title VARCHAR(255) NOT NULL,
  excerpt TEXT DEFAULT NULL,
  content LONGTEXT NOT NULL,
  featured_image VARCHAR(255) DEFAULT NULL,
  featured_alt VARCHAR(255) DEFAULT NULL,
  meta_title VARCHAR(190) DEFAULT NULL,
  meta_description TEXT DEFAULT NULL,
  canonical_url VARCHAR(255) DEFAULT NULL,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reading_time INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_categories (
  post_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, category_id),
  CONSTRAINT fk_blog_post_categories_post FOREIGN KEY (post_id)
    REFERENCES blog_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_blog_post_categories_category FOREIGN KEY (category_id)
    REFERENCES blog_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_post_tags (
  post_id INT UNSIGNED NOT NULL,
  tag_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (post_id, tag_id),
  CONSTRAINT fk_blog_post_tags_post FOREIGN KEY (post_id)
    REFERENCES blog_posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_blog_post_tags_tag FOREIGN KEY (tag_id)
    REFERENCES blog_tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_blog_posts_published_at ON blog_posts(published_at DESC);
CREATE INDEX idx_blog_posts_is_published ON blog_posts(is_published);
