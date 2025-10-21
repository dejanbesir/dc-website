<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Generates the static HTML output for a property.
 *
 * @throws RuntimeException
 */
function generate_property_page(int $propertyId, ?string $previousSlug = null): void
{
    $property = fetch_property($propertyId);
    if (!$property) {
        throw new RuntimeException('Property not found.');
    }

    $html = render_property_template($property);
    $slug = $property['slug'];

    // Ensure output directories exist.
    if (!is_dir(PROPERTY_OUTPUT_DIR)) {
        if (!mkdir(PROPERTY_OUTPUT_DIR, 0775, true) && !is_dir(PROPERTY_OUTPUT_DIR)) {
            throw new RuntimeException('Unable to create properties output directory.');
        }
    }

    $directoryPath = PROPERTY_OUTPUT_DIR . DIRECTORY_SEPARATOR . $slug;
    if (!is_dir($directoryPath)) {
        if (!mkdir($directoryPath, 0775, true) && !is_dir($directoryPath)) {
            throw new RuntimeException('Unable to create property directory: ' . $directoryPath);
        }
    }

    $indexPath = $directoryPath . DIRECTORY_SEPARATOR . 'index.html';
    file_put_contents($indexPath, $html);

    // Maintain legacy .html file at /properties/{slug}.html for backwards compatibility.
    $legacyPath = PROPERTY_OUTPUT_DIR . DIRECTORY_SEPARATOR . $slug . '.html';
    file_put_contents($legacyPath, $html);

    // Remove previously generated output if slug changed.
    if ($previousSlug !== null && $previousSlug !== $slug) {
        delete_property_output($previousSlug);
    }
}

/**
 * Deletes generated output for a property slug.
 */
function delete_property_output(string $slug): void
{
    $legacyPath = PROPERTY_OUTPUT_DIR . DIRECTORY_SEPARATOR . $slug . '.html';
    if (is_file($legacyPath)) {
        @unlink($legacyPath);
    }

    $directoryPath = PROPERTY_OUTPUT_DIR . DIRECTORY_SEPARATOR . $slug;
    if (is_dir($directoryPath)) {
        $indexPath = $directoryPath . DIRECTORY_SEPARATOR . 'index.html';
        if (is_file($indexPath)) {
            @unlink($indexPath);
        }

        // Remove directory if empty.
        $files = scandir($directoryPath);
        if (is_array($files) && count($files) <= 2) {
            @rmdir($directoryPath);
        }
    }
}

/**
 * Regenerates all property pages.
 *
 * @return array<int, array{id:int, slug:string, status:string, error?:string}>
 */
function regenerate_all_properties(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query('SELECT id, slug FROM properties ORDER BY id ASC');
    $results = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = ['id' => (int) $row['id'], 'slug' => (string) $row['slug'], 'status' => 'ok'];
        try {
            generate_property_page((int) $row['id']);
        } catch (Throwable $exception) {
            $status['status'] = 'error';
            $status['error'] = $exception->getMessage();
        }
        $results[] = $status;
    }

    return $results;
}

/**
 * Renders the property HTML template to a string.
 *
 * @param array<string, mixed> $property
 */
