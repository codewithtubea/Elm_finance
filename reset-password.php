<?php
// reset-password.php
session_start();
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Reset Password</title></head><body>";
echo "<h1>Reset User Password</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $new_password = $_POST['password'] ?? '';
    
    if (empty($user_id) || empty($new_password)) {
        echo "<p style='color:red;'>Please fill all fields</p>";
    } else {
        try {
            // Create proper password hash
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE elm_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                echo "<p style='color:green;'>✓ Password reset for user ID $user_id</p>";
                echo "<p>New password: <strong>$new_password</strong></p>";
                echo "<p>New hash: $hashed_password</p>";
            } else {
                echo "<p style='color:red;'>User not found</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<h3>Reset Password for Existing User</h3>
<form method="POST">
    <p>User ID: <input type="number" name="user_id" value="1"></p>
    <p>New Password: <input type="text" name="password" value="Test1234"></p>
    <p><input type="submit" value="Reset Password"></p>
</form>

<hr>
<h3>Current Users:</h3>
<?php
try {
    $stmt = $pdo->query("SELECT id, username, email FROM elm_users ORDER BY id");
    $users = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href='simple-login.php'>Back to login</a></p>
</body></html>