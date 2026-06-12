<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();
$user = getCurrentUser();

if ($user['role'] === 'student') {
    header('Location: index.php');
    exit;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create_event') {
      $stmt = $conn->prepare("INSERT INTO events (title, description, category, event_date, event_time, venue, capacity, organizer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sssssssi", $_POST['title'], $_POST['description'], $_POST['category'], $_POST['event_date'], $_POST['event_time'], $_POST['venue'], $_POST['capacity'], $_SESSION['user_id']);
        $stmt->execute();
    } elseif ($_POST['action'] === 'update_event') {
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, capacity=? WHERE id=?");
        $stmt->bind_param("sssssssi", $_POST['title'], $_POST['description'], $_POST['category'], $_POST['event_date'], $_POST['event_time'], $_POST['venue'], $_POST['capacity'], $_POST['event_id']);
        $stmt->execute();
    } elseif ($_POST['action'] === 'delete_event') {
        $stmt = $conn->prepare("DELETE FROM events WHERE id=?");
        $stmt->bind_param("i", $_POST['event_id']);
        $stmt->execute();
    } elseif ($_POST['action'] === 'mark_as_past') {
        $stmt = $conn->prepare("UPDATE events SET is_past = 1 WHERE id=?");
        $stmt->bind_param("i", $_POST['event_id']);
        $stmt->execute();
    } elseif ($_POST['action'] === 'restore_event') {
        $stmt = $conn->prepare("UPDATE events SET is_past = 0 WHERE id=?");
        $stmt->bind_param("i", $_POST['event_id']);
        $stmt->execute();
    }
}

