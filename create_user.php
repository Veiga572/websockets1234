<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $usersFile = __DIR__ . '/users.json';
    $usersData = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) : ['users' => []];
    // Check if username already exists
    foreach ($usersData['users'] as $user) {
        if ($user['username'] === $username) {
            $error = 'Username already exists!';
            break;
        }
    }
    if (!isset($error)) {
        $usersData['users'][] = [
            'username' => $username,
            'password' => $password // For production, hash the password!
        ];
        file_put_contents($usersFile, json_encode($usersData, JSON_PRETTY_PRINT));
        $success = 'User created successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
</head>
<body class="theme-light">
    <div class="container py-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create User</h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger" role="alert"><?php echo $error; ?></div>
                <?php elseif (isset($success)): ?>
                    <div class="alert alert-success" role="alert"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
</body>
</html>