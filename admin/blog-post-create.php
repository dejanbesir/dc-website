<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pdo = get_pdo();

$post = [
    'id'             => null,
    'title'          => '',
    'slug'           => '',
    'excerpt'        => '',
    'content'        => '',
    'meta_title'     => '',
    'meta_description'=> '',
    'canonical_url'  => '',
    'featured_image' => null,
    'featured_alt'   => '',
    'is_published'   => 1,
    'published_at'   => date('Y-m-d H:i:s'),
    'categories'     => [],
    'tags'           => [],
];

$categories = fetch_blog_categories($pdo);
$tags = fetch_blog_tags($pdo);
$errors = [];
$isEdit = false;
$pageTitle = 'Create Blog Article';
$activeNav = 'blog';

require __DIR__ . '/blog-post-form.php';
