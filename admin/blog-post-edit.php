<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    set_flash('error', 'Missing blog article identifier.');
    header('Location: /admin/blog.php');
    exit;
}

$pdo = get_pdo();
$post = fetch_blog_post($pdo, $id);

if (!$post) {
    set_flash('error', 'Blog article not found.');
    header('Location: /admin/blog.php');
    exit;
}

$post['categories'] = $post['categories'] ?? [];
$post['tags'] = $post['tags'] ?? [];

$categories = fetch_blog_categories($pdo);
$tags = fetch_blog_tags($pdo);
$errors = [];
$isEdit = true;
$pageTitle = 'Edit Blog Article';
$activeNav = 'blog';

require __DIR__ . '/blog-post-form.php';
