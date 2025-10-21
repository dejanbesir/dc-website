<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_admin_authenticated()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';

    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        mark_admin_authenticated();
        header('Location: /admin/dashboard.php');
        exit;
    }

    $error = 'Invalid password. Please try again.';
}

$flash = get_flash_messages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login · Dubrovnik Coast</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 flex items-center justify-center">
    <div class="w-full max-w-md bg-white shadow-lg rounded-lg p-8 space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-slate-800 text-center">Dubrovnik Coast Admin</h1>
            <p class="text-center text-slate-500 text-sm mt-2">Enter the administrator password to continue.</p>
        </div>

        <?php if ($error !== null): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-4">
            <label class="block">
                <span class="text-sm font-medium text-slate-600">Admin Password</span>
                <input
                    type="password"
                    name="password"
                    required
                    class="mt-1 block w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="••••••••"
                >
            </label>

            <button
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 rounded transition"
            >
                Sign In
            </button>
        </form>

        <p class="text-xs text-slate-400 text-center">
            Tip: update the admin password hash in <code>admin/includes/config.php</code> before production.
        </p>
    </div>
</body>
</html>
