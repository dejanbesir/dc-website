<?php
declare(strict_types=1);

/** @var array<string, mixed> $post */
/** @var array<int, array{id:int, name:string, slug:string}> $categories */
/** @var array<int, array{id:int, name:string, slug:string}> $tags */
/** @var array<string, string> $errors */
/** @var bool $isEdit */

$post = $post ?? [];
$categories = $categories ?? [];
$tags = $tags ?? [];
$errors = $errors ?? [];
$isEdit = $isEdit ?? false;

$pageTitle = $pageTitle ?? ($isEdit ? 'Edit Blog Article' : 'Create Blog Article');
$activeNav = 'blog';

require_once __DIR__ . '/includes/header.php';

$publishedAt = $post['published_at'] ?? date('Y-m-d H:i:s');
$publishedInput = '';
if ($publishedAt) {
    $timestamp = strtotime((string) $publishedAt);
    if ($timestamp !== false) {
        $publishedInput = date('Y-m-d\TH:i', $timestamp);
    }
}

$selectedCategories = array_map('intval', $post['categories'] ?? []);
$selectedTags = array_map('intval', $post['tags'] ?? []);

function field_error(array $errors, string $key): ?string
{
    return $errors[$key] ?? null;
}
?>

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-800"><?= $isEdit ? 'Edit Blog Article' : 'Create Blog Article' ?></h1>
        <p class="text-sm text-slate-500">Craft destination stories, travel tips, and insider guides for Dubrovnik Coast.</p>
    </div>
    <a href="/admin/blog.php" class="text-sm text-slate-500 hover:text-slate-700 underline">Back to blog list</a>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($errors['general']) ?>
    </div>
<?php endif; ?>

