ALTER TABLE properties
    ADD COLUMN page_title VARCHAR(190) DEFAULT NULL AFTER contact_phone,
    ADD COLUMN meta_description TEXT DEFAULT NULL AFTER page_title,
    ADD COLUMN canonical_url VARCHAR(255) DEFAULT NULL AFTER meta_description,
    ADD COLUMN robots_directives VARCHAR(60) DEFAULT 'index,follow' AFTER canonical_url,
    ADD COLUMN og_title VARCHAR(190) DEFAULT NULL AFTER robots_directives,
    ADD COLUMN og_description TEXT DEFAULT NULL AFTER og_title,
    ADD COLUMN og_image VARCHAR(255) DEFAULT NULL AFTER og_description,
    ADD COLUMN twitter_card VARCHAR(32) DEFAULT 'summary_large_image' AFTER og_image,
    ADD COLUMN hero_alt VARCHAR(255) DEFAULT NULL AFTER hero_image,
    ADD COLUMN hero_caption TEXT DEFAULT NULL AFTER hero_alt,
    ADD COLUMN floorplan_alt VARCHAR(255) DEFAULT NULL AFTER floorplan_image,
    ADD COLUMN floorplan_caption TEXT DEFAULT NULL AFTER floorplan_alt,
    ADD COLUMN schema_json LONGTEXT DEFAULT NULL AFTER floorplan_caption;

ALTER TABLE property_gallery
    ADD COLUMN caption TEXT DEFAULT NULL AFTER alt_text;