function render_property_template(array $property): string
{
    $metaTitle = trim((string) ($property['page_title'] ?? ''));
    if ($metaTitle === '') {
        $metaTitle = sprintf('%s · Dubrovnik Coast', $property['name']);
    }

    $metaDescription = trim((string) ($property['meta_description'] ?? ''));
    if ($metaDescription === '') {
        $metaDescription = truncate_for_meta($property['summary'] ?? $property['description'] ?? '');
    }

    $canonical = trim((string) ($property['canonical_url'] ?? ''));
    if ($canonical === '') {
        $canonical = SITE_BASE_URL . '/properties/' . $property['slug'] . '/';
    }

    $robots = trim((string) ($property['robots_directives'] ?? 'index,follow'));
    $ogTitle = trim((string) ($property['og_title'] ?? ''));
    if ($ogTitle === '') {
        $ogTitle = $metaTitle;
    }

    $ogDescription = trim((string) ($property['og_description'] ?? ''));
    if ($ogDescription === '') {
        $ogDescription = $metaDescription;
    }

    $heroImage = $property['hero_image'] ?? '';
    $ogImage = $property['og_image'] ?? '';
    if ($ogImage === '' && $heroImage !== '') {
        $ogImage = $heroImage;
    }
    $ogImageAbsolute = $ogImage ? absolute_url($ogImage) : '';

    $twitterCard = trim((string) ($property['twitter_card'] ?? 'summary_large_image'));
    if ($twitterCard === '') {
        $twitterCard = 'summary_large_image';
    }

    $galleryImages = [];
    if ($heroImage !== '') {
        $galleryImages[] = [
            'path'    => $heroImage,
            'alt'     => $property['hero_alt'] ?? '',
            'caption' => $property['hero_caption'] ?? '',
        ];
    }
    foreach ($property['gallery'] ?? [] as $item) {
        $galleryImages[] = [
            'path'    => $item['image_path'],
            'alt'     => $item['alt_text'] ?? '',
            'caption' => $item['caption'] ?? '',
        ];
    }

    $schemaJson = build_property_schema($property, $galleryImages, $metaDescription);

    $quickFacts = $property['quick_facts'] ?? [];
    $amenities = $property['amenities'] ?? [];
    $seasons = $property['seasons'] ?? [];
    $floorplanNotes = format_list($property['floorplan_notes'] ?? '');
    $descriptionParagraphs = format_paragraphs($property['description'] ?? '');

    $templatePath = __DIR__ . '/../templates/property.php';
    if (!is_file($templatePath)) {
        throw new RuntimeException('Property template not found.');
    }

    ob_start();
    include $templatePath;

    return (string) ob_get_clean();
}

/**
 * Builds JSON-LD payload for the property.
 *
 * @param array<int, array{path:string, alt:string, caption:string}> $galleryImages
 */
function build_property_schema(array $property, array $galleryImages, string $fallbackDescription): string
{
    $custom = [];
    $rawSchema = $property['schema_json'] ?? '';
    if (is_string($rawSchema) && trim($rawSchema) !== '') {
        $decoded = json_decode($rawSchema, true);
        if (is_array($decoded)) {
            $custom = $decoded;
        }
    }

    $images = [];
    foreach ($galleryImages as $image) {
        if (!empty($image['path'])) {
            $images[] = absolute_url($image['path']);
        }
    }

    $defaults = [
        '@context' => 'https://schema.org',
        '@type'    => 'LodgingBusiness',
        'name'     => $property['name'],
        'description' => $fallbackDescription,
        'image'    => $images,
        'telephone' => $property['contact_phone'] ?? '',
        'priceRange' => $property['base_rate'] ? sprintf('EUR %s+ per night', number_format((float) $property['base_rate'], 0)) : null,
        'address'  => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $property['address_line'] ?? '',
            'addressLocality' => $property['city'] ?? '',
            'addressRegion'   => $property['region'] ?? '',
            'postalCode'      => $property['postal_code'] ?? '',
            'addressCountry'  => $property['country'] ?? '',
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $property['latitude'] ?? '',
            'longitude' => $property['longitude'] ?? '',
        ],
        'url' => SITE_BASE_URL . '/properties/' . $property['slug'] . '/',
    ];

    $schema = array_replace_recursive($defaults, $custom);

    return json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Generates brief summary text for meta descriptions.
 */
function truncate_for_meta(string $text, int $length = 155): string
{
    $clean = trim(strip_tags($text));
    if ($clean === '') {
        return '';
    }

    if (mb_strlen($clean) <= $length) {
        return $clean;
    }

    $truncated = mb_substr($clean, 0, $length - 1);
    return rtrim($truncated, " \t\n\r\0\x0B.,;:") . '…';
}

/**
 * Converts multi-line text into an array of paragraphs.
 *
 * @return array<int, string>
 */
function format_paragraphs(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $parts = array_map('trim', explode("\n\n", $text));
    $paragraphs = [];
    foreach ($parts as $part) {
        if ($part !== '') {
            $paragraphs[] = $part;
        }
    }

    return $paragraphs;
}

/**
 * Converts multi-line text into bullet items.
 *
 * @return array<int, string>
 */
function format_list(string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $lines = array_map('trim', explode("\n", $text));
    $items = [];
    foreach ($lines as $line) {
        if ($line !== '') {
            $items[] = $line;
        }
    }

    return $items;
}

/**
 * Returns an absolute URL for a given path.
 */
function absolute_url(string $path): string
{
    if ($path === '') {
        return '';
    }
    if (preg_match('/^https?:\\/\\//i', $path)) {
        return $path;
    }

    return rtrim(SITE_BASE_URL, '/') . '/' . ltrim($path, '/');
}