<form action="/admin/blog-post-save.php" method="post" enctype="multipart/form-data" class="space-y-8">
    <?php if ($post['id'] ?? null): ?>
        <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
    <?php endif; ?>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-slate-800">Article Details</h2>
            <p class="text-sm text-slate-500">Core information shown in listings and on the post itself.</p>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label for="title" class="block text-sm font-medium text-slate-700">Title<span class="text-rose-500">*</span></label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                       class="w-full border <?= field_error($errors, 'title') ? 'border-rose-300' : 'border-slate-300' ?> rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <?php if ($message = field_error($errors, 'title')): ?>
                    <p class="text-xs text-rose-600"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>
            <div class="space-y-2">
                <label for="slug" class="block text-sm font-medium text-slate-700">Slug</label>
                <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>"
                       class="w-full border <?= field_error($errors, 'slug') ? 'border-rose-300' : 'border-slate-300' ?> rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="e.g. summer-in-dubrovnik-guide">
                <?php if ($message = field_error($errors, 'slug')): ?>
                    <p class="text-xs text-rose-600"><?= htmlspecialchars($message) ?></p>
                <?php else: ?>
                    <p class="text-xs text-slate-500">Leave blank to auto-generate from the title.</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="space-y-2">
            <label for="excerpt" class="block text-sm font-medium text-slate-700">Short excerpt</label>
            <textarea id="excerpt" name="excerpt" rows="3"
                      class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
            <p class="text-xs text-slate-500">Used in previews and meta descriptions. Will be truncated automatically if left blank.</p>
        </div>

        <div class="space-y-2">
            <label for="content-editor" class="block text-sm font-medium text-slate-700">Main content<span class="text-rose-500">*</span></label>
            <textarea id="content-editor" name="content" rows="12"
                      class="hidden"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
            <div id="content-editor__container" class="border <?= field_error($errors, 'content') ? 'border-rose-300' : 'border-slate-300' ?> rounded">
                <div id="content-editor__placeholder" style="min-height: 320px;"></div>
            </div>
            <?php if ($message = field_error($errors, 'content')): ?>
                <p class="text-xs text-rose-600"><?= htmlspecialchars($message) ?></p>
            <?php endif; ?>
            <p class="text-xs text-slate-500">Rich text editor supports headings, links, lists, and images.</p>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-slate-800">Media &amp; Publish Settings</h2>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Featured image<span class="text-rose-500">*</span></label>
                <?php if (!empty($post['featured_image'])): ?>
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" alt="" class="rounded border border-slate-200 max-h-48 object-cover mb-3">
                <?php endif; ?>
                <input type="file" name="featured_image" accept="image/*"
                       class="block w-full text-sm text-slate-600">
                <?php if ($message = field_error($errors, 'featured_image')): ?>
                    <p class="text-xs text-rose-600"><?= htmlspecialchars($message) ?></p>
                <?php else: ?>
                    <p class="text-xs text-slate-500">High-quality hero image recommended (min 1200px wide).</p>
                <?php endif; ?>
            </div>
            <div class="space-y-2">
                <label for="featured_alt" class="block text-sm font-medium text-slate-700">Featured image alt text</label>
                <input type="text" id="featured_alt" name="featured_alt" value="<?= htmlspecialchars($post['featured_alt'] ?? '') ?>"
                       class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Describe the image for accessibility and SEO">
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="space-y-2">
                <label for="published_at" class="block text-sm font-medium text-slate-700">Publish date &amp; time</label>
                <input type="datetime-local" id="published_at" name="published_at"
                       value="<?= htmlspecialchars($publishedInput) ?>"
                       class="w-full border <?= field_error($errors, 'published_at') ? 'border-rose-300' : 'border-slate-300' ?> rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <?php if ($message = field_error($errors, 'published_at')): ?>
                    <p class="text-xs text-rose-600"><?= htmlspecialchars($message) ?></p>
                <?php endif; ?>
            </div>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-slate-700">Visibility</label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="is_published" value="1" <?= !isset($post['is_published']) || (int) $post['is_published'] === 1 ? 'checked' : '' ?>
                           class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    Published (uncheck to hide from public blog)
                </label>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-slate-800">Categories &amp; Tags</h2>
            <p class="text-sm text-slate-500">Group articles by travel themes and filter them on the public blog.</p>
        </header>

        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Categories</h3>
                    <a href="/admin/blog-categories.php" class="text-xs text-indigo-600 hover:underline">Manage</a>
                </div>
                <?php if (empty($categories)): ?>
                    <p class="text-sm text-slate-500">No categories yet. <a href="/admin/blog-categories.php" class="underline">Create one</a>.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                <input type="checkbox" name="categories[]"
                                       value="<?= (int) $category['id'] ?>"
                                       <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?>
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span><?= htmlspecialchars($category['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Tags</h3>
                    <a href="/admin/blog-tags.php" class="text-xs text-indigo-600 hover:underline">Manage</a>
                </div>
                <?php if (empty($tags)): ?>
                    <p class="text-sm text-slate-500">No tags yet. <a href="/admin/blog-tags.php" class="underline">Create one</a>.</p>
                <?php else: ?>
                    <div class="space-y-2">
                        <?php foreach ($tags as $tag): ?>
                            <label class="flex items-center gap-3 text-sm text-slate-600">
                                <input type="checkbox" name="tags[]"
                                       value="<?= (int) $tag['id'] ?>"
                                       <?= in_array((int) $tag['id'], $selectedTags, true) ? 'checked' : '' ?>
                                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span><?= htmlspecialchars($tag['name']) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg shadow-sm p-6 space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-slate-800">Search &amp; Social Metadata</h2>
            <p class="text-sm text-slate-500">Optional fields for SEO and social sharing.</p>
        </header>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="space-y-2 md:col-span-2">
                <label for="meta_title" class="block text-sm font-medium text-slate-700">Meta title</label>
                <input type="text" id="meta_title" name="meta_title" value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>"
                       class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <p class="text-xs text-slate-500">Defaults to the article title if left blank.</p>
            </div>
            <div class="space-y-2">
                <label for="canonical_url" class="block text-sm font-medium text-slate-700">Canonical URL</label>
                <input type="text" id="canonical_url" name="canonical_url" value="<?= htmlspecialchars($post['canonical_url'] ?? '') ?>"
                       class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <div class="space-y-2">
            <label for="meta_description" class="block text-sm font-medium text-slate-700">Meta description</label>
            <textarea id="meta_description" name="meta_description" rows="3"
                      class="w-full border border-slate-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
            <p class="text-xs text-slate-500">Aim for 155–160 characters highlighting the experience or travel value.</p>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="/admin/blog.php" class="text-sm text-slate-500 hover:text-slate-700">Cancel</a>
        <button type="submit" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded text-sm font-medium">
            <?= $isEdit ? 'Update Article' : 'Publish Article' ?>
        </button>
    </div>
</form>

<script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
<script>
    (function() {
        const textarea = document.getElementById('content-editor');
        const placeholder = document.getElementById('content-editor__placeholder');

        const initialData = textarea.value;

        ClassicEditor
            .create(placeholder, {
                toolbar: [
                    'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList',
                    'blockQuote', 'insertTable', 'undo', 'redo'
                ],
                placeholder: 'Write your article here...'
            })
            .then(editor => {
                editor.setData(initialData);
                const form = textarea.closest('form');
                form.addEventListener('submit', () => {
                    textarea.value = editor.getData();
                });
            })
            .catch(error => {
                console.error(error);
            });
    })();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
