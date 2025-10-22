<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/includes/blog.php';

/**
 * Fetches published blog posts with categories and tags for the listing page.
 *
 * @return array{posts: array<int, array<string, mixed>>, categories: array<int, array<string, mixed>>, tags: array<int, array<string, mixed>>}
 */
function fetch_blog_index_data(): array
{
    $pdo = get_pdo();

    $posts = fetch_blog_posts_public($pdo);
    $categories = fetch_blog_categories($pdo);
    $tags = fetch_blog_tags($pdo);

    return [
        'posts' => $posts,
        'categories' => $categories,
        'tags' => $tags,
    ];
}

/**
 * Loads a blog post by slug for the public site.
 *
 * @return array<string, mixed>|null
 */
function fetch_blog_post_by_slug(string $slug): ?array
{
    $pdo = get_pdo();
    return fetch_single_blog_post_public($pdo, $slug);
}

/**
 * Returns additional posts for "You may also like" sections.
 *
 * @param array<int> $excludeIds
 * @return array<int, array<string, mixed>>
 */
function fetch_additional_blog_posts(array $excludeIds = [], int $limit = 3): array
{
    $pdo = get_pdo();
    $sql = 'SELECT id, slug, title, excerpt, featured_image, featured_alt, published_at
            FROM blog_posts
            WHERE is_published = 1';

    $params = [];
    if ($excludeIds) {
        $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
        $sql .= " AND id NOT IN ($placeholders)";
        $params = array_map('intval', $excludeIds);
    }

    $sql .= ' ORDER BY published_at DESC LIMIT ' . max(1, $limit);

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll() ?: [];
}

/**
 * @return array<int, array<string, mixed>>
 */
function fetch_blog_posts_public(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            p.id,
            p.slug,
            p.title,
            p.excerpt,
            p.featured_image,
            p.featured_alt,
            p.published_at,
            p.reading_time,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '||') AS category_names,
            GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR '||') AS category_slugs,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR '||') AS tag_names,
            GROUP_CONCAT(DISTINCT t.slug ORDER BY t.slug SEPARATOR '||') AS tag_slugs
        FROM blog_posts p
        LEFT JOIN blog_post_categories pc ON pc.post_id = p.id
        LEFT JOIN blog_categories c ON c.id = pc.category_id
        LEFT JOIN blog_post_tags pt ON pt.post_id = p.id
        LEFT JOIN blog_tags t ON t.id = pt.tag_id
        WHERE p.is_published = 1
        ORDER BY p.published_at DESC, p.id DESC
    SQL;

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll() ?: [];

    foreach ($rows as &$row) {
        $row['category_names'] = convert_concat_field($row['category_names'] ?? '');
        $row['category_slugs'] = convert_concat_field($row['category_slugs'] ?? '');
        $row['tag_names'] = convert_concat_field($row['tag_names'] ?? '');
        $row['tag_slugs'] = convert_concat_field($row['tag_slugs'] ?? '');
    }
    unset($row);

    return $rows;
}

/**
 * @return array<string, mixed>|null
 */
function fetch_single_blog_post_public(PDO $pdo, string $slug): ?array
{
    $sql = <<<SQL
        SELECT
            p.*,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR '||') AS category_names,
            GROUP_CONCAT(DISTINCT c.slug ORDER BY c.slug SEPARATOR '||') AS category_slugs,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR '||') AS tag_names,
            GROUP_CONCAT(DISTINCT t.slug ORDER BY t.slug SEPARATOR '||') AS tag_slugs
        FROM blog_posts p
        LEFT JOIN blog_post_categories pc ON pc.post_id = p.id
        LEFT JOIN blog_categories c ON c.id = pc.category_id
        LEFT JOIN blog_post_tags pt ON pt.post_id = p.id
        LEFT JOIN blog_tags t ON t.id = pt.tag_id
        WHERE p.slug = :slug
          AND p.is_published = 1
        GROUP BY p.id
        LIMIT 1
    SQL;

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();

    if (!$row) {
        return null;
    }

    $row['category_names'] = convert_concat_field($row['category_names'] ?? '');
    $row['category_slugs'] = convert_concat_field($row['category_slugs'] ?? '');
    $row['tag_names'] = convert_concat_field($row['tag_names'] ?? '');
    $row['tag_slugs'] = convert_concat_field($row['tag_slugs'] ?? '');

    return $row;
}

/**
 * @return array<int, string>
 */
function convert_concat_field(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    return array_values(array_filter(array_map('trim', explode('||', $value))));
}
