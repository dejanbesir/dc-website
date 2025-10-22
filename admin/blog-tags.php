<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $name = (string) ($_POST['name'] ?? '');
        $result = create_blog_tag($name);
        if ($result['success']) {
            set_flash('success', 'Tag added.');
        } else {
            set_flash('error', $result['error'] ?? 'Unable to add tag.');
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            delete_blog_tag($id);
            set_flash('success', 'Tag removed.');
        } else {
            set_flash('error', 'Invalid tag identifier.');
        }
    }

    header('Location: /admin/blog-tags.php');
    exit;
}

$tags = fetch_blog_tags($pdo);
$pageTitle = 'Blog Tags';
$activeNav = 'blog';

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800">Blog Tags</h1>
        <p class="text-sm text-slate-500">Mark articles with flexible descriptors such as “family travel” or “heritage”.</p>
    </div>
    <a href="/admin/blog.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Back to articles</a>
</div>

<section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 mb-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Add tag</h2>
    <form method="post" class="flex flex-col md:flex-row gap-3 md:items-center">
        <input type="hidden" name="action" value="create">
        <label for="name" class="sr-only">Tag name</label>
        <input type="text" id="name" name="name" required
               class="flex-1 border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
               placeholder="e.g. Beaches">
        <button type="submit" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium">
            Add Tag
        </button>
    </form>
</section>

<section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6">
    <h2 class="text-lg font-semibold text-slate-800 mb-4">Existing tags</h2>
    <?php if (empty($tags)): ?>
        <p class="text-sm text-slate-500">No tags yet. Create your first tag above.</p>
    <?php else: ?>
        <ul class="divide-y divide-slate-200">
            <?php foreach ($tags as $tag): ?>
                <li class="py-3 flex items-center justify-between">
                    <div>
                        <div class="font-medium text-slate-800"><?= htmlspecialchars($tag['name']) ?></div>
                        <div class="text-xs text-slate-500 font-mono"><?= htmlspecialchars($tag['slug']) ?></div>
                    </div>
                    <form method="post" onsubmit="return confirm('Delete this tag?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $tag['id'] ?>">
                        <button type="submit" class="text-sm text-rose-600 hover:underline">Delete</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
