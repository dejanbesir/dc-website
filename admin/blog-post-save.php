<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/blog.php');
    exit;
}

$result = save_blog_post($_POST, $_FILES);

if ($result['success']) {
    $message = isset($_POST['id']) && $_POST['id'] !== '' ? 'Blog article updated.' : 'Blog article created.';
    set_flash('success', $message);
    header('Location: /admin/blog-post-edit.php?id=' . (int) $result['id']);
    exit;
}

$errors = $result['errors'];

$pdo = get_pdo();

$post = [
    'id'              => $result['id'],
    'title'           => trim((string) ($_POST['title'] ?? '')),
    'slug'            => trim((string) ($_POST['slug'] ?? '')),
    'excerpt'         => trim((string) ($_POST['excerpt'] ?? '')),
    'content'         => trim((string) ($_POST['content'] ?? '')),
    'meta_title'      => trim((string) ($_POST['meta_title'] ?? '')),
    'meta_description'=> trim((string) ($_POST['meta_description'] ?? '')),
    'canonical_url'   => trim((string) ($_POST['canonical_url'] ?? '')),
    'featured_image'  => $result['id'] ? (fetch_blog_post($pdo, (int) $result['id'])['featured_image'] ?? null) : null,
    'featured_alt'    => trim((string) ($_POST['featured_alt'] ?? '')),
    'is_published'    => isset($_POST['is_published']) ? 1 : 0,
    'published_at'    => trim((string) ($_POST['published_at'] ?? '')),
    'categories'      => array_map('intval', $_POST['categories'] ?? []),
    'tags'            => array_map('intval', $_POST['tags'] ?? []),
];

if (!empty($post['published_at'])) {
    $post['published_at'] = str_replace('T', ' ', $post['published_at']);
}

$categories = fetch_blog_categories($pdo);
$tags = fetch_blog_tags($pdo);
$isEdit = $post['id'] !== null;
$pageTitle = $isEdit ? 'Edit Blog Article' : 'Create Blog Article';
$activeNav = 'blog';

require __DIR__ . '/blog-post-form.php';