// Fetch all events (upcoming and past)
$upcoming_events = $conn->query("SELECT e.*, u.name as organizer_name, COUNT(r.id) as registrations 
                                FROM events e 
                                LEFT JOIN users u ON e.organizer_id = u.id 
                                LEFT JOIN registrations r ON e.id = r.event_id 
                                WHERE e.event_date >= CURDATE() OR (e.event_date < CURDATE() AND e.is_past = 0)
                                GROUP BY e.id 
                                ORDER BY e.event_date ASC")->fetch_all(MYSQLI_ASSOC);

$past_events = $conn->query("SELECT e.*, u.name as organizer_name, COUNT(r.id) as registrations,
                                    COALESCE(AVG(rv.rating), 0) as avg_rating,
                                    COUNT(DISTINCT rv.id) as review_count
                            FROM events e 
                            LEFT JOIN users u ON e.organizer_id = u.id 
                            LEFT JOIN registrations r ON e.id = r.event_id
                            LEFT JOIN reviews rv ON e.id = rv.event_id
                            WHERE e.event_date < CURDATE() OR e.is_past = 1
                            GROUP BY e.id 
                            ORDER BY e.event_date DESC")->fetch_all(MYSQLI_ASSOC);

// Filter registrations by event
$event_filter = $_GET['filter_event'] ?? '';
$registrations = $conn->query("SELECT r.id, u.name, u.email, u.department, e.title as event_title, r.registered_at 
                               FROM registrations r 
                               JOIN users u ON r.user_id = u.id 
                               JOIN events e ON r.event_id = e.id" . 
                               ($event_filter ? " WHERE e.id = $event_filter" : "") . 
                               " ORDER BY r.registered_at DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch all registered students with their events for certificate generation
$certificate_students = $conn->query("SELECT r.id, u.name, u.department, e.title as event_title, e.event_date
                                      FROM registrations r 
                                      JOIN users u ON r.user_id = u.id 
                                      JOIN events e ON r.event_id = e.id
                                      WHERE e.event_date < CURDATE() OR e.is_past = 1
                                      ORDER BY e.event_date DESC, u.name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Organizer Dashboard - CampusConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
* { margin: 0; padding: 0; box-sizing: border-box; }
:root { 
    --ink: #0f0e17; 
    --cream: #fffcf0; 
    --amber: #e86920; 
    --indigo: #1e2557; 
    --border: #e2dfd6; 
    --sand: #f4f1e8; 
    --muted: #6b6a75;
    --gold: #c9a03d;
    --teal: #2d6a6a;
}
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--ink); line-height: 1.6; }
.top-nav { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 3rem; border-bottom: 1px solid var(--border); background: var(--cream); position: sticky; top: 0; z-index: 100; }
.top-nav .logo { font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.4rem; color: var(--indigo); text-decoration: none; }
.top-nav .logo span { color: var(--amber); }
.page-title { font-family: 'Fraunces', serif; font-size: 2rem; font-weight: 700; margin: 2rem 0 1rem 0; padding: 0 3rem; }
.card-custom { background: #fff; border-radius: 1.25rem; border: 1px solid var(--border); padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 4px 20px rgba(0,0,0,.04); transition: transform 0.2s, box-shadow 0.2s; }
.card-custom:hover { box-shadow: 0 8px 30px rgba(0,0,0,.08); }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--border); }
.table th { background: var(--sand); font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
.table tr:hover { background: rgba(244,241,232,0.3); }
.btn { padding: 0.5rem 1rem; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.9rem; text-decoration: none; display: inline-block; margin: 0.2rem; transition: all 0.2s; }
.btn-primary { background: var(--indigo); color: white; }
.btn-primary:hover { background: #2d3a8c; transform: translateY(-1px); }
.btn-danger { background: #dc3545; color: white; }
.btn-danger:hover { background: #c82333; transform: translateY(-1px); }
.btn-warning { background: var(--amber); color: var(--ink); }
.btn-warning:hover { background: #d45a1a; transform: translateY(-1px); }
.btn-success { background: #28a745; color: white; }
.btn-success:hover { background: #218838; transform: translateY(-1px); }
.btn-info { background: var(--teal); color: white; }
.btn-info:hover { background: #1e5a5a; transform: translateY(-1px); }
.btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
.form-control { width: 100%; padding: 0.5rem; margin-bottom: 0.5rem; border: 1px solid var(--border); border-radius: 0.5rem; font-family: 'DM Sans', sans-serif; transition: border-color 0.2s; }
.form-control:focus { outline: none; border-color: var(--indigo); box-shadow: 0 0 0 3px rgba(30,37,87,0.1); }
.filter-bar { margin-bottom: 1rem; padding: 1rem; background: var(--sand); border-radius: 0.5rem; }
.tabs-section { padding: 0 3rem 3rem; }
.action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }
.edit-form { margin-top: 1rem; padding-top: 1rem; border-top: 2px solid var(--border); }
.past-badge { background: var(--amber); color: var(--ink); padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; display: inline-block; }
.upcoming-badge { background: #28a745; color: white; padding: 0.2rem 0.5rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; display: inline-block; }
.nav-tabs { display: flex; gap: 0.5rem; margin-bottom: 2rem; border-bottom: 2px solid var(--border); flex-wrap: wrap; }
.nav-tab { padding: 0.75rem 1.5rem; background: none; border: none; cursor: pointer; font-weight: 500; color: var(--muted); transition: all 0.2s; border-radius: 0.5rem 0.5rem 0 0; }
.nav-tab:hover { background: var(--sand); color: var(--indigo); }
.nav-tab.active { color: var(--indigo); border-bottom: 3px solid var(--indigo); background: rgba(30,37,87,0.05); }
.tab-pane { display: none; animation: fadeIn 0.3s ease; }
.tab-pane.active { display: block; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.rating-stars { color: #ffc107; font-size: 0.8rem; letter-spacing: 2px; }

/* Certificate Styles */
.certificate-section { background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo) 50%, #2d3a8c 100%); }
.certificate-preview { background: #fff; border-radius: 1rem; padding: 2rem; margin-bottom: 1.5rem; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
.certificate-select { background: rgba(255,255,255,0.95); border-radius: 1rem; padding: 1.5rem; }

#certificateContainer {
    background: white;
    width: 100%;
    max-width: 800px;
    margin: 0 auto;
    position: relative;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    border-radius: 0;
}

.certificate-actions { display: flex; gap: 1rem; justify-content: center; margin-top: 1rem; }
</style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="logo">Campus<span>Connect</span></a>
</div>

<h1 class="page-title">📋 Event Management Dashboard</h1>

<div class="tabs-section">
    <!-- Create New Event Form -->
    <div class="card-custom">
        <h2><i class="bi bi-plus-circle"></i> Create New Event</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_event">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <input class="form-control" name="title" placeholder="Event Title" required>
                <input class="form-control" name="category" placeholder="Category" required>
                <input class="form-control" type="date" name="event_date" required>
                <input class="form-control" type="time" name="event_time" required>
                <input class="form-control" name="venue" placeholder="Venue" required>
                <input class="form-control" type="number" name="capacity" placeholder="Capacity" required>
            </div>
            <textarea class="form-control" name="description" placeholder="Description" rows="3"></textarea>
            <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Create Event</button>
        </form>
    </div>

    <!-- Tabs -->
    <div class="nav-tabs">
        <button class="nav-tab active" onclick="showTab('upcoming')"><i class="bi bi-calendar-week"></i> Upcoming Events</button>
        <button class="nav-tab" onclick="showTab('past')"><i class="bi bi-clock-history"></i> Past Events</button>
        <button class="nav-tab" onclick="showTab('students')"><i class="bi bi-people"></i> Registered Students</button>
        <button class="nav-tab" onclick="showTab('certificate')"><i class="bi bi-award"></i> Create Certificate</button>
    </div>

    <!-- Upcoming Events Tab -->
    <div id="upcoming-tab" class="tab-pane active">
        <div class="card-custom">
            <h3><i class="bi bi-calendar-event"></i> Upcoming Events</h3>
            <div class="table-responsive" style="overflow-x:auto">
                <table class="table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Category</th><th>Venue</th><th>Registrations</th><th>Capacity</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_events as $event): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($event['event_date'] . ' ' . $event['event_time']); ?></td>
                            <td><span style="background: var(--amber); padding: 0.25rem 0.5rem; border-radius: 0.5rem; font-size:0.8rem;"><?php echo htmlspecialchars($event['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($event['venue']); ?></td>
                            <td><span class="upcoming-badge"><?php echo $event['registrations']; ?> registered</span></td>
                            <td><?php echo htmlspecialchars($event['capacity']); ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="toggleEdit(<?php echo $event['id']; ?>)"><i class="bi bi-pencil"></i> Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as past event?')">
                                    <input type="hidden" name="action" value="mark_as_past">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-archive"></i> Mark Past</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event permanently?')">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <tr id="edit-<?php echo $event['id']; ?>" style="display:none;">
                            <td colspan="7">
                                <div class="edit-form">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_event">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                                            <input class="form-control" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                            <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($event['description']); ?></textarea>
                                            <input class="form-control" name="category" value="<?php echo htmlspecialchars($event['category']); ?>" required>
                                            <input class="form-control" type="date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                                            <input class="form-control" type="time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                                            <input class="form-control" name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>
                                            <input class="form-control" type="number" name="capacity" value="<?php echo $event['capacity']; ?>" required>
                                        </div>
                                        <div style="margin-top: 0.5rem;">
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check-lg"></i> Save Changes</button>
                                            <button class="btn btn-danger btn-sm" type="button" onclick="toggleEdit(<?php echo $event['id']; ?>)"><i class="bi bi-x-lg"></i> Cancel</button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($upcoming_events)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--muted);"><i class="bi bi-calendar-x"></i> No upcoming events</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Past Events Tab -->
    <div id="past-tab" class="tab-pane">
        <div class="card-custom">
            <h3><i class="bi bi-archive"></i> Past Events Archive</h3>
            <div class="table-responsive" style="overflow-x:auto">
                <table class="table">
                    <thead>
                        <tr><th>Title</th><th>Date</th><th>Category</th><th>Venue</th><th>Attendees</th><th>Rating</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($past_events as $event): 
                            $avg_rating = round($event['avg_rating'], 1);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['title']); ?></strong><br><span class="past-badge"><i class="bi bi-clock-history"></i> Past Event</span></td>
                            <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                            <td><?php echo htmlspecialchars($event['category']); ?></td>
                            <td><?php echo htmlspecialchars($event['venue']); ?></td>
                            <td><?php echo $event['registrations']; ?> attendees</td>
                            <td>
                                <?php if ($avg_rating > 0): ?>
                                    <div class="rating-stars">
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php echo $i <= $avg_rating ? '★' : '☆'; ?>
                                        <?php endfor; ?>
                                        <span style="font-size:0.7rem;">(<?php echo $event['review_count']; ?>)</span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--muted);">No reviews yet</span>
                                <?php endif; ?>
                             </td>
                            <td class="action-buttons">
                                <button class="btn btn-warning btn-sm" onclick="toggleEditPast(<?php echo $event['id']; ?>)"><i class="bi bi-pencil"></i> Edit</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Restore this event to upcoming?')">
                                    <input type="hidden" name="action" value="restore_event">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-arrow-repeat"></i> Restore</button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this event permanently?')">
                                    <input type="hidden" name="action" value="delete_event">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Delete</button>
                                </form>
                             </td>
                         </tr>
                        <tr id="edit-past-<?php echo $event['id']; ?>" style="display:none;">
                            <td colspan="7">
                                <div class="edit-form">
                                    <form method="POST">
                                        <input type="hidden" name="action" value="update_event">
                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem;">
                                            <input class="form-control" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                            <textarea class="form-control" name="description" rows="2"><?php echo htmlspecialchars($event['description']); ?></textarea>
                                            <input class="form-control" name="category" value="<?php echo htmlspecialchars($event['category']); ?>" required>
                                            <input class="form-control" type="date" name="event_date" value="<?php echo $event['event_date']; ?>" required>
                                            <input class="form-control" type="time" name="event_time" value="<?php echo $event['event_time']; ?>" required>
                                            <input class="form-control" name="venue" value="<?php echo htmlspecialchars($event['venue']); ?>" required>
                                            <input class="form-control" type="number" name="capacity" value="<?php echo $event['capacity']; ?>" required>
                                        </div>
                                        <div style="margin-top: 0.5rem;">
                                            <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check-lg"></i> Save Changes</button>
                                            <button class="btn btn-danger btn-sm" type="button" onclick="toggleEditPast(<?php echo $event['id']; ?>)"><i class="bi bi-x-lg"></i> Cancel</button>
                                        </div>
                                    </form>
                                </div>
                             </td>
                         </tr>
                        <?php endforeach; ?>
                        <?php if (empty($past_events)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--muted);"><i class="bi bi-archive"></i> No past events yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Registered Students Tab -->
    <div id="students-tab" class="tab-pane">
        <div class="card-custom">
            <h3><i class="bi bi-people"></i> Registered Students</h3>
            <div class="filter-bar">
                <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <label style="font-weight: 600;"><i class="bi bi-funnel"></i> Filter by Event: </label>
                    <select name="filter_event" onchange="this.form.submit()" style="padding: 0.5rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                        <option value="">All Events</option>
                        <?php 
                        $all_events = array_merge($upcoming_events, $past_events);
                        foreach ($all_events as $event): ?>
                        <option value="<?php echo $event['id']; ?>" <?php echo ($event_filter == $event['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['title']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="table-responsive" style="overflow-x:auto">
                <table class="table">
                    <thead><tr><th>Student Name</th><th>Email</th><th>Department</th><th>Event</th><th>Registered Date</th></tr></thead>
                    <tbody>
                        <?php if (empty($registrations)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 2rem; color: var(--muted);"><i class="bi bi-person-x"></i> No registrations found</td></tr>
                        <?php else: foreach ($registrations as $reg): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['email']); ?></td>
                            <td><span class="past-badge" style="background:#e8eaff; color:var(--indigo);"><?php echo htmlspecialchars($reg['department'] ?? 'N/A'); ?></span></td>
                            <td><?php echo htmlspecialchars($reg['event_title']); ?></td>
                            <td><?php echo date('M d, Y g:i A', strtotime($reg['registered_at'])); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

  <div id="certificate-tab" class="tab-pane">
        <div class="card-custom" style="background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e8 100%);">
            <h3 style="color: #2d6a4f;"><i class="bi bi-award"></i> Create Certificate</h3>
            <p style="color: #52796f; margin-bottom: 1.5rem;">Generate participation certificates for students</p>
            
            <div style="background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #2d6a4f;"><i class="bi bi-person"></i> Student</label>
                        <select id="studentName" class="form-control" style="border-color: #c8e6d9;" onchange="updateCert()">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($certificate_students as $student): ?>
                                <option value="<?php echo htmlspecialchars($student['name']); ?>" data-dept="<?php echo htmlspecialchars($student['department'] ?? 'General'); ?>" data-event="<?php echo htmlspecialchars($student['event_title']); ?>">
                                    <?php echo htmlspecialchars($student['name']); ?> - <?php echo htmlspecialchars($student['event_title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="font-weight: 600; display: block; margin-bottom: 0.5rem; color: #2d6a4f;"><i class="bi bi-calendar"></i> Date</label>
                        <input type="text" id="certDate" class="form-control" value="<?php echo date('F j, Y'); ?>" style="border-color: #c8e6d9;">
                    </div>
                </div>
            </div>
            
            <!-- Certificate Preview -->
            <div id="certContainer" style="max-width: 700px; margin: 0 auto; background: white; border-radius: 1rem; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.1);">
                <div style="padding: 2rem; text-align: center; border: 1px solid #e0f0e0; border-radius: 1rem; background: linear-gradient(135deg, #fff 0%, #fafffa 100%);">
                    <!-- Top Border Line -->
                    <div style="width: 80px; height: 3px; background: #2d6a4f; margin: 0 auto 1rem;"></div>
                    
                    <!-- Logo -->
                    <div style="margin-bottom: 1rem;">
                        <div style="width: 50px; height: 50px; margin: 0 auto; background: #2d6a4f; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="bi bi-tree" style="font-size: 1.5rem; color: white;"></i>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h1 style="font-family: 'Fraunces', serif; font-size: 1.6rem; color: #2d6a4f; letter-spacing: 3px; margin-bottom: 0.5rem;">CERTIFICATE</h1>
                    <p style="font-size: 0.8rem; color: #6c9e7a; text-transform: uppercase; letter-spacing: 2px;">of participation</p>
                    
                    <div style="width: 60px; height: 1px; background: #c8e6d9; margin: 1rem auto;"></div>
                    
                    <!-- Body -->
                    <p style="font-size: 0.85rem; color: #52796f;">This certificate is proudly presented to</p>
                    <h2 id="certName" style="font-family: 'Fraunces', serif; font-size: 1.8rem; color: #1b5e2a; margin: 0.5rem 0;">_________</h2>
                    
                    <p style="font-size: 0.85rem; color: #52796f; margin-top: 1rem;">for successfully completing</p>
                    <h3 id="certEvent" style="font-family: 'Fraunces', serif; font-size: 1.2rem; color: #2d6a4f; margin: 0.3rem 0;">_________</h3>
                    
                    <p style="font-size: 0.85rem; color: #52796f; margin-top: 1rem;">from the Department of</p>
                    <h3 id="certDept" style="font-size: 1rem; color: #40916c; margin: 0.3rem 0;">_________</h3>
                    
                    <!-- Congrats -->
                    <div style="margin: 1.2rem 0;">
                        <p style="font-size: 0.9rem; color: #2d6a4f; font-weight: 500;">🎉 Congratulations! 🎉</p>
                    </div>
                    
                    <!-- Signatures -->
                    <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; padding-top: 1rem;">
                        <div style="text-align: center; width: 40%;">
                            <div style="width: 100%; height: 1px; background: #c8e6d9; margin-bottom: 0.3rem;"></div>
                            <p style="font-size: 0.7rem; color: #6c9e7a;">Principal</p>
                        </div>
                        <div style="text-align: center; width: 40%;">
                            <div style="width: 100%; height: 1px; background: #c8e6d9; margin-bottom: 0.3rem;"></div>
                            <p style="font-size: 0.7rem; color: #6c9e7a;" id="displayDate"><?php echo date('F j, Y'); ?></p>
                        </div>
                    </div>
                    
                    <!-- Bottom Seal -->
                    <div style="margin-top: 1rem;">
                        <i class="bi bi-check-circle" style="color: #2d6a4f; font-size: 1.2rem; opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            
            <!-- Buttons -->
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
                <button class="btn btn-primary" onclick="downloadCert()" style="background: #2d6a4f;"><i class="bi bi-download"></i> Download PNG</button>
                <button class="btn btn-success" onclick="printCert()" style="background: #40916c;"><i class="bi bi-printer"></i> Print</button>
            </div>
            <p style="text-align: center; margin-top: 1rem; font-size: 0.75rem; color: #6c9e7a;"><i class="bi bi-info-circle"></i> Select a student to generate certificate</p>
        </div>
    </div>

<script>
function showTab(tabName) {
    document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
    document.querySelectorAll('.nav-tab').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function toggleEdit(eventId) {
    var editRow = document.getElementById('edit-' + eventId);
    editRow.style.display = editRow.style.display === 'none' ? 'table-row' : 'none';
}

function toggleEditPast(eventId) {
    var editRow = document.getElementById('edit-past-' + eventId);
    editRow.style.display = editRow.style.display === 'none' ? 'table-row' : 'none';
}

function updateCert() {
    var select = document.getElementById('studentName');
    var opt = select.options[select.selectedIndex];
    
    if (select.value) {
        document.getElementById('certName').innerHTML = select.value;
        document.getElementById('certEvent').innerHTML = opt.getAttribute('data-event') || '_________';
        document.getElementById('certDept').innerHTML = opt.getAttribute('data-dept') || '_________';
    } else {
        document.getElementById('certName').innerHTML = '_________';
        document.getElementById('certEvent').innerHTML = '_________';
        document.getElementById('certDept').innerHTML = '_________';
    }
}

function downloadCert() {
    var select = document.getElementById('studentName');
    if (!select.value) { alert('Please select a student'); return; }
    
    var el = document.getElementById('certContainer');
    var original = el.style.width;
    el.style.width = '700px';
    
    html2canvas(el, { scale: 2, backgroundColor: '#ffffff' }).then(canvas => {
        var link = document.createElement('a');
        link.download = 'certificate_' + select.value.replace(/\s/g, '_') + '.png';
        link.href = canvas.toDataURL();
        link.click();
        el.style.width = original;
    });
}

function printCert() {
    var select = document.getElementById('studentName');
    if (!select.value) { alert('Please select a student'); return; }
    
    var content = document.getElementById('certContainer').innerHTML;
    var win = window.open('', '_blank');
    win.document.write(`
        <html><head><title>Certificate</title>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@400;700&family=DM+Sans&display=swap" rel="stylesheet">
        <style>
            body { margin: 0; padding: 2rem; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f9f0; font-family: 'DM Sans', sans-serif; }
            * { margin: 0; padding: 0; box-sizing: border-box; }
        </style>
        </head><body>${content}</body></html>
    `);
    win.print();
    win.close();
}

document.getElementById('certDate')?.addEventListener('input', function() {
    document.getElementById('displayDate').innerHTML = this.value || '<?php echo date("F j, Y"); ?>';
});
</script>

</body>
</html>