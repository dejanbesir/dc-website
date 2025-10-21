<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$property = [
    'id'                 => null,
    'name'               => '',
    'slug'               => '',
    'category'           => 'villa',
    'headline'           => '',
    'summary'            => '',
    'description'        => '',
    'base_rate'          => '',
    'contact_phone'      => '',
    'page_title'         => '',
    'meta_description'   => '',
    'canonical_url'      => '',
    'robots_directives'  => 'index,follow',
    'og_title'           => '',
    'og_description'     => '',
    'og_image'           => '',
    'twitter_card'       => 'summary_large_image',
    'address_line'       => '',
    'city'               => '',
    'region'             => '',
    'postal_code'        => '',
    'country'            => '',
    'latitude'           => '',
    'longitude'          => '',
    'map_embed'          => '',
    'floorplan_notes'    => '',
    'floorplan_image'    => '',
    'floorplan_alt'      => '',
    'floorplan_caption'  => '',
    'hero_image'         => '',
    'hero_alt'           => '',
    'hero_caption'       => '',
    'schema_json'        => '',
    'quick_facts'        => [],
    'amenities'          => [],
    'seasons'            => [],
    'gallery'            => [],
];

$isEdit = false;

// Provide a sensible default JSON-LD scaffold for new properties.
$defaultSchema = [
    '@context' => 'https://schema.org',
    '@type'    => 'LodgingBusiness',
    'name'     => 'Property name',
    'description' => 'Short property summary for search engines.',
    'image'    => [],
    'address'  => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => '',
        'addressLocality' => '',
        'addressRegion'   => '',
        'postalCode'      => '',
        'addressCountry'  => '',
    ],
    'geo' => [
        '@type'     => 'GeoCoordinates',
        'latitude'  => '',
        'longitude' => '',
    ],
];

$property['schema_json'] = json_encode($defaultSchema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

require __DIR__ . '/property-form.php';
