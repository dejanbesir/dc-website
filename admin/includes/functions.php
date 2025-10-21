<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Creates a URL-friendly slug based on the property name.
 */
function slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = strtolower((string) $value);
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'property-' . uniqid();
}

/**
 * Ensures a slug is unique by appending numeric suffixes where needed.
 */
function ensure_unique_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $suffix = 1;

    $query = 'SELECT id FROM properties WHERE slug = :slug';
    if ($ignoreId !== null) {
        $query .= ' AND id != :ignore';
    }

    $stmt = $pdo->prepare($query);

    while (true) {
        $params = [':slug' => $slug];
        if ($ignoreId !== null) {
            $params[':ignore'] = $ignoreId;
        }
        $stmt->execute($params);

        if ($stmt->fetchColumn() === false) {
            return $slug;
        }

        $slug = sprintf('%s-%d', $base, $suffix);
        $suffix++;
    }
}

/**
 * Moves an uploaded file into the property image directory, returning the public path.
 */
function store_property_upload(array $file, string $slug, string $prefix): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $original = $file['name'] ?? 'upload';
    $extension = pathinfo($original, PATHINFO_EXTENSION);
    $safeExtension = $extension ? strtolower(preg_replace('/[^a-z0-9]/i', '', $extension)) : '';

    try {
        $uniqueToken = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $uniqueToken = substr(uniqid('', true), -8);
    }

    $filename = sprintf(
        '%s-%s-%s%s',
        $prefix,
        date('YmdHis'),
        $uniqueToken,
        $safeExtension ? '.' . $safeExtension : ''
    );

    $targetDir = PUBLIC_IMG_DIR . DIRECTORY_SEPARATOR . $slug;
    if (!is_dir(PUBLIC_IMG_DIR) && !mkdir(PUBLIC_IMG_DIR, 0775, true) && !is_dir(PUBLIC_IMG_DIR)) {
        throw new RuntimeException('Unable to access base image directory: ' . PUBLIC_IMG_DIR);
    }
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to create property directory: ' . $targetDir);
    }

    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('Failed to move uploaded file.');
    }

    // Ensure the file is world-readable.
    chmod($targetPath, 0644);

    return PUBLIC_IMG_PREFIX . '/' . $slug . '/' . $filename;
}

/**
 * Stores multiple gallery images and returns an array of saved paths.
 *
 * @return array<int, string>
 */
function store_gallery_uploads(array $files, string $slug): array
{
    $stored = [];

    $count = is_array($files['name']) ? count($files['name']) : 0;
    for ($index = 0; $index < $count; $index++) {
        $file = [
            'name'     => $files['name'][$index] ?? null,
            'type'     => $files['type'][$index] ?? null,
            'tmp_name' => $files['tmp_name'][$index] ?? null,
            'error'    => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size'     => $files['size'][$index] ?? 0,
        ];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            continue;
        }

        $path = store_property_upload($file, $slug, 'gallery');
        if ($path !== null) {
            $stored[] = $path;
        }
    }

    return $stored;
}

/**
 * Normalises an array pair (labels/values) into structured rows.
 *
 * @return array<int, array{label: string, value: string}>
 */
function normalise_label_value_pairs(array $labels, array $values): array
{
    $rows = [];
    $total = max(count($labels), count($values));
    for ($i = 0; $i < $total; $i++) {
        $label = trim($labels[$i] ?? '');
        $value = trim($values[$i] ?? '');
        if ($label === '' && $value === '') {
            continue;
        }

        $rows[] = [
            'label' => $label,
            'value' => $value,
        ];
    }

    return $rows;
}

/**
 * Converts rate string to decimal.
 */
function normalise_rate(string $value): ?float
{
    $sanitised = preg_replace('/[^0-9.,]/', '', $value) ?? '';
    $sanitised = str_replace(',', '.', $sanitised);

    if ($sanitised === '') {
        return null;
    }

    return (float) $sanitised;
}

/**
 * Fetches all properties grouped by category.
 *
 * @return array<string, array<int, array<string, mixed>>>
 */
function fetch_properties_grouped(): array
{
    $pdo = get_pdo();
    $stmt = $pdo->query(
        'SELECT p.*, 
            (SELECT COUNT(*) FROM property_gallery WHERE property_id = p.id) AS gallery_count,
            (SELECT COUNT(*) FROM property_amenities WHERE property_id = p.id) AS amenity_count
         FROM properties p
         ORDER BY p.category, p.name'
    );

    $grouped = [
        'villa'     => [],
        'apartment' => [],
        'other'     => [],
    ];

    while ($row = $stmt->fetch()) {
        $category = $row['category'] ?? 'other';
        if (!isset($grouped[$category])) {
            $grouped[$category] = [];
        }
        $grouped[$category][] = $row;
    }

    return $grouped;
}

/**
 * Fetches a single property by ID.
 */
function fetch_property(int $id): ?array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT * FROM properties WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $property = $stmt->fetch();

    if (!$property) {
        return null;
    }

    $property['quick_facts'] = fetch_child_rows('property_quick_facts', $id);
    $property['amenities'] = fetch_child_rows('property_amenities', $id);
    $property['seasons'] = fetch_child_rows('property_seasons', $id, 'sort_order ASC');
    $property['gallery'] = fetch_child_rows('property_gallery', $id, 'sort_order ASC');

    return $property;
}

/**
 * Helper to fetch child rows from a lookup table.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_child_rows(string $table, int $propertyId, string $orderBy = 'id ASC'): array
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare(sprintf('SELECT * FROM %s WHERE property_id = :id ORDER BY %s', $table, $orderBy));
    $stmt->execute([':id' => $propertyId]);

    return $stmt->fetchAll() ?: [];
}
