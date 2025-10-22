<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/blog.php';
require_once __DIR__ . '/includes/auth.php';

require_admin();

$pdo = get_pdo();
$pageTitle = 'Blog Articles';
$activeNav = 'blog';
$posts = fetch_blog_posts_admin($pdo);

require_once __DIR__ . '/includes/header.php';
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-semibold text-slate-800">Blog Articles</h1>
    <div class="flex items-center gap-3">
        <a href="/admin/blog-categories.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Manage categories</a>
        <a href="/admin/blog-tags.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Manage tags</a>
        <a href="/admin/blog-post-create.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded">
            <span class="text-lg">+</span>
            <span class="text-sm font-medium">New Article</span>
        </a>
    </div>
</div>

<?php if (empty($posts)): ?>
    <div class="bg-white border border-dashed border-slate-200 rounded-lg p-10 text-center text-slate-500">
        <p class="text-lg font-medium mb-2">No blog articles yet.</p>
        <p>Create your first story to showcase Dubrovnik to your guests.</p>
        <a href="/admin/blog-post-create.php" class="mt-4 inline-flex items-center justify-center bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
            Create Article
        </a>
    </div>
<?php else: ?>
    <div class="overflow-x-auto bg-white border border-slate-200 rounded-lg shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-slate-600 uppercase tracking-wide text-xs">
                    <th class="px-4 py-3 font-semibold">Title</th>
                    <th class="px-4 py-3 font-semibold">Slug</th>
                    <th class="px-4 py-3 font-semibold">Categories</th>
                    <th class="px-4 py-3 font-semibold">Tags</th>
                    <th class="px-4 py-3 font-semibold">Published</th>
                    <th class="px-4 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($posts as $post): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-800"><?= htmlspecialchars($post['title']) ?></div>
                            <?php if (!empty($post['excerpt'])): ?>
                                <div class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($post['excerpt']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500"><?= htmlspecialchars($post['slug']) ?></td>
                        <td class="px-4 py-3 text-slate-600">
                            <?= htmlspecialchars($post['category_list'] ?? '—') ?>
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            <?= htmlspecialchars($post['tag_list'] ?? '—') ?>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 text-xs">
                                <?php if ((int) $post['is_published'] === 1): ?>
                                    <span class="inline-flex items-center bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full">Published</span>
                                <?php else: ?>
                                    <span class="inline-flex items-center bg-slate-100 text-slate-600 px-2 py-1 rounded-full">Hidden</span>
                                <?php endif; ?>
                                <span class="text-slate-500">
                                    <?= htmlspecialchars(date('M j, Y', strtotime((string) $post['published_at']))) ?>
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                <a href="/blog/<?= htmlspecialchars($post['slug']) ?>/" target="_blank" class="text-sm text-indigo-600 hover:underline">View</a>
                                <a href="/admin/blog-post-edit.php?id=<?= (int) $post['id'] ?>" class="text-sm text-indigo-600 hover:underline">Edit</a>
                                <form action="/admin/blog-post-delete.php" method="post" class="inline">
                                    <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                                    <button type="submit" class="text-sm text-rose-600 hover:underline" onclick="return confirm('Delete this article? This cannot be undone.');">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
