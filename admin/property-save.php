<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/generator.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash('error', 'Invalid request method.');
    header('Location: /admin/property-create.php');
    exit;
}

$pdo = get_pdo();

$propertyId = isset($_POST['property_id']) ? (int) $_POST['property_id'] : 0;
$isEdit = $propertyId > 0;

$existing = null;
$previousSlug = null;
if ($isEdit) {
    $existing = fetch_property($propertyId);
    if (!$existing) {
        set_flash('error', 'Property not found.');
        header('Location: /admin/properties.php');
        exit;
    }
    $previousSlug = $existing['slug'];
}

$name = trim((string) ($_POST['name'] ?? ''));
$slugInput = trim((string) ($_POST['slug'] ?? ''));
$category = $_POST['category'] ?? 'villa';
$headline = trim((string) ($_POST['headline'] ?? ''));
$summary = trim((string) ($_POST['summary'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$baseRate = normalise_rate((string) ($_POST['base_rate'] ?? ''));
$contactPhone = trim((string) ($_POST['contact_phone'] ?? ''));
$pageTitle = trim((string) ($_POST['page_title'] ?? ''));
$metaDescription = trim((string) ($_POST['meta_description'] ?? ''));
$canonicalUrl = trim((string) ($_POST['canonical_url'] ?? ''));
$robotsChoice = trim((string) ($_POST['robots_directives'] ?? 'index,follow'));
$robotsCustom = trim((string) ($_POST['robots_custom'] ?? ''));
$twitterCard = trim((string) ($_POST['twitter_card'] ?? 'summary_large_image'));
$ogTitle = trim((string) ($_POST['og_title'] ?? ''));
$ogDescription = trim((string) ($_POST['og_description'] ?? ''));
$addressLine = trim((string) ($_POST['address_line'] ?? ''));
$city = trim((string) ($_POST['city'] ?? ''));
$region = trim((string) ($_POST['region'] ?? ''));
$postalCode = trim((string) ($_POST['postal_code'] ?? ''));
$country = trim((string) ($_POST['country'] ?? ''));
$latitude = trim((string) ($_POST['latitude'] ?? ''));
$longitude = trim((string) ($_POST['longitude'] ?? ''));
$mapEmbed = trim((string) ($_POST['map_embed'] ?? ''));
$floorplanNotes = trim((string) ($_POST['floorplan_notes'] ?? ''));
$floorplanAlt = trim((string) ($_POST['floorplan_alt'] ?? ''));
$floorplanCaption = trim((string) ($_POST['floorplan_caption'] ?? ''));
$heroAlt = trim((string) ($_POST['hero_alt'] ?? ''));
$heroCaption = trim((string) ($_POST['hero_caption'] ?? ''));
$schemaJson = $_POST['schema_json'] ?? '';
$schemaJson = is_string($schemaJson) ? trim($schemaJson) : '';

if ($name === '') {
    set_flash('error', 'Property name is required.');
    header('Location: ' . ($isEdit ? "/admin/property-edit.php?id={$propertyId}" : '/admin/property-create.php'));
    exit;
}

if (!in_array($category, ['villa', 'apartment', 'other'], true)) {
    $category = 'other';
}

$slugBase = $slugInput !== '' ? slugify($slugInput) : slugify($name);
$slug = ensure_unique_slug($pdo, $slugBase, $isEdit ? $propertyId : null);

$robots = $robotsChoice === 'custom' ? ($robotsCustom !== '' ? $robotsCustom : 'index,follow') : $robotsChoice;

$latitudeValue = $latitude !== '' ? (float) $latitude : null;
$longitudeValue = $longitude !== '' ? (float) $longitude : null;

$heroPath = $existing['hero_image'] ?? null;
$floorplanPath = $existing['floorplan_image'] ?? null;
$ogImagePath = $existing['og_image'] ?? null;

$galleryExistingInput = $_POST['gallery_existing'] ?? [];
$galleryExistingInput = is_array($galleryExistingInput) ? $galleryExistingInput : [];

// Handle slug change for image directories and stored paths.
if ($isEdit && $previousSlug !== $slug) {
    $oldDir = PUBLIC_IMG_DIR . DIRECTORY_SEPARATOR . $previousSlug;
    $newDir = PUBLIC_IMG_DIR . DIRECTORY_SEPARATOR . $slug;

    if (is_dir($oldDir)) {
        if (!is_dir($newDir)) {
            @rename($oldDir, $newDir);
        } else {
            $files = scandir($oldDir) ?: [];
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                @rename($oldDir . DIRECTORY_SEPARATOR . $file, $newDir . DIRECTORY_SEPARATOR . $file);
            }
            @rmdir($oldDir);
        }
    }

    $rewritePath = static function (?string $path) use ($previousSlug, $slug): ?string {
        if (!$path) {
            return $path;
        }
        return str_replace('/' . $previousSlug . '/', '/' . $slug . '/', $path);
    };

    $heroPath = $rewritePath($heroPath);
    $floorplanPath = $rewritePath($floorplanPath);
    $ogImagePath = $rewritePath($ogImagePath);

    foreach ($galleryExistingInput as $id => &$data) {
        if (isset($data['path'])) {
            $data['path'] = $rewritePath((string) $data['path']);
        }
    }
    unset($data);
}

// Handle hero image removal / upload.
if (!empty($_POST['hero_remove'])) {
    if ($heroPath) {
        @unlink(PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $heroPath));
    }
    $heroPath = null;
}

if (!empty($_FILES['hero_image']) && ($_FILES['hero_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $heroPath = store_property_upload($_FILES['hero_image'], $slug, 'hero');
}

// Floorplan.
if (!empty($_POST['floorplan_remove'])) {
    if ($floorplanPath) {
        @unlink(PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $floorplanPath));
    }
    $floorplanPath = null;
}

if (!empty($_FILES['floorplan_image']) && ($_FILES['floorplan_image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $floorplanPath = store_property_upload($_FILES['floorplan_image'], $slug, 'floorplan');
}

// Open Graph image.
if (!empty($_POST['og_image_remove'])) {
    if ($ogImagePath) {
        @unlink(PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $ogImagePath));
    }
    $ogImagePath = null;
}

if (!empty($_FILES['og_image_upload']) && ($_FILES['og_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $ogImagePath = store_property_upload($_FILES['og_image_upload'], $slug, 'og');
}

// Gallery uploads.
$newGalleryPaths = [];
if (!empty($_FILES['gallery_images'])) {
    $newGalleryPaths = store_gallery_uploads($_FILES['gallery_images'], $slug);
}

$pdo->beginTransaction();

try {
    $propertyParams = [
        ':slug'             => $slug,
        ':category'         => $category,
        ':name'             => $name,
        ':headline'         => $headline,
        ':summary'          => $summary,
        ':description'      => $description,
        ':base_rate'        => $baseRate,
        ':contact_phone'    => $contactPhone,
        ':page_title'       => $pageTitle,
        ':meta_description' => $metaDescription,
        ':canonical_url'    => $canonicalUrl,
        ':robots'           => $robots,
        ':og_title'         => $ogTitle,
        ':og_description'   => $ogDescription,
        ':og_image'         => $ogImagePath,
        ':twitter_card'     => $twitterCard,
        ':address_line'     => $addressLine,
        ':city'             => $city,
        ':region'           => $region,
        ':postal_code'      => $postalCode,
        ':country'          => $country,
        ':latitude'         => $latitudeValue,
        ':longitude'        => $longitudeValue,
        ':map_embed'        => $mapEmbed,
        ':floorplan_notes'  => $floorplanNotes,
        ':floorplan_image'  => $floorplanPath,
        ':floorplan_alt'    => $floorplanAlt,
        ':floorplan_caption'=> $floorplanCaption,
        ':hero_image'       => $heroPath,
        ':hero_alt'         => $heroAlt,
        ':hero_caption'     => $heroCaption,
        ':schema_json'      => $schemaJson !== '' ? $schemaJson : null,
    ];

    if ($isEdit) {
        $propertyParams[':id'] = $propertyId;
        $stmt = $pdo->prepare(
            'UPDATE properties SET
                slug = :slug,
                category = :category,
                name = :name,
                headline = :headline,
                summary = :summary,
                description = :description,
                base_rate = :base_rate,
                contact_phone = :contact_phone,
                page_title = :page_title,
                meta_description = :meta_description,
                canonical_url = :canonical_url,
                robots_directives = :robots,
                og_title = :og_title,
                og_description = :og_description,
                og_image = :og_image,
                twitter_card = :twitter_card,
                address_line = :address_line,
                city = :city,
                region = :region,
                postal_code = :postal_code,
                country = :country,
                latitude = :latitude,
                longitude = :longitude,
                map_embed = :map_embed,
                floorplan_notes = :floorplan_notes,
                floorplan_image = :floorplan_image,
                floorplan_alt = :floorplan_alt,
                floorplan_caption = :floorplan_caption,
                hero_image = :hero_image,
                hero_alt = :hero_alt,
                hero_caption = :hero_caption,
                schema_json = :schema_json,
                updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute($propertyParams);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO properties (
                slug, category, name, headline, summary, description, base_rate, contact_phone,
                page_title, meta_description, canonical_url, robots_directives, og_title, og_description, og_image, twitter_card,
                address_line, city, region, postal_code, country, latitude, longitude, map_embed,
                floorplan_notes, floorplan_image, floorplan_alt, floorplan_caption,
                hero_image, hero_alt, hero_caption, schema_json, created_at, updated_at
            ) VALUES (
                :slug, :category, :name, :headline, :summary, :description, :base_rate, :contact_phone,
                :page_title, :meta_description, :canonical_url, :robots, :og_title, :og_description, :og_image, :twitter_card,
                :address_line, :city, :region, :postal_code, :country, :latitude, :longitude, :map_embed,
                :floorplan_notes, :floorplan_image, :floorplan_alt, :floorplan_caption,
                :hero_image, :hero_alt, :hero_caption, :schema_json, NOW(), NOW()
            )'
        );
        $stmt->execute($propertyParams);
        $propertyId = (int) $pdo->lastInsertId();
    }

    // Quick facts processing.
    $factIds = $_POST['fact_id'] ?? [];
    $factLabels = $_POST['fact_label'] ?? [];
    $factValues = $_POST['fact_value'] ?? [];

    $factsToInsert = [];
    $factsToUpdate = [];
    $factsToDelete = [];

    $factCount = max(count($factLabels), count($factValues));
    for ($i = 0; $i < $factCount; $i++) {
        $id = isset($factIds[$i]) ? (int) $factIds[$i] : 0;
        $label = trim((string) ($factLabels[$i] ?? ''));
        $value = trim((string) ($factValues[$i] ?? ''));

        if ($label === '' && $value === '') {
            if ($id > 0) {
                $factsToDelete[] = $id;
            }
            continue;
        }

        $payload = [
            'id'    => $id,
            'label' => $label,
            'value' => $value,
            'order' => count($factsToInsert) + count($factsToUpdate),
        ];

        if ($id > 0) {
            $factsToUpdate[] = $payload;
        } else {
            $factsToInsert[] = $payload;
        }
    }

    if (!empty($factsToDelete)) {
        $in = implode(',', array_fill(0, count($factsToDelete), '?'));
        $pdo->prepare("DELETE FROM property_quick_facts WHERE property_id = ? AND id IN ($in)")
            ->execute(array_merge([$propertyId], $factsToDelete));
    }

    if (!empty($factsToUpdate)) {
        $stmtFactUpdate = $pdo->prepare(
            'UPDATE property_quick_facts SET label = :label, value = :value, sort_order = :sort WHERE id = :id AND property_id = :property_id'
        );
        foreach ($factsToUpdate as $fact) {
            $stmtFactUpdate->execute([
                ':label'        => $fact['label'],
                ':value'        => $fact['value'],
                ':sort'         => $fact['order'],
                ':id'           => $fact['id'],
                ':property_id'  => $propertyId,
            ]);
        }
    }

    if (!empty($factsToInsert)) {
        $stmtFactInsert = $pdo->prepare(
            'INSERT INTO property_quick_facts (property_id, label, value, sort_order) VALUES (:property_id, :label, :value, :sort_order)'
        );
        foreach ($factsToInsert as $fact) {
            $stmtFactInsert->execute([
                ':property_id' => $propertyId,
                ':label'       => $fact['label'],
                ':value'       => $fact['value'],
                ':sort_order'  => $fact['order'],
            ]);
        }
    }

    // Amenities.
    $amenityIds = $_POST['amenity_id'] ?? [];
    $amenityLabels = $_POST['amenity'] ?? [];

    $amenitiesToInsert = [];
    $amenitiesToUpdate = [];
    $amenitiesToDelete = [];

    $amenityCount = count($amenityLabels);
    for ($i = 0; $i < $amenityCount; $i++) {
        $id = isset($amenityIds[$i]) ? (int) $amenityIds[$i] : 0;
        $label = trim((string) ($amenityLabels[$i] ?? ''));

        if ($label === '') {
            if ($id > 0) {
                $amenitiesToDelete[] = $id;
            }
            continue;
        }

        $payload = [
            'id'    => $id,
            'label' => $label,
            'order' => count($amenitiesToInsert) + count($amenitiesToUpdate),
        ];

        if ($id > 0) {
            $amenitiesToUpdate[] = $payload;
        } else {
            $amenitiesToInsert[] = $payload;
        }
    }

    if (!empty($amenitiesToDelete)) {
        $in = implode(',', array_fill(0, count($amenitiesToDelete), '?'));
        $pdo->prepare("DELETE FROM property_amenities WHERE property_id = ? AND id IN ($in)")
            ->execute(array_merge([$propertyId], $amenitiesToDelete));
    }

    if (!empty($amenitiesToUpdate)) {
        $stmtAmenityUpdate = $pdo->prepare(
            'UPDATE property_amenities SET label = :label, sort_order = :sort WHERE id = :id AND property_id = :property_id'
        );
        foreach ($amenitiesToUpdate as $amenity) {
            $stmtAmenityUpdate->execute([
                ':label'       => $amenity['label'],
                ':sort'        => $amenity['order'],
                ':id'          => $amenity['id'],
                ':property_id' => $propertyId,
            ]);
        }
    }

    if (!empty($amenitiesToInsert)) {
        $stmtAmenityInsert = $pdo->prepare(
            'INSERT INTO property_amenities (property_id, label, sort_order) VALUES (:property_id, :label, :sort_order)'
        );
        foreach ($amenitiesToInsert as $amenity) {
            $stmtAmenityInsert->execute([
                ':property_id' => $propertyId,
                ':label'       => $amenity['label'],
                ':sort_order'  => $amenity['order'],
            ]);
        }
    }

    // Seasons.
    $seasonIds = $_POST['season_id'] ?? [];
    $seasonLabels = $_POST['season_label'] ?? [];
    $seasonDates = $_POST['season_dates'] ?? [];
    $seasonRates = $_POST['season_rate'] ?? [];

    $seasonsToInsert = [];
    $seasonsToUpdate = [];
    $seasonsToDelete = [];

    $seasonCount = max(count($seasonLabels), count($seasonDates), count($seasonRates));
    for ($i = 0; $i < $seasonCount; $i++) {
        $id = isset($seasonIds[$i]) ? (int) $seasonIds[$i] : 0;
        $label = trim((string) ($seasonLabels[$i] ?? ''));
        $dates = trim((string) ($seasonDates[$i] ?? ''));
        $rate = normalise_rate((string) ($seasonRates[$i] ?? ''));

        if ($label === '' && $dates === '' && $rate === null) {
            if ($id > 0) {
                $seasonsToDelete[] = $id;
            }
            continue;
        }

        $payload = [
            'id'    => $id,
            'label' => $label,
            'dates' => $dates,
            'rate'  => $rate,
            'order' => count($seasonsToInsert) + count($seasonsToUpdate),
        ];

        if ($id > 0) {
            $seasonsToUpdate[] = $payload;
        } else {
            $seasonsToInsert[] = $payload;
        }
    }

    if (!empty($seasonsToDelete)) {
        $in = implode(',', array_fill(0, count($seasonsToDelete), '?'));
        $pdo->prepare("DELETE FROM property_seasons WHERE property_id = ? AND id IN ($in)")
            ->execute(array_merge([$propertyId], $seasonsToDelete));
    }

    if (!empty($seasonsToUpdate)) {
        $stmtSeasonUpdate = $pdo->prepare(
            'UPDATE property_seasons SET label = :label, date_range = :date_range, nightly_rate = :nightly_rate, sort_order = :sort WHERE id = :id AND property_id = :property_id'
        );
        foreach ($seasonsToUpdate as $season) {
            $stmtSeasonUpdate->execute([
                ':label'       => $season['label'],
                ':date_range'  => $season['dates'],
                ':nightly_rate'=> $season['rate'],
                ':sort'        => $season['order'],
                ':id'          => $season['id'],
                ':property_id' => $propertyId,
            ]);
        }
    }

    if (!empty($seasonsToInsert)) {
        $stmtSeasonInsert = $pdo->prepare(
            'INSERT INTO property_seasons (property_id, label, date_range, nightly_rate, sort_order)
             VALUES (:property_id, :label, :date_range, :nightly_rate, :sort_order)'
        );
        foreach ($seasonsToInsert as $season) {
            $stmtSeasonInsert->execute([
                ':property_id' => $propertyId,
                ':label'       => $season['label'],
                ':date_range'  => $season['dates'],
                ':nightly_rate'=> $season['rate'],
                ':sort_order'  => $season['order'],
            ]);
        }
    }

// Gallery existing updates.
$galleryToDelete = [];
$galleryToUpdate = [];
$maxGalleryOrder = -1;
$removeAllGallery = !empty($_POST['gallery_remove_all']);

if ($isEdit && !empty($existing['gallery'])) {
    if ($removeAllGallery) {
        foreach ($existing['gallery'] as $item) {
            $id = (int) $item['id'];
            $galleryToDelete[] = $id;
            if (!empty($item['image_path'])) {
                @unlink(PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $item['image_path']));
            }
        }
    } else {
        foreach ($existing['gallery'] as $index => $item) {
            $id = (int) $item['id'];
            $input = $galleryExistingInput[$id] ?? [];
            $remove = !empty($input['remove']);

            if ($remove) {
                $galleryToDelete[] = $id;
                if (!empty($item['image_path'])) {
                    @unlink(PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $item['image_path']));
                }
                continue;
            }

            $alt = isset($input['alt']) ? trim((string) $input['alt']) : ($item['alt_text'] ?? '');
            $caption = isset($input['caption']) ? trim((string) $input['caption']) : ($item['caption'] ?? '');
            $sortOrder = isset($input['sort_order']) && $input['sort_order'] !== '' ? (int) $input['sort_order'] : $index;
            $path = $input['path'] ?? $item['image_path'];

            $galleryToUpdate[] = [
                'id'    => $id,
                'path'  => $path,
                'alt'   => $alt,
                'caption' => $caption,
                'sort'  => $sortOrder,
            ];

            $maxGalleryOrder = max($maxGalleryOrder, $sortOrder);
        }
    }
}

    if (!empty($galleryToDelete)) {
        $in = implode(',', array_fill(0, count($galleryToDelete), '?'));
        $pdo->prepare("DELETE FROM property_gallery WHERE property_id = ? AND id IN ($in)")
            ->execute(array_merge([$propertyId], $galleryToDelete));
    }

    if (!empty($galleryToUpdate)) {
        $stmtGalleryUpdate = $pdo->prepare(
            'UPDATE property_gallery SET image_path = :path, alt_text = :alt, caption = :caption, sort_order = :sort
             WHERE id = :id AND property_id = :property_id'
        );
        foreach ($galleryToUpdate as $gallery) {
            $stmtGalleryUpdate->execute([
                ':path'        => $gallery['path'],
                ':alt'         => $gallery['alt'],
                ':caption'     => $gallery['caption'],
                ':sort'        => $gallery['sort'],
                ':id'          => $gallery['id'],
                ':property_id' => $propertyId,
            ]);
        }
    }

    // New gallery inserts.
    if (!empty($newGalleryPaths)) {
        $newAlts = $_POST['gallery_alt'] ?? [];
        $newCaptions = $_POST['gallery_caption'] ?? [];
        $stmtGalleryInsert = $pdo->prepare(
            'INSERT INTO property_gallery (property_id, image_path, alt_text, caption, sort_order)
             VALUES (:property_id, :image_path, :alt_text, :caption, :sort_order)'
        );
        foreach ($newGalleryPaths as $index => $path) {
            $alt = trim((string) ($newAlts[$index] ?? ''));
            $caption = trim((string) ($newCaptions[$index] ?? ''));
            $maxGalleryOrder++;
            $stmtGalleryInsert->execute([
                ':property_id' => $propertyId,
                ':image_path'  => $path,
                ':alt_text'    => $alt,
                ':caption'     => $caption,
                ':sort_order'  => $maxGalleryOrder,
            ]);
        }
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash('error', 'Property could not be saved. ' . $exception->getMessage());
    header('Location: ' . ($isEdit ? "/admin/property-edit.php?id={$propertyId}" : '/admin/property-create.php'));
    exit;
}

try {
    generate_property_page($propertyId, $previousSlug);
} catch (Throwable $exception) {
    set_flash('error', 'Property saved, but static page generation failed: ' . $exception->getMessage());
    header('Location: /admin/property-edit.php?id=' . $propertyId);
    exit;
}

set_flash('success', sprintf('"%s" saved successfully.', $name));
header('Location: /admin/properties.php');
exit;
