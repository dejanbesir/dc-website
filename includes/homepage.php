<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/db.php';

const FEATURED_MAX_SLIDES = 6;
const FEATURED_PROPERTIES_PER_SLIDE = 2;

/**
 * Fetches random villa and apartment data and returns grouped slides for the homepage carousel.
 *
 * @return array<int, array{category:string, properties:array<int, array<string, mixed>>}>
 */
function fetch_featured_property_slides(int $maxSlides = FEATURED_MAX_SLIDES): array
{
    try {
        $pdo = get_pdo();
    } catch (Throwable $exception) {
        return [];
    }

    $maxSlides = max(1, $maxSlides);
    $perSlide = FEATURED_PROPERTIES_PER_SLIDE;

    $villaLimit = $maxSlides * $perSlide;
    $apartmentLimit = max($perSlide, (int) ceil($maxSlides / 3) * $perSlide);

    $villas = load_properties_by_category($pdo, 'villa', $villaLimit);
    $apartments = load_properties_by_category($pdo, 'apartment', $apartmentLimit);

    $allPropertyIds = array_merge(
        array_column($villas, 'id'),
        array_column($apartments, 'id')
    );

    $quickFactMap = [];
    if ($allPropertyIds) {
        $quickFactMap = load_property_quick_facts($pdo, $allPropertyIds);
    }

    foreach ($villas as &$villa) {
        $villa = enrich_property_card($villa, $quickFactMap[$villa['id']] ?? []);
    }
    unset($villa);

    foreach ($apartments as &$apartment) {
        $apartment = enrich_property_card($apartment, $quickFactMap[$apartment['id']] ?? []);
    }
    unset($apartment);

    return build_featured_slides($villas, $apartments, $maxSlides, $perSlide);
}

/**
 * @return array<int, array<string, mixed>>
 */
function load_properties_by_category(PDO $pdo, string $category, int $limit): array
{
    $sql = <<<SQL
        SELECT
            id,
            slug,
            category,
            name,
            headline,
            summary,
            base_rate,
            hero_image,
            hero_alt
        FROM properties
        WHERE category = :category
          AND hero_image IS NOT NULL
          AND hero_image <> ''
        ORDER BY RAND()
        LIMIT :limit
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':category', $category, PDO::PARAM_STR);
    $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
    $stmt->execute();

    $properties = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $properties[] = [
            'id'         => (int) $row['id'],
            'slug'       => (string) $row['slug'],
            'category'   => (string) $row['category'],
            'name'       => (string) $row['name'],
            'headline'   => trim((string) ($row['headline'] ?? '')),
            'summary'    => trim((string) ($row['summary'] ?? '')),
            'base_rate'  => isset($row['base_rate']) ? (float) $row['base_rate'] : null,
            'hero_image' => trim((string) ($row['hero_image'] ?? '')),
            'hero_alt'   => trim((string) ($row['hero_alt'] ?? '')),
        ];
    }

    return $properties;
}

/**
 * @param array<int, int> $propertyIds
 * @return array<int, array<int, array{label:string, value:string}>>
 */
function load_property_quick_facts(PDO $pdo, array $propertyIds): array
{
    if (!$propertyIds) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
    $sql = <<<SQL
        SELECT property_id, label, value
        FROM property_quick_facts
        WHERE property_id IN ($placeholders)
        ORDER BY property_id, sort_order ASC, id ASC
    SQL;

    $stmt = $pdo->prepare($sql);
    foreach ($propertyIds as $index => $id) {
        $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
    }
    $stmt->execute();

    $facts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $propertyId = (int) $row['property_id'];
        $facts[$propertyId][] = [
            'label' => trim((string) ($row['label'] ?? '')),
            'value' => trim((string) ($row['value'] ?? '')),
        ];
    }

    return $facts;
}

/**
 * @param array<int, array{label:string, value:string}> $quickFacts
 * @return array<string, mixed>
 */
function enrich_property_card(array $property, array $quickFacts): array
{
    $highlights = build_highlights($property, $quickFacts);
    $priceLabel = null;

    if (isset($property['base_rate']) && $property['base_rate'] !== null) {
        $priceLabel = sprintf(
            'From &euro;%s/night',
            number_format((float) $property['base_rate'], 0)
        );
    }

    return array_merge($property, [
        'highlights' => $highlights,
        'price_label' => $priceLabel,
        'url' => '/properties/' . $property['slug'] . '/',
    ]);
}

/**
 * @param array<int, array{label:string, value:string}> $quickFacts
 * @return array<int, string>
 */
function build_highlights(array $property, array $quickFacts): array
{
    $highlights = [];

    foreach ($quickFacts as $fact) {
        $value = $fact['value'];
        $label = $fact['label'];

        if ($value === '' && $label === '') {
            continue;
        }

        if ($value !== '' && $label !== '') {
            $highlights[] = sprintf('%s %s', $value, $label);
        } elseif ($value !== '') {
            $highlights[] = $value;
        } else {
            $highlights[] = $label;
        }

        if (count($highlights) >= 3) {
            break;
        }
    }

    if ($highlights) {
        return $highlights;
    }

    $headline = $property['headline'] ?? '';
    if ($headline !== '') {
        return [$headline];
    }

    $summary = $property['summary'] ?? '';
    if ($summary !== '') {
        $substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

        $trimmed = $substr($summary, 0, 120);
        if ($strlen($summary) > 120) {
            $trimmed = rtrim($trimmed, " \t\n\r\0\x0B.,;:") . '...';
        }
        return [$trimmed];
    }

    return [];
}

/**
 * @param array<int, array<string, mixed>> $villas
 * @param array<int, array<string, mixed>> $apartments
 * @return array<int, array{category:string, properties:array<int, array<string, mixed>>}>
 */
function build_featured_slides(array $villas, array $apartments, int $maxSlides, int $perSlide): array
{
    $villaGroups = array_chunk($villas, $perSlide);
    $apartmentGroups = array_chunk($apartments, $perSlide);

    $slides = [];
    $pattern = ['villa', 'villa', 'apartment'];
    $patternIndex = 0;

    while (count($slides) < $maxSlides && (!empty($villaGroups) || !empty($apartmentGroups))) {
        $desiredCategory = $pattern[$patternIndex % count($pattern)];
        $patternIndex++;

        if ($desiredCategory === 'villa' && !empty($villaGroups)) {
            $slides[] = [
                'category' => 'villa',
                'properties' => array_shift($villaGroups),
            ];
            continue;
        }

        if ($desiredCategory === 'apartment' && !empty($apartmentGroups)) {
            $slides[] = [
                'category' => 'apartment',
                'properties' => array_shift($apartmentGroups),
            ];
            continue;
        }

        if (!empty($villaGroups)) {
            $slides[] = [
                'category' => 'villa',
                'properties' => array_shift($villaGroups),
            ];
            continue;
        }

        if (!empty($apartmentGroups)) {
            $slides[] = [
                'category' => 'apartment',
                'properties' => array_shift($apartmentGroups),
            ];
        }
    }

    return $slides;
}
