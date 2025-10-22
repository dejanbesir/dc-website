<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/blog.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    delete_blog_post($id);
    set_flash('success', 'Blog article deleted.');
} else {
    set_flash('error', 'Unable to determine which article to delete.');
}

header('Location: /admin/blog.php');
exit;
