<?php
require_once 'config.php';
require_once 'functions.php';

$user = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
}

$category_filter = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';
$date_filter = $_GET['date'] ?? '';

$events = getEvents();

/* FILTERS */
$events = array_filter($events, function($e) use ($category_filter, $search, $date_filter) {

    if ($category_filter && $e['category'] !== $category_filter) return false;

    if ($search && stripos($e['title'] . $e['description'], $search) === false) return false;

    if ($date_filter && $e['event_date'] < $date_filter) return false;

    return true;
});

$categories = ['tech','cultural','sports','workshop','seminar'];
$event_colors = ['#1e2557', '#3b2a1a', '#0e3d2f', '#3d1a3a', '#1a2a3d'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Events - CampusConnect</title>
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

.page-title {
    font-family: 'Fraunces', serif;
    font-size: 2.2rem;
    font-weight: 700;
    color: var(--ink);
    margin: 2rem 0 2rem 0;
    padding: 0 3rem;
}

.page-content {
    padding: 0 3rem 3rem;
}

/* FILTER FORM */
.filter-form {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.2rem;
    margin-bottom: 2.5rem;
    padding: 1.8rem;
    background: #fff;
    border-radius: 1.25rem;
    border: 1px solid var(--border);
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}

.filter-form input,
.filter-form select {
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
}

.filter-form button {
    background: var(--indigo);
    color: #fff;
    border: none;
    border-radius: 2rem;
    padding: 0.75rem 1.8rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.filter-form button:hover {
    background: var(--indigo-mid);
}

/* EVENT GRID */
.event-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.event-card {
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    overflow: hidden;
    background: #fff;
    transition: transform .2s, box-shadow .2s;
    display: flex;
    flex-direction: column;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}

.event-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,.08);
}

.event-card-top {
    padding: 1.8rem;
    position: relative;
    overflow: hidden;
    background: var(--indigo);
    color: #fff;
}

.event-card-top::after {
    content: '';
    position: absolute;
    width: 120px; height: 120px;
    border-radius: 50%;
    border: 30px solid rgba(255,255,255,.06);
    right: -30px; top: -30px;
}

.event-cat {
    font-size: .68rem;
    font-weight: 500;
    letter-spacing: .12em;
    text-transform: uppercase;
    color: var(--amber);
    margin-bottom: .7rem;
    position: relative;
    z-index: 1;
}

.event-title {
    font-family: 'Fraunces', serif;
    font-size: 1.25rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.25;
    position: relative;
    z-index: 1;
}

.event-card-body {
    padding: 1.4rem;
    flex: 1;
    display: flex;
    flex-direction: column;
}

.event-desc {
    font-size: .85rem;
    color: var(--muted);
    line-height: 1.65;
    margin-bottom: 1rem;
}

.event-meta {
    font-size: .8rem;
    color: var(--ink);
    display: flex;
    flex-direction: column;
    gap: .35rem;
    margin-bottom: 1rem;
}

.event-meta span {
    display: flex;
    align-items: center;
    gap: .5rem;
}

.event-meta span::before {
    content: '';
    display: inline-block;
    width: 5px; height: 5px;
    border-radius: 50%;
    background: var(--amber);
    flex-shrink: 0;
}

.capacity-label {
    font-size: .72rem;
    color: var(--muted);
    margin-bottom: .4rem;
}

.progress-bar-custom {
    height: 4px;
    background: #ece9e1;
    border-radius: 2px;
    margin-bottom: 1.3rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    border-radius: 2px;
}

.btn-reg {
    background: var(--indigo);
    color: #fff;
    padding: .65rem 1.2rem;
    border-radius: 2rem;
    font-size: .82rem;
    font-weight: 500;
    text-decoration: none;
    display: block;
    text-align: center;
    transition: background .2s;
    margin-top: auto;
    border: none;
    cursor: pointer;
}

.btn-reg:hover { background: var(--indigo-mid); }

.btn-secondary-reg {
    background: var(--muted);
    color: #fff;
    padding: .65rem 1.2rem;
    border-radius: 2rem;
    font-size: .82rem;
    font-weight: 500;
    text-decoration: none;
    display: block;
    text-align: center;
    transition: background .2s;
    margin-top: auto;
    border: none;
    cursor: pointer;
}

.btn-secondary-reg:hover { background: var(--indigo); }

.text-muted-light {
    color: var(--muted);
    font-size: 0.9rem;
}

.no-events {
    grid-column: 1 / -1;
    text-align: center;
    color: var(--muted);
    padding: 3rem 0;
    font-size: .95rem;
}

/* REGISTERED EVENTS */
.registered-section {
    margin-top: 4rem;
    padding: 2rem;
    background: var(--sand);
    border-radius: 1.25rem;
}

.registered-section h3 {
    font-family: 'Fraunces', serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--ink);
    margin-bottom: 1.5rem;
}

.event-list {
    list-style: none;
}

.event-list li {
    padding: 1rem 1.2rem;
    background: #fff;
    border-radius: 0.75rem;
    border-left: 3px solid var(--amber);
    margin-bottom: 0.75rem;
    color: var(--ink);
}

@media (max-width: 900px) {
    .filter-form { grid-template-columns: repeat(2, 1fr); }
    .event-grid { grid-template-columns: repeat(2, 1fr); }
    .top-nav { padding: 1rem 1.5rem; }
    .page-title { padding: 0 1.5rem; }
    .page-content { padding: 0 1.5rem 3rem; }
}

@media (max-width: 600px) {
    .event-grid { grid-template-columns: 1fr; }
    .filter-form { grid-template-columns: 1fr; }
    .top-nav { padding: 0.8rem 1rem; }
    .page-title { font-size: 1.6rem; }
}
</style>
</head>

<body>



<div id="mainContent" class="main-content">
    <div class="top-nav">
        <a href="index.php" class="logo">Campus<span>Connect</span></a>
        <div style="font-size: 0.95rem; color: var(--muted);">Explore Events</div>
    </div>

    <h1 class="page-title"> Explore Events</h1>

    <div class="page-content">
        <!-- SEARCH + FILTER -->
        <form method="GET" class="filter-form">
            <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach($categories as $cat): ?>
                <option value="<?php echo $cat; ?>" <?php if($category_filter==$cat) echo 'selected'; ?>>
                <?php echo ucfirst($cat); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filter</button>
        </form>

        <!-- EVENT GRID -->
        <div class="event-grid">
            
            <?php if(empty($events)): ?>
                <p class="no-events">No events found</p>
            <?php endif; ?>

            <?php foreach($events as $i => $event): 
                $today = date('Y-m-d');
                $status = ($event['event_date'] > $today) ? "Upcoming" : (($event['event_date'] == $today) ? "Ongoing" : "Completed");
                $pct = $event['capacity'] > 0 ? round(($event['registrations'] / $event['capacity']) * 100) : 0;
                $color = $event_colors[$i % count($event_colors)];
                $reg_url = ($user && $user['role'] === 'student')
                    ? 'register.php?event_id=' . $event['id']
                    : 'login.php';
            ?>

            <div class="event-card">
                <div class="event-card-top" style="background:<?php echo $color; ?>">
                    <div class="event-cat"><?php echo strtoupper(htmlspecialchars($event['category'])); ?></div>
                    <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                </div>
                <div class="event-card-body">
                    <p class="event-desc"><?php echo substr(htmlspecialchars($event['description']), 0, 100); ?>…</p>
                    <div class="event-meta">
                        <span><?php echo htmlspecialchars($event['event_date']); ?></span>
                        <span><?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                    <div class="capacity-label"><?php echo $event['registrations']; ?> / <?php echo $event['capacity']; ?> registered</div>
                    <div class="progress-bar-custom">
                        <div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
                    </div>
                    <?php if ($user && $user['role'] === 'student'): ?>
                        <a href="register.php?event_id=<?php echo $event['id']; ?>" class="btn-reg">Register Now</a>
                    <?php elseif (!$user): ?>
                        <a href="login.php" class="btn-secondary-reg">Login to Register</a>
                    <?php else: ?>
                        <span class="text-muted-light" style="text-align: center; padding: 0.65rem;">Organizer/Admin cannot register</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>
        </div>

         
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>