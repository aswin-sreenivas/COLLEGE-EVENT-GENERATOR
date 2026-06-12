<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();
requireRole('student'); 

$msg = "";

if($_SERVER['REQUEST_METHOD']=="POST"){
    $event_id = $_POST['event_id'];
    $result = registerForEvent($_SESSION['user_id'],$event_id);

    $msg = $result['success'] ? "Registered!" : $result['message'];
}

$events = getEvents();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register for Event - CampusConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
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
  background: var(--cream);
  color: var(--ink);
  line-height: 1.6;
}

.top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 3rem;
    border-bottom: 1px solid var(--border);
    background: var(--cream);
    position: sticky;
    top: 0;
    z-index: 100;
}

.top-nav .logo {
    font-family: 'Fraunces', serif;
    font-weight: 700;
    font-size: 1.4rem;
    color: var(--indigo);
    letter-spacing: -0.02em;
    text-decoration: none;
}

.top-nav .logo span { color: var(--amber); }

.page-content {
    padding: 2rem 3rem;
    max-width: 600px;
}

.page-title {
    font-family: 'Fraunces', serif;
    font-size: 2rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 1.5rem;
}

.form-box {
    background: #fff;
    border-radius: 1.25rem;
    padding: 2rem;
    border: 1px solid var(--border);
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
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
}

.form-group select {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    font-size: 0.95rem;
    font-family: 'DM Sans', sans-serif;
}

.form-group select:focus {
    outline: none;
    border-color: var(--indigo);
    box-shadow: 0 0 0 3px rgba(30, 37, 87, 0.1);
}

.btn-submit {
    background: var(--indigo);
    color: #fff;
    padding: 0.85rem 1.8rem;
    border-radius: 2rem;
    border: none;
    font-weight: 500;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background 0.2s;
    width: 100%;
}

.btn-submit:hover { background: var(--indigo-mid); }

.message {
    margin-top: 1.5rem;
    padding: 1rem 1.2rem;
    border-radius: 0.75rem;
    border-left: 3px solid var(--amber);
    background: var(--amber-light);
    color: var(--ink);
}

@media (max-width: 900px) {
    .top-nav { padding: 1rem 1.5rem; }
    .page-content { padding: 2rem 1.5rem; }
}

@media (max-width: 600px) {
    .top-nav { padding: 0.8rem 1rem; }
    .page-content { padding: 1.5rem 1rem; }
    .page-title { font-size: 1.5rem; }
}
</style>
</head>

<body>

<?php include 'Dashboard.php'; ?>

<div id="mainContent" class="main-content">
    <div class="top-nav">
        <a href="index.php" class="logo">Campus<span>Connect</span></a>
        <div style="font-size: 0.95rem; color: var(--muted);">Register for Events</div>
    </div>

    <div class="page-content">
        <h1 class="page-title">Register for Event</h1>

        <div class="form-box">
            <form method="POST">
                <div class="form-group">
                    <label for="event_id">Select Event</label>
                    <select id="event_id" name="event_id" required>
                        <option value="">-- Choose an event --</option>
                        <?php foreach($events as $e): ?>
                        <option value="<?php echo $e['id']; ?>">
                            <?php echo htmlspecialchars($e['title']); ?> — <?php echo $e['event_date']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-submit">Register Now</button>
            </form>
        </div>

        <?php if($msg): ?>
            <div class="message"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>