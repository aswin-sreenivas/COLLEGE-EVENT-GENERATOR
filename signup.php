<?php
require_once 'config.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'student'; // Fixed role as student
    $department = trim($_POST['department'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (empty($department)) {
        $error = "Department is required";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email already registered";
        } else {
            // Hash password and insert user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashed_password, $role, $department);

            if ($stmt->execute()) {
                $success = "Account created successfully! Redirecting to login...";
                // Redirect after 2 seconds
                echo "<script>
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 2000);
                </script>";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account - CampusConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --ink:          #0f0e17;
  --cream:        #fffcf0;
  --amber:        #e86920;
  --amber-light:  #fdf3dc;
  --indigo:       #1e2557;
  --indigo-mid:   #5e2d8c;
  --muted:        #6b6a75;
  --border:       #e2dfd6;
  --sand:         #f4f1e8;
}

body {
  font-family: 'DM Sans', sans-serif;
  background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-mid) 100%);
  color: var(--ink);
  line-height: 1.6;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* NAV */
nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.1rem 3rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  background: transparent;
  position: relative;
  z-index: 100;
}

.logo {
  font-family: 'Fraunces', serif;
  font-weight: 700;
  font-size: 1.4rem;
  color: var(--cream);
  letter-spacing: -0.02em;
  text-decoration: none;
}
.logo span { color: var(--amber); }

/* CONTAINER */
.signup-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 2rem;
}

.signup-box {
    background: var(--cream);
    border-radius: 1.5rem;
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 480px;
}

.signup-box h2 {
    font-family: 'Fraunces', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--indigo);
    text-align: center;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.signup-box .subtitle {
    text-align: center;
    color: var(--muted);
    font-size: 0.9rem;
    margin-bottom: 2rem;
}

.alert {
    padding: 1rem 1.2rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    border-left: 3px solid #c82333;
    background: #fff5f6;
    color: #c82333;
    font-size: 0.9rem;
}

.alert.success {
    border-left-color: #27ae60;
    background: #f0fdf4;
    color: #27ae60;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--ink);
    font-size: 0.9rem;
    letter-spacing: 0.5px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    font-size: 0.95rem;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--indigo);
    box-shadow: 0 0 0 3px rgba(30, 37, 87, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.submit-btn {
    width: 100%;
    padding: 0.85rem 1.5rem;
    background: var(--indigo);
    color: var(--cream);
    border: none;
    border-radius: 2rem;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    letter-spacing: 0.5px;
    margin-top: 0.5rem;
}

.submit-btn:hover {
    background: var(--indigo-mid);
}

.footer-text {
    text-align: center;
    color: var(--muted);
    margin-top: 1.5rem;
    font-size: 0.85rem;
}

.footer-text a {
    color: var(--indigo);
    text-decoration: none;
    font-weight: 600;
}

.footer-text a:hover {
    text-decoration: underline;
}

.hidden {
    display: none;
}

@media (max-width: 600px) {
    nav { padding: 1rem 1.5rem; }
    .signup-box { padding: 2rem; }
    .signup-box h2 { font-size: 1.5rem; }
    .form-row { grid-template-columns: 1fr; }
}
</style>
</head>

<body>



<div class="signup-container">
    <div class="signup-box">
        <h2>Create Account</h2>
        <p class="subtitle">Join CampusConnect Today</p>

        <?php if($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" id="signupForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" id="department" name="department" required placeholder="e.g., Computer Science">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="••••••••">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <p class="footer-text">
            Already have an account? <a href="login.php">Sign in</a>
        </p>
    </div>
</div>

</body>
</html>