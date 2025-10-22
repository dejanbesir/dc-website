<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/**
 * Ensures a blog slug is unique, appending suffixes as needed.
 */
function ensure_unique_blog_slug(PDO $pdo, string $slug, ?int $ignoreId = null): string
{
    $base = $slug;
    $suffix = 1;

    $query = 'SELECT id FROM blog_posts WHERE slug = :slug';
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
 * Stores a featured image for a blog post and returns its public path.
 */
function store_blog_featured_image(array $file, string $slug): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('No featured image uploaded.');
    }

    $original = (string) ($file['name'] ?? '');
    $extension = strtolower(preg_replace('/[^a-z0-9]/i', '', pathinfo($original, PATHINFO_EXTENSION) ?? ''));
    if ($extension === '') {
        $extension = 'jpg';
    }

    $safeSlug = $slug !== '' ? $slug : 'post-' . uniqid();

    if (!is_dir(BLOG_IMAGE_DIR) && !mkdir(BLOG_IMAGE_DIR, 0775, true) && !is_dir(BLOG_IMAGE_DIR)) {
        throw new RuntimeException('Unable to create blog image directory.');
    }

    $targetDir = BLOG_IMAGE_DIR . DIRECTORY_SEPARATOR . $safeSlug;
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        throw new RuntimeException('Unable to create blog post image directory.');
    }

    try {
        $token = bin2hex(random_bytes(4));
    } catch (Throwable $exception) {
        $token = substr(uniqid('', true), -8);
    }

    $filename = sprintf('featured-%s-%s.%s', date('YmdHis'), $token, $extension);
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $targetPath)) {
        throw new RuntimeException('Failed to move uploaded featured image.');
    }

    chmod($targetPath, 0644);

    return sprintf('%s/%s/%s', BLOG_IMAGE_PREFIX, $safeSlug, $filename);
}

/**
 * Calculates reading time (in minutes) based on body content.
 */
function calculate_reading_time(string $content): int
{
    $plain = trim(strip_tags($content));
    if ($plain === '') {
        return 1;
    }

    $words = str_word_count($plain);
    return max(1, (int) ceil($words / 225));
}

/**
 * Loads all categories ordered alphabetically.
 *
 * @return array<int, array{id:int, name:string, slug:string}>
 */
function fetch_blog_categories(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, slug FROM blog_categories ORDER BY name ASC');
    return $stmt->fetchAll() ?: [];
}

/**
 * Loads all tags ordered alphabetically.
 *
 * @return array<int, array{id:int, name:string, slug:string}>
 */
function fetch_blog_tags(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, slug FROM blog_tags ORDER BY name ASC');
    return $stmt->fetchAll() ?: [];
}

/**
 * Loads a single blog post with its categories and tags.
 *
 * @return array<string, mixed>|null
 */
function fetch_blog_post(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $post = $stmt->fetch();
    if (!$post) {
        return null;
    }

    $post['categories'] = fetch_blog_post_term_ids($pdo, $id, 'category');
    $post['tags'] = fetch_blog_post_term_ids($pdo, $id, 'tag');

    return $post;
}

/**
 * Fetches all blog posts for admin overview.
 *
 * @return array<int, array<string, mixed>>
 */
function fetch_blog_posts_admin(PDO $pdo): array
{
    $sql = <<<SQL
        SELECT
            p.*,
            GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') AS category_list,
            GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS tag_list
        FROM blog_posts p
        LEFT JOIN blog_post_categories pc ON pc.post_id = p.id
        LEFT JOIN blog_categories c ON c.id = pc.category_id
        LEFT JOIN blog_post_tags pt ON pt.post_id = p.id
        LEFT JOIN blog_tags t ON t.id = pt.tag_id
        GROUP BY p.id
        ORDER BY p.published_at DESC, p.id DESC
    SQL;

    $stmt = $pdo->query($sql);
    return $stmt->fetchAll() ?: [];
}

/**
 * @return array<int>
 */
function fetch_blog_post_term_ids(PDO $pdo, int $postId, string $type): array
{
    if ($type === 'category') {
        $sql = 'SELECT category_id FROM blog_post_categories WHERE post_id = :id';
    } elseif ($type === 'tag') {
        $sql = 'SELECT tag_id FROM blog_post_tags WHERE post_id = :id';
    } else {
        return [];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $postId]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

/**
 * Persists a blog post (create or update).
 *
 * @param array<string, mixed> $input
 * @param array<string, mixed> $files
 * @return array{success:bool, id:int|null, errors:array<string,string>}
 */
function save_blog_post(array $input, array $files): array
{
    $pdo = get_pdo();
    $pdo->beginTransaction();

    $errors = [];

    $id = isset($input['id']) ? (int) $input['id'] : null;
    $current = null;
    if ($id !== null) {
        $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $current = $stmt->fetch();
        if (!$current) {
            $pdo->rollBack();
            return ['success' => false, 'id' => null, 'errors' => ['general' => 'Blog post not found.']];
        }
    }

    $title = trim((string) ($input['title'] ?? ''));
    $slugInput = trim((string) ($input['slug'] ?? ''));
    $excerpt = trim((string) ($input['excerpt'] ?? ''));
    $content = trim((string) ($input['content'] ?? ''));
    $metaTitle = trim((string) ($input['meta_title'] ?? ''));
    $metaDescription = trim((string) ($input['meta_description'] ?? ''));
    $canonicalUrl = trim((string) ($input['canonical_url'] ?? ''));
    $featuredAlt = trim((string) ($input['featured_alt'] ?? ''));
    $isPublished = isset($input['is_published']) ? 1 : 0;
    $publishedAt = trim((string) ($input['published_at'] ?? ''));

    $categoryIds = array_map('intval', $input['categories'] ?? []);
    $tagIds = array_map('intval', $input['tags'] ?? []);

    if ($title === '') {
        $errors['title'] = 'Title is required.';
    }

    if ($content === '') {
        $errors['content'] = 'Content cannot be empty.';
    }

    $slug = $slugInput !== '' ? $slugInput : slugify($title);
    if ($slug === '') {
        $errors['slug'] = 'Unable to derive a valid slug.';
    }

    if ($publishedAt !== '') {
        $publishedAt = str_replace('T', ' ', $publishedAt);
        $timestamp = strtotime($publishedAt);
        if ($timestamp === false) {
            $errors['published_at'] = 'Invalid publish date.';
        } else {
            $publishedAt = date('Y-m-d H:i:s', $timestamp);
        }
    } else {
        $publishedAt = $current['published_at'] ?? date('Y-m-d H:i:s');
    }

    if ($errors) {
        $pdo->rollBack();
        return ['success' => false, 'id' => $id, 'errors' => $errors];
    }

    $slug = ensure_unique_blog_slug($pdo, $slug, $id);

    if ($excerpt === '') {
        $excerpt = mb_substr(trim(strip_tags($content)), 0, 180);
    }

    $readingTime = calculate_reading_time($content);

    $featuredImagePath = $current['featured_image'] ?? null;

    if (!empty($files['featured_image']) && ($files['featured_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        try {
            $featuredImagePath = store_blog_featured_image($files['featured_image'], $slug);
        } catch (Throwable $exception) {
            $errors['featured_image'] = $exception->getMessage();
            $pdo->rollBack();
            return ['success' => false, 'id' => $id, 'errors' => $errors];
        }
    }

    if ($featuredImagePath === null) {
        $pdo->rollBack();
        $errors['featured_image'] = 'Featured image is required.';
        return ['success' => false, 'id' => $id, 'errors' => $errors];
    }

    try {
        if ($id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO blog_posts
                   (slug, title, excerpt, content, featured_image, featured_alt, meta_title, meta_description,
                    canonical_url, is_published, published_at, reading_time)
                 VALUES
                   (:slug, :title, :excerpt, :content, :featured_image, :featured_alt, :meta_title, :meta_description,
                    :canonical_url, :is_published, :published_at, :reading_time)'
            );
            $stmt->execute([
                ':slug'            => $slug,
                ':title'           => $title,
                ':excerpt'         => $excerpt,
                ':content'         => $content,
                ':featured_image'  => $featuredImagePath,
                ':featured_alt'    => $featuredAlt,
                ':meta_title'      => $metaTitle !== '' ? $metaTitle : null,
                ':meta_description'=> $metaDescription !== '' ? $metaDescription : null,
                ':canonical_url'   => $canonicalUrl !== '' ? $canonicalUrl : null,
                ':is_published'    => $isPublished,
                ':published_at'    => $publishedAt,
                ':reading_time'    => $readingTime,
            ]);

            $id = (int) $pdo->lastInsertId();
        } else {
            $fields = [
                'slug'            => $slug,
                'title'           => $title,
                'excerpt'         => $excerpt,
                'content'         => $content,
                'featured_alt'    => $featuredAlt !== '' ? $featuredAlt : null,
                'meta_title'      => $metaTitle !== '' ? $metaTitle : null,
                'meta_description'=> $metaDescription !== '' ? $metaDescription : null,
                'canonical_url'   => $canonicalUrl !== '' ? $canonicalUrl : null,
                'is_published'    => $isPublished,
                'published_at'    => $publishedAt,
                'reading_time'    => $readingTime,
            ];

            $fields['featured_image'] = $featuredImagePath;

            $setParts = [];
            $params = [];
            foreach ($fields as $column => $value) {
                $setParts[] = sprintf('%s = :%s', $column, $column);
                $params[':' . $column] = $value;
            }
            $params[':id'] = $id;

            $sql = sprintf('UPDATE blog_posts SET %s WHERE id = :id', implode(', ', $setParts));
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        sync_blog_post_terms($pdo, $id, 'category', $categoryIds);
        sync_blog_post_terms($pdo, $id, 'tag', $tagIds);
    } catch (Throwable $exception) {
        $pdo->rollBack();
        $errors['general'] = 'Failed to save blog post: ' . $exception->getMessage();
        return ['success' => false, 'id' => $id, 'errors' => $errors];
    }

    $pdo->commit();

    return ['success' => true, 'id' => $id, 'errors' => []];
}

/**
 * Synchronises many-to-many term relations.
 *
 * @param array<int> $ids
 */
function sync_blog_post_terms(PDO $pdo, int $postId, string $type, array $ids): void
{
    $ids = array_values(array_unique(array_filter($ids, static fn($value) => $value > 0)));

    if ($type === 'category') {
        $table = 'blog_post_categories';
        $column = 'category_id';
    } elseif ($type === 'tag') {
        $table = 'blog_post_tags';
        $column = 'tag_id';
    } else {
        return;
    }

    $pdo->prepare(sprintf('DELETE FROM %s WHERE post_id = :id', $table))
        ->execute([':id' => $postId]);

    if (!$ids) {
        return;
    }

    $stmt = $pdo->prepare(sprintf(
        'INSERT INTO %s (post_id, %s) VALUES (:post, :term)',
        $table,
        $column
    ));

    foreach ($ids as $termId) {
        $stmt->execute([
            ':post' => $postId,
            ':term' => $termId,
        ]);
    }
}

/**
 * Creates a new blog category.
 */
function create_blog_category(string $name): array
{
    $pdo = get_pdo();
    $name = trim($name);
    if ($name === '') {
        return ['success' => false, 'error' => 'Name is required.'];
    }

    $slug = slugify($name);
    if ($slug === '') {
        return ['success' => false, 'error' => 'Unable to derive slug from name.'];
    }

    $slug = ensure_unique_term_slug($pdo, $slug, 'category');

    $stmt = $pdo->prepare('INSERT INTO blog_categories (name, slug) VALUES (:name, :slug)');
    $stmt->execute([
        ':name' => $name,
        ':slug' => $slug,
    ]);

    return ['success' => true];
}

/**
 * Creates a new blog tag.
 */
function create_blog_tag(string $name): array
{
    $pdo = get_pdo();
    $name = trim($name);
    if ($name === '') {
        return ['success' => false, 'error' => 'Name is required.'];
    }

    $slug = slugify($name);
    if ($slug === '') {
        return ['success' => false, 'error' => 'Unable to derive slug from name.'];
    }

    $slug = ensure_unique_term_slug($pdo, $slug, 'tag');

    $stmt = $pdo->prepare('INSERT INTO blog_tags (name, slug) VALUES (:name, :slug)');
    $stmt->execute([
        ':name' => $name,
        ':slug' => $slug,
    ]);

    return ['success' => true];
}

/**
 * Deletes a category by ID.
 */
function delete_blog_category(int $id): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM blog_categories WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/**
 * Deletes a tag by ID.
 */
function delete_blog_tag(int $id): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM blog_tags WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/**
 * Deletes a blog post by ID.
 */
function delete_blog_post(int $id): void
{
    $pdo = get_pdo();
    $stmt = $pdo->prepare('DELETE FROM blog_posts WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

/**
 * Ensures category/tag slugs are unique.
 */
function ensure_unique_term_slug(PDO $pdo, string $slug, string $type, ?int $ignoreId = null): string
{
    $table = $type === 'tag' ? 'blog_tags' : 'blog_categories';

    $base = $slug;
    $suffix = 1;

    $query = sprintf('SELECT id FROM %s WHERE slug = :slug', $table);
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
