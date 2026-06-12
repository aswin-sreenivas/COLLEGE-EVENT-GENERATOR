<?php
require_once 'config.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin.php");
            } elseif ($user['role'] == 'organizer') {
                header("Location: organizer.php");
            } else {
                header("Location: index.php");
            }
            exit;

        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "User not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - CampusConnect</title>
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
.top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.1rem 3rem;
            background: transparent;
            position: relative;
            z-index: 10;
        }

        .top-nav .logo {
            font-family: 'Fraunces', serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: white;
            letter-spacing: -0.02em;
            text-decoration: none;
        }

        .top-nav .logo span {
            color: var(--amber);
        }

        .nav-links {
            display: flex;
            gap: 2rem;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--amber);
        }


/* CONTAINER */
.login-container {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: 2rem;
}

.login-box {
    background: var(--cream);
    border-radius: 1.5rem;
    padding: 3rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    width: 100%;
    max-width: 420px;
}

.login-box h2 {
    font-family: 'Fraunces', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--indigo);
    text-align: center;
    margin-bottom: 0.5rem;
    letter-spacing: -0.02em;
}

.login-box .subtitle {
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

.form-group input {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    font-size: 0.95rem;
    font-family: 'DM Sans', sans-serif;
    transition: border-color 0.2s;
}

.form-group input:focus {
    outline: none;
    border-color: var(--indigo);
    box-shadow: 0 0 0 3px rgba(30, 37, 87, 0.1);
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

@media (max-width: 600px) {
    nav { padding: 1rem 1.5rem; }
    .login-box { padding: 2rem; }
    .login-box h2 { font-size: 1.5rem; }
}
</style>
</head>

<body>
    <nav class="top-nav">
    <a href="index.php" class="logo">Campus<span>Connect</span></a>
    <div class="nav-links">
        <a href="index.php"><i class="bi bi-house"></i> Home</a>
      
    </div>
</nav>



<div class="login-container">
    <div class="login-box">
        <h2>Welcome</h2>
        <p class="subtitle">Sign in to your account</p>

        <?php if($error): ?>
            <div class="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="you@example.com">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="••••••••">
            </div>

            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <p class="footer-text">
            Don't have an account? <a href="signup.php">Create one</a>
        </p>
    </div>
</div>

</body>
</html>