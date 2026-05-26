<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Login - <?= htmlspecialchars(APP_NAME) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --blue: #0f2a44;
            --blue-dark: #071827;
            --orange: #ff8a1f;
            --bg: #f4f7fb;
            --white: #ffffff;
            --text: #132033;
            --muted: #6b7280;
            --border: #e5e7eb;
            --danger: #b91c1c;
            --danger-bg: #fee2e2;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, var(--blue-dark), var(--blue));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text);
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: var(--white);
            border-radius: 22px;
            padding: 32px;
            box-shadow: 0 24px 70px rgba(0,0,0,0.24);
        }

        .brand {
            margin-bottom: 26px;
        }

        .brand-label {
            display: inline-flex;
            padding: 7px 12px;
            border-radius: 999px;
            background: #eef5ff;
            color: var(--blue);
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 14px;
        }

        h1 {
            margin: 0;
            font-size: 28px;
            line-height: 1.1;
            color: var(--blue-dark);
        }

        p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 15px;
        }

        .error {
            background: var(--danger-bg);
            color: var(--danger);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 700;
            color: var(--blue-dark);
        }

        .field {
            margin-bottom: 18px;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 14px 15px;
            font-size: 16px;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        input:focus {
            border-color: var(--orange);
            box-shadow: 0 0 0 4px rgba(255, 138, 31, .16);
        }

        button {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 15px 18px;
            background: var(--orange);
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: transform .15s, opacity .15s;
        }

        button:hover {
            transform: translateY(-1px);
            opacity: .95;
        }

        .small {
            margin-top: 18px;
            text-align: center;
            font-size: 13px;
            color: var(--muted);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand">
        <div class="brand-label"><?= htmlspecialchars(APP_NAME) ?> Admin</div>
        <h1>Inloggen</h1>
        <p>Beheer aanvragen, leads en bedrijven.</p>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="/actions/login_action.php" method="post">
        <?= csrf_field() ?>

        <div class="field">
            <label for="username">Gebruikersnaam</label>
            <input type="text" id="username" name="username" required autocomplete="username">
        </div>

        <div class="field">
            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>

        <button type="submit">Inloggen</button>
    </form>

    <div class="small">
        KetelOfferte24.nl
    </div>
</div>

</body>
</html>
