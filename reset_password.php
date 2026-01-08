<!-- <!-- <?php
/**
 * reset_password.php
 * Password Reset Page for QuickMark
 * Allows users to set a new password using reset token
 */

// require_once 'includes/password_reset.php';

// $error = '';
// $success = '';
// $token_valid = false;
// $token = '';

// // Check if token is provided
// if (isset($_GET['token'])) {
//     $token = trim($_GET['token']);
//     $passwordReset = new PasswordReset();
//     $token_data = $passwordReset->validateToken($token);
    
//     if ($token_data) {
//         $token_valid = true;
//     } else {
//         $error = 'Invalid or expired reset link. Please request a new password reset.';
//     }
// } else {
//     $error = 'No reset token provided.';
// }

// // Handle password reset form submission
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
//     $new_password = trim($_POST['new_password']);
//     $confirm_password = trim($_POST['confirm_password']);
    
//     // Validate password
//     if (strlen($new_password) < 6) {
//         $error = 'Password must be at least 6 characters long.';
//     } elseif ($new_password !== $confirm_password) {
//         $error = 'Passwords do not match.';
//     } else {
//         $passwordReset = new PasswordReset();
//         if ($passwordReset->updatePassword($token, $new_password)) {
//             $success = 'Password updated successfully! You can now login with your new password.';
//         } else {
//             $error = 'Failed to update password. Please try again.';
//         }
//     }
// }
// ?> -->


 <?php
require_once __DIR__ . '/includes/password_reset.php';
$passwordReset = new PasswordReset();

$success = '';
$error = '';
$token_data = null;
$token = $_GET['token'] ?? '';

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    // Validate token again for safety
    $token_data = $passwordReset->validateToken($token);
    if (!$token_data) {
        $error = "Invalid or expired token.";
    } elseif (empty($new_password) || strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $updated = $passwordReset->updatePassword($token, $new_password);
        if ($updated) {
            $success = "Password updated successfully. You can now <a href='index.php'>login</a>.";
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
} else {
    // On initial page load via GET
    if (!empty($token)) {
        $token_data = $passwordReset->validateToken($token);
        if (!$token_data) {
            $error = "Invalid or expired token.";
        }
    } else {
        $error = "Missing token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - QuickMark</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-container {
            max-width: 500px;
            margin: 60px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        .form-label {
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="reset-container">
    <h3 class="text-center mb-4">Reset Your Password</h3>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php elseif ($token_data): ?>
        <div class="alert alert-info">Hi <strong><?php echo htmlspecialchars($token_data['email']); ?></strong>, please enter your new password.</div>
    <?php endif; ?>

    <?php if ($token_data && empty($success)): ?>
        <form method="POST" action="">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="6">
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Optional: Add password visibility toggle
    const toggleVisibility = (inputId) => {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
    };
</script>

</body>
</html>
