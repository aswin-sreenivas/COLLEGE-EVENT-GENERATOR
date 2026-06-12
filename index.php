<?php
require_once 'config.php';
require_once 'functions.php';


$events   = getEvents();
$bulletins = getBulletins();

// Fetch past events for display
$past_events = $conn->query("
    SELECT e.*, 
           COUNT(DISTINCT r.id) as registrations,
           COALESCE(AVG(rv.rating), 0) as avg_rating
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    LEFT JOIN reviews rv ON e.id = rv.event_id
    WHERE e.event_date < CURDATE() AND e.status = 'approved'
    GROUP BY e.id
    ORDER BY e.event_date DESC
    LIMIT 3
")->fetch_all(MYSQLI_ASSOC);

$event_colors = ['#1e2557', '#3b2a1a', '#0e3d2f', '#3d1a3a', '#1a2a3d'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CampusConnect</title>

<!-- Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>
*  { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --ink:          #0f0e17;
  --cream:        #fffcf0;
  --amber:        #e86920;
  --amber-light:  #fdf3dc;
  --indigo:       #1e2557;
  --indigo-mid:   #2d3a8c;
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

/* ─── NAV ───────────────────────────────── */
nav {
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

.logo {
  font-family: 'Fraunces', serif;
  font-weight: 700;
  font-size: 1.4rem;
  color: var(--indigo);
  letter-spacing: -0.02em;
  text-decoration: none;
}
.logo span { color: var(--amber); }

.nav-links {
  display: flex;
  align-items: center;
  gap: 2rem;
}
.nav-links a {
  font-size: .85rem;
  font-weight: 500;
  color: var(--muted);
  text-decoration: none;
  letter-spacing: .03em;
  text-transform: uppercase;
}
.nav-links a:hover { color: var(--ink); }

.nav-btn {
  background: var(--indigo);
  color: #fff;
  padding: .5rem 1.3rem;
  border-radius: 2rem;
  font-size: .8rem;
  font-weight: 500;
  text-decoration: none;
  letter-spacing: .03em;
  transition: background .2s;
}
.nav-btn:hover { background: var(--indigo-mid); }

/* ─── HERO ───────────────────────────────── */
.hero {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 88vh;
  overflow: hidden;
}

.hero-left {
  background: var(--indigo);
  padding: 5rem 4rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
}
.hero-left::before {
  content: '';
  position: absolute;
  width: 380px; height: 380px;
  border-radius: 50%;
  border: 80px solid rgba(255,255,255,.04);
  right: -120px; top: -100px;
}
.hero-left::after {
  content: '';
  position: absolute;
  width: 240px; height: 240px;
  border-radius: 50%;
  border: 50px solid rgba(255,255,255,.04);
  left: -80px; bottom: -60px;
}

.hero-tag {
  font-size: .72rem;
  font-weight: 500;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--amber);
  border: 1px solid rgba(232,160,32,.35);
  display: inline-block;
  padding: .35rem .9rem;
  border-radius: 2rem;
  margin-bottom: 2rem;
}

.hero h1 {
  font-family: 'Fraunces', serif;
  font-size: 3.8rem;
  line-height: 1.05;
  color: #fff;
  font-weight: 700;
  letter-spacing: -.03em;
  margin-bottom: 1.5rem;
  position: relative;
  z-index: 1;
}
.hero h1 em { font-style: italic; color: var(--amber); }

.hero-sub {
  color: rgba(255,255,255,.55);
  font-size: 1rem;
  line-height: 1.7;
  max-width: 380px;
  margin-bottom: 2.5rem;
  position: relative;
  z-index: 1;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  position: relative;
  z-index: 1;
}

.btn-primary {
  background: var(--amber);
  color: var(--ink);
  padding: .75rem 1.8rem;
  border-radius: 2rem;
  font-weight: 500;
  font-size: .9rem;
  text-decoration: none;
  transition: opacity .2s;
}
.btn-primary:hover { opacity: .88; }

.btn-ghost {
  color: rgba(255,255,255,.65);
  font-size: .9rem;
  text-decoration: none;
  font-weight: 400;
  padding: .75rem 1rem;
}
.btn-ghost:hover { color: #fff; }

.hero-right {
  background: var(--amber-light);
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 3rem;
  position: relative;
  overflow: hidden;
}
.hero-right::before {
  content: '';
  position: absolute;
  inset: 0;
  background: repeating-linear-gradient(
    45deg,
    rgba(0,0,0,.025) 0px,rgba(0,0,0,.025) 1px,
    transparent 1px,transparent 18px
  );
}

.stat-row {
  display: flex;
  gap: 1.2rem;
  position: relative;
  z-index: 1;
}
.stat-box {
  background: #fff;
  border-radius: 1rem;
  padding: 1.3rem 1.4rem;
  flex: 1;
  box-shadow: 0 2px 16px rgba(0,0,0,.06);
}
.stat-num {
  font-family: 'Fraunces', serif;
  font-size: 2.2rem;
  font-weight: 700;
  color: var(--indigo);
  line-height: 1;
}
.stat-label {
  font-size: .75rem;
  color: var(--muted);
  margin-top: .3rem;
  letter-spacing: .02em;
}

.feature-tag {
  position: absolute;
  top: 2rem; right: 2rem;
  background: var(--indigo);
  color: #fff;
  padding: .55rem 1rem;
  border-radius: .75rem;
  font-size: .78rem;
  font-weight: 500;
  z-index: 1;
}

/* ─── SECTION SHARED ─────────────────────── */
section { padding: 5rem 3rem; }

.section-label {
  font-size: .72rem;
  font-weight: 500;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: var(--amber);
  margin-bottom: .6rem;
}
.section-title {
  font-family: 'Fraunces', serif;
  font-size: 2.4rem;
  font-weight: 700;
  letter-spacing: -.03em;
  color: var(--ink);
}
.section-head {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-bottom: 3rem;
}
.section-head .view-all {
  font-size: .82rem;
  color: var(--muted);
  text-decoration: none;
  border-bottom: 1px solid var(--border);
  padding-bottom: .1rem;
}
.section-head .view-all:hover { color: var(--ink); }

/* ─── BULLETINS ──────────────────────────── */
.bulletins-section { background: var(--sand); }

.bulletin-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.bulletin-card {
  background: #fff;
  border-radius: 1.25rem;
  padding: 1.8rem;
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.bulletin-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,0,0,.08);
}
.bulletin-card::after {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: linear-gradient(90deg, var(--amber), var(--indigo));
}
.b-cat {
  font-size: .7rem;
  font-weight: 500;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: .8rem;
}
.b-title {
  font-family: 'Fraunces', serif;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--ink);
  line-height: 1.3;
  margin-bottom: .7rem;
}
.b-body {
  font-size: .87rem;
  color: var(--muted);
  line-height: 1.65;
  margin-bottom: 1.2rem;
}
.b-date {
  font-size: .75rem;
  color: var(--amber);
  font-weight: 500;
}
.no-items {
  grid-column: 1 / -1;
  text-align: center;
  color: var(--muted);
  padding: 3rem 0;
  font-size: .95rem;
}

/* ─── EVENTS ─────────────────────────────── */
.event-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1.5rem;
}
.event-card {
  border: 1px solid var(--border);
  border-radius: 1.25rem;
  overflow: hidden;
  background: #fff;
  transition: transform .2s, box-shadow .2s;
  display: flex;
  flex-direction: column;
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
.event-body {
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
.progress-bar {
  height: 4px;
  background: #ece9e1;
  border-radius: 2px;
  margin-bottom: 1.3rem;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  background: var(--indigo);
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
}
.btn-reg:hover { background: var(--indigo-mid); }

/* Past Events Card Styles */
.past-event-card {
  border: 1px solid var(--border);
  border-radius: 1.25rem;
  overflow: hidden;
  background: #fff;
  transition: transform .2s, box-shadow .2s;
  display: flex;
  flex-direction: column;
  opacity: 0.85;
}
.past-event-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(0,0,0,.08);
  opacity: 1;
}
.past-event-card .event-card-top {
  background: var(--muted);
}
.past-badge {
  display: inline-block;
  background: var(--amber);
  color: var(--ink);
  font-size: 0.7rem;
  padding: 0.2rem 0.6rem;
  border-radius: 1rem;
  margin-bottom: 0.5rem;
}
.past-rating {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: var(--amber);
}
.view-all-wrap {
  text-align: center;
  margin-top: 3rem;
}
.btn-outline {
  display: inline-block;
  border: 1.5px solid var(--ink);
  color: var(--ink);
  padding: .75rem 2rem;
  border-radius: 2rem;
  font-size: .9rem;
  font-weight: 500;
  text-decoration: none;
  transition: background .2s, color .2s;
}
.btn-outline:hover { background: var(--ink); color: var(--cream); }

/* ─── FOOTER ─────────────────────────────── */
footer {
  background: var(--ink);
  color: rgba(255,255,255,.4);
  text-align: center;
  padding: 2.5rem;
  font-size: .82rem;
}
footer strong {
  color: rgba(255,255,255,.75);
  font-family: 'Fraunces', serif;
  font-style: italic;
}

/* ─── RESPONSIVE ─────────────────────────── */
@media (max-width: 900px) {
  .hero { grid-template-columns: 1fr; }
  .hero-right { display: none; }
  .bulletin-grid,
  .event-grid { grid-template-columns: 1fr 1fr; }
  nav { padding: 1rem 1.5rem; }
  section { padding: 4rem 1.5rem; }
}
@media (max-width: 600px) {
  .bulletin-grid,
  .event-grid { grid-template-columns: 1fr; }
  .hero-left { padding: 3.5rem 2rem; }
  .hero h1 { font-size: 2.8rem; }
  .stat-row { flex-direction: column; }
}
</style>
</head>
<body>

<?php include 'Dashboard.php'; ?>

<!-- ─── NAV ─────────────────────────────────────── -->
<nav>
  <a href="index.php" class="logo">Campus<span>Connect</span></a>
  <div class="nav-links">
    <a href="events.php">Events</a>
    <a href="previous_events.php">Past Events</a>
    <a href="bulletins.php">Bulletins</a>
    <a href="about.php">About</a>
   <?php if ($user): ?>

  <?php if ($user['role'] === 'student'): ?>
    <a href="student_profile.php" class="nav-btn">My Account</a>
  <?php endif; ?>

  <?php if ($user['role'] === 'organizer'): ?>
    <a href="organizer.php" class="nav-btn">Dashboard</a>
  <?php endif; ?>

  <?php if ($user['role'] === 'admin'): ?>
    <a href="admin.php" class="nav-btn">Dashboard</a>
  <?php endif; ?>

  <a href="logout.php" class="nav-btn" style="background: var(--amber); color: var(--ink);">Logout</a>

<?php else: ?>
  <a href="login.php" class="nav-btn">Sign In</a>
<?php endif; ?>
  </div>
</nav>

<!-- ─── HERO ─────────────────────────────────────── -->
<div class="hero">
  <div class="hero-left">
    <div class="hero-tag">✦ Your Campus Hub</div>
    <h1>Discover<br><em>what's</em><br>happening</h1>
    <p class="hero-sub">Tech fests, workshops, cultural nights, and competitions — all in one place for your campus life.</p>
    <div class="hero-actions">
      <a href="events.php" class="btn-primary">Explore Events</a>
      <?php if ($user && ($user['role'] === 'admin' || $user['role'] === 'organizer')): ?>
        <a href="admin.php" class="btn-ghost">Manage Events →</a>
      <?php else: ?>
        <a href="events.php" class="btn-ghost">View Schedule →</a>
      <?php endif; ?>
    </div>
  </div>
  <div class="hero-right">
    <div class="feature-tag">Live Updates ●</div>
    <div class="stat-row">
      <div class="stat-box">
        <div class="stat-num"><?php echo count($events); ?></div>
        <div class="stat-label">Active Events</div>
      </div>
      <div class="stat-box">
        <?php $total_reg = array_sum(array_column($events, 'registrations')); ?>
        <div class="stat-num"><?php echo $total_reg >= 1000 ? round($total_reg/1000,1).'k' : $total_reg; ?></div>
        <div class="stat-label">Registrations</div>
      </div>
      <div class="stat-box">
        <div class="stat-num"><?php echo count($bulletins); ?></div>
        <div class="stat-label">Updates</div>
      </div>
    </div>
  </div>
</div>

<!-- ─── BULLETINS ─────────────────────────────────── -->
<section class="bulletins-section">
  <div class="section-head">
    <div>
      <div class="section-label">Latest Updates</div>
      <div class="section-title">Campus Bulletin</div>
    </div>
    <a href="bulletins.php" class="view-all">View all updates →</a>
  </div>

  <div class="bulletin-grid">
    <?php if (empty($bulletins)): ?>
      <p class="no-items">No updates available at the moment.</p>
    <?php else: ?>
      <?php foreach ($bulletins as $b): ?>
      <div class="bulletin-card">
        <div class="b-cat">Campus</div>
        <div class="b-title"><?php echo htmlspecialchars($b['title']); ?></div>
        <div class="b-body"><?php echo substr(htmlspecialchars($b['description']), 0, 120); ?>…</div>
        <div class="b-date"><?php echo date('M d, Y', strtotime($b['created_at'])); ?></div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<!-- ─── EVENTS ─────────────────────────────────────── -->
<section>
  <div class="section-head">
    <div>
      <div class="section-label">Upcoming Events</div>
      <div class="section-title">Don't miss out</div>
    </div>
    <a href="events.php" class="view-all">See all events →</a>
  </div>

  <div class="event-grid">
    <?php if (empty($events)): ?>
      <p class="no-items">No events available right now. Check back soon!</p>
    <?php else: ?>
      <?php foreach (array_slice($events, 0, 3) as $i => $event):
        $pct = $event['capacity'] > 0 ? round(($event['registrations'] / $event['capacity']) * 100) : 0;
        $color = $event_colors[$i % count($event_colors)];
        $reg_url = ($user && $user['role'] === 'student')
          ? 'register.php?event_id=' . $event['id']
          : 'register.php';
      ?>
      <div class="event-card">
        <div class="event-card-top" style="background:<?php echo $color; ?>">
          <div class="event-cat"><?php echo strtoupper(htmlspecialchars($event['category'])); ?></div>
          <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
        </div>
        <div class="event-body">
          <p class="event-desc"><?php echo substr(htmlspecialchars($event['description']), 0, 100); ?>…</p>
          <div class="event-meta">
            <span><?php echo htmlspecialchars($event['event_date']); ?></span>
            <span><?php echo htmlspecialchars($event['venue']); ?></span>
          </div>
          <div class="capacity-label"><?php echo $event['registrations']; ?> / <?php echo $event['capacity']; ?> registered</div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?php echo $pct; ?>%;background:<?php echo $color; ?>"></div>
          </div>
          <a href="<?php echo $reg_url; ?>" class="btn-reg">Register Now</a>
        </div>
      </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="view-all-wrap">
    <a href="events.php" class="btn-outline">View All Events</a>
  </div>
</section>

<!-- ─── PAST EVENTS SECTION ───────────────────────── -->
<?php if (!empty($past_events)): ?>
<section style="background: var(--sand);">
  <div class="section-head">
    <div>
      <div class="section-label">Relive the Memories</div>
      <div class="section-title">Past Events</div>
    </div>
    <a href="previous_events.php" class="view-all">View all past events →</a>
  </div>

  <div class="event-grid">
    <?php foreach ($past_events as $i => $event):
      $color = $event_colors[$i % count($event_colors)];
      $avg_rating = round($event['avg_rating'], 1);
    ?>
      <div class="past-event-card">
        <div class="event-card-top" style="background:<?php echo $color; ?>">
          <div class="event-cat"><?php echo strtoupper(htmlspecialchars($event['category'])); ?></div>
          <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
        </div>
        <div class="event-body">
          <div class="past-badge">Past Event</div>
          <p class="event-desc"><?php echo substr(htmlspecialchars($event['description']), 0, 100); ?>…</p>
          <div class="event-meta">
            <span><?php echo htmlspecialchars($event['event_date']); ?></span>
            <span><?php echo htmlspecialchars($event['venue']); ?></span>
          </div>
          <div class="capacity-label"><?php echo $event['registrations']; ?> attendees</div>
          <?php if ($avg_rating > 0): ?>
            <div class="past-rating">
              <i class="bi bi-star-fill"></i> <?php echo $avg_rating; ?> / 5 (<?php echo $event['review_count'] ?? 0; ?> reviews)
            </div>
          <?php else: ?>
            <div class="past-rating">
              <i class="bi bi-star"></i> No reviews yet
            </div>
          <?php endif; ?>
          <a href="previous_events.php" class="btn-reg" style="background: var(--amber); color: var(--ink); margin-top: 1rem;">View Memories →</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="view-all-wrap">
    <a href="previous_events.php" class="btn-outline">Explore All Past Events</a>
  </div>
</section>
<?php endif; ?>

<!-- ─── FOOTER ─────────────────────────────────────── -->
<footer>
  <p>&copy; 2026 <strong>CampusConnect</strong> — Built with care for campus life.</p>
</footer>
</body>
</html>