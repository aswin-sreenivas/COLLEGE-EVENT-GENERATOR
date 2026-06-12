<?php
require_once 'config.php';
require_once 'functions.php';

requireRole('admin');

// Add department column to users table if it doesn't exist
$check_dept = $conn->query("SHOW COLUMNS FROM users LIKE 'department'");
if ($check_dept->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN department VARCHAR(100) DEFAULT NULL");
}

// Add is_past column to events table if it doesn't exist
$check_past = $conn->query("SHOW COLUMNS FROM events LIKE 'is_past'");
if ($check_past->num_rows == 0) {
    $conn->query("ALTER TABLE events ADD COLUMN is_past TINYINT(1) DEFAULT 0");
}

// Add images column to events table if it doesn't exist
$check_images = $conn->query("SHOW COLUMNS FROM events LIKE 'images'");
if ($check_images->num_rows == 0) {
    $conn->query("ALTER TABLE events ADD COLUMN images TEXT DEFAULT NULL");
}

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/events/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ── AJAX Handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    while (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    // Create Event
    if ($action === 'create_event') {
        $title        = trim($_POST['title']       ?? '');
        $description  = trim($_POST['description'] ?? '');
        $category     = trim($_POST['category']    ?? '');
        $event_date   = trim($_POST['event_date']  ?? '');
        $event_time   = trim($_POST['event_time']  ?? '');
        $venue        = trim($_POST['venue']       ?? '');
        $capacity     = intval($_POST['capacity']  ?? 100);
        $organizer_id = $_SESSION['user_id'];
        $is_past      = isset($_POST['is_past']) ? intval($_POST['is_past']) : 0;

        if (!$title || !$description || !$category || !$event_date || !$venue) {
            echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO events (title, description, category, event_date, event_time, venue, capacity, organizer_id, status, is_past) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved', ?)");
        $stmt->bind_param("sssssssii", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $organizer_id, $is_past);
        $result = $stmt->execute();
        
        echo json_encode(['success' => $result, 'message' => $result ? 'Event created successfully.' : $conn->error, 'id' => $stmt->insert_id]);
        exit;
    }

    // Update Event
    if ($action === 'update_event') {
        $id          = intval($_POST['event_id']   ?? 0);
        $title       = trim($_POST['title']        ?? '');
        $description = trim($_POST['description']  ?? '');
        $category    = trim($_POST['category']     ?? '');
        $event_date  = trim($_POST['event_date']   ?? '');
        $event_time  = trim($_POST['event_time']   ?? '');
        $venue       = trim($_POST['venue']        ?? '');
        $capacity    = intval($_POST['capacity']   ?? 100);
        $is_past     = isset($_POST['is_past']) ? intval($_POST['is_past']) : 0;

        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid event ID.']); exit; }
        
        $stmt = $conn->prepare("UPDATE events SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, capacity=?, is_past=? WHERE id=?");
        $stmt->bind_param("sssssssii", $title, $description, $category, $event_date, $event_time, $venue, $capacity, $is_past, $id);
        $result = $stmt->execute();
        
        echo json_encode(['success' => $result, 'message' => $result ? 'Event updated successfully.' : $conn->error]);
        exit;
    }

    // Upload Images for Event
    if ($action === 'upload_images') {
        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid event ID.']);
            exit;
        }
        
        $uploaded_images = [];
        $existing_images = [];
        
        // Get existing images
        $img_query = $conn->query("SELECT images FROM events WHERE id = $event_id");
        $img_row = $img_query->fetch_assoc();
        if ($img_row['images']) {
            $existing_images = json_decode($img_row['images'], true) ?: [];
        }
        
        // Handle file uploads
        if (isset($_FILES['event_images']) && !empty($_FILES['event_images']['name'][0])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            foreach ($_FILES['event_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['event_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = basename($_FILES['event_images']['name'][$key]);
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $new_filename = 'event_' . $event_id . '_' . time() . '_' . $key . '.' . $ext;
                        $destination = $upload_dir . $new_filename;
                        if (move_uploaded_file($tmp_name, $destination)) {
                            $uploaded_images[] = 'uploads/events/' . $new_filename;
                        }
                    }
                }
            }
        }
        
        // Handle image order from reordering
        $image_order = isset($_POST['image_order']) ? json_decode($_POST['image_order'], true) : [];
        
        // Merge existing and new images
        $all_images = array_merge($existing_images, $uploaded_images);
        
        // Apply custom order if provided
        if (!empty($image_order)) {
            $ordered = [];
            foreach ($image_order as $img_path) {
                if (in_array($img_path, $all_images)) {
                    $ordered[] = $img_path;
                }
            }
            foreach ($all_images as $img) {
                if (!in_array($img, $ordered)) {
                    $ordered[] = $img;
                }
            }
            $all_images = $ordered;
        }
        
        // Update database
        $images_json = json_encode($all_images);
        $stmt = $conn->prepare("UPDATE events SET images = ? WHERE id = ?");
        $stmt->bind_param("si", $images_json, $event_id);
        $result = $stmt->execute();
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? count($uploaded_images) . ' image(s) uploaded successfully.' : $conn->error,
            'images' => $all_images
        ]);
        exit;
    }
    
    // Delete Event Image
    if ($action === 'delete_event_image') {
        $event_id = intval($_POST['event_id'] ?? 0);
        $image_path = $_POST['image_path'] ?? '';
        
        if (!$event_id || !$image_path) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        
        // Get current images
        $img_query = $conn->query("SELECT images FROM events WHERE id = $event_id");
        $img_row = $img_query->fetch_assoc();
        $images = json_decode($img_row['images'], true) ?: [];
        
        // Remove image from array
        $images = array_filter($images, function($img) use ($image_path) {
            return $img !== $image_path;
        });
        $images = array_values($images);
        
        // Delete file from server
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        // Update database
        $images_json = json_encode($images);
        $stmt = $conn->prepare("UPDATE events SET images = ? WHERE id = ?");
        $stmt->bind_param("si", $images_json, $event_id);
        $result = $stmt->execute();
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Image deleted successfully.' : $conn->error,
            'images' => $images
        ]);
        exit;
    }

    // Delete Event
    if ($action === 'delete_event') {
        $id = intval($_POST['event_id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
        
        // Get images to delete files
        $img_query = $conn->query("SELECT images FROM events WHERE id = $id");
        $img_row = $img_query->fetch_assoc();
        if ($img_row['images']) {
            $images = json_decode($img_row['images'], true) ?: [];
            foreach ($images as $img_path) {
                if (file_exists($img_path)) {
                    unlink($img_path);
                }
            }
        }
        
        $conn->query("DELETE FROM registrations WHERE event_id = $id");
        $conn->query("DELETE FROM events WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Event deleted.']);
        exit;
    }

    // Mark as Past Event
    if ($action === 'mark_as_past') {
        $id = intval($_POST['event_id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
        $stmt = $conn->prepare("UPDATE events SET is_past = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        echo json_encode(['success' => $result, 'message' => $result ? 'Event marked as past.' : $conn->error]);
        exit;
    }

    // Mark as Upcoming Event
    if ($action === 'mark_as_upcoming') {
        $id = intval($_POST['event_id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
        $stmt = $conn->prepare("UPDATE events SET is_past = 0 WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        echo json_encode(['success' => $result, 'message' => $result ? 'Event marked as upcoming.' : $conn->error]);
        exit;
    }

    // Approve Event
    if ($action === 'approve_event') {
        $id = intval($_POST['event_id'] ?? 0);
        $stmt = $conn->prepare("UPDATE events SET status = 'approved' WHERE id = ?");
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        echo json_encode(['success' => $result, 'message' => $result ? 'Event approved.' : $conn->error]);
        exit;
    }

    // Reject Event
    if ($action === 'reject_event') {
        $id = intval($_POST['event_id'] ?? 0);
        $conn->query("DELETE FROM events WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Event rejected and deleted.']);
        exit;
    }

    // Create Bulletin
    if ($action === 'create_bulletin') {
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        if (!$title || !$description) {
            echo json_encode(['success' => false, 'message' => 'Headline and content are required.']);
            exit;
        }
        $stmt = $conn->prepare("INSERT INTO bulletins (title, description, created_by, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("ssi", $title, $description, $_SESSION['user_id']);
        $result = $stmt->execute();
        echo json_encode(['success' => $result, 'message' => $result ? 'Bulletin published.' : $conn->error]);
        exit;
    }

    // Delete Bulletin
    if ($action === 'delete_bulletin') {
        $id = intval($_POST['bulletin_id'] ?? 0);
        $conn->query("DELETE FROM bulletins WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Bulletin deleted.']);
        exit;
    }

    // Delete Registration
    if ($action === 'delete_registration') {
        $id = intval($_POST['registration_id'] ?? 0);
        $conn->query("DELETE FROM registrations WHERE id = $id");
        echo json_encode(['success' => true, 'message' => 'Registration removed.']);
        exit;
    }

    // Get Single Event
    if ($action === 'get_event') {
        $id = intval($_POST['event_id'] ?? 0);
        $result = $conn->query("SELECT * FROM events WHERE id = $id");
        $event = $result->fetch_assoc();
        echo json_encode(['success' => (bool)$event, 'event' => $event]);
        exit;
    }

    // Get Registrations for an Event (with department handling)
    if ($action === 'get_registrations') {
        $event_id = intval($_POST['event_id'] ?? 0);
        
        $query = "SELECT r.id, u.name, u.email, 
                         COALESCE(u.department, 'N/A') as department, 
                         COALESCE(u.phone, '') AS phone, 
                         r.registered_at
                  FROM registrations r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.event_id = $event_id
                  ORDER BY r.registered_at DESC";
        
        $result = $conn->query($query);
        if ($result) {
            $rows = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode(['success' => true, 'registrations' => $rows]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        }
        exit;
    }

    // Search Students by Name or Email
    if ($action === 'search_students') {
        $search = trim($_POST['search'] ?? '');
        $event_id = intval($_POST['event_id'] ?? 0);
        
        $query = "SELECT r.id, u.name, u.email, 
                         COALESCE(u.department, 'N/A') as department, 
                         COALESCE(u.phone, '') AS phone, 
                         r.registered_at
                  FROM registrations r
                  JOIN users u ON r.user_id = u.id
                  WHERE r.event_id = $event_id";
        
        if ($search) {
            $query .= " AND (u.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                        OR u.email LIKE '%" . $conn->real_escape_string($search) . "%')";
        }
        
        $query .= " ORDER BY r.registered_at DESC";
        $rows = $conn->query($query)->fetch_all(MYSQLI_ASSOC);
        echo json_encode(['success' => true, 'registrations' => $rows]);
        exit;
    }

    // Export Data
    if ($action === 'export_data') {
        $events = $conn->query("SELECT * FROM events")->fetch_all(MYSQLI_ASSOC);
        $regs = $conn->query("SELECT r.*, u.name, u.email, e.title FROM registrations r JOIN users u ON r.user_id = u.id JOIN events e ON r.event_id = e.id")->fetch_all(MYSQLI_ASSOC);
        $bulletins = $conn->query("SELECT * FROM bulletins")->fetch_all(MYSQLI_ASSOC);
        header('Content-Disposition: attachment; filename="campusconnect_export.json"');
        echo json_encode(['events' => $events, 'registrations' => $regs, 'bulletins' => $bulletins, 'exported_at' => date('Y-m-d H:i:s')], JSON_PRETTY_PRINT);
        exit;
    }

    // Reset All Data
    if ($action === 'reset_data') {
        if (($_POST['confirm'] ?? '') === 'yes') {
            $conn->query("DELETE FROM registrations");
            $conn->query("DELETE FROM events");
            $conn->query("DELETE FROM bulletins");
            echo json_encode(['success' => true, 'message' => 'All data has been reset.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Confirmation required.']);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// ── Page Data ─────────────────────────────────────────────────────────────────
$stats = [
    'total_events' => $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'],
    'total_registrations' => $conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'],
    'active_events' => $conn->query("SELECT COUNT(*) as c FROM events WHERE event_date >= CURDATE() OR is_past = 0")->fetch_assoc()['c'],
    'categories' => $conn->query("SELECT COUNT(DISTINCT category) as c FROM events")->fetch_assoc()['c']
];

$events = $conn->query("SELECT e.*, 
                        (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) as registrations 
                        FROM events e ORDER BY e.event_date DESC")->fetch_all(MYSQLI_ASSOC);

$bulletins = $conn->query("SELECT * FROM bulletins ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);

// Get unique departments for filter
$departments = $conn->query("SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != ''")->fetch_all(MYSQLI_ASSOC);

$registrations = $conn->query("SELECT r.id, u.name, u.email, 
                               IFNULL(u.department, 'N/A') as department, 
                               e.title AS event_title, r.registered_at
                               FROM registrations r
                               JOIN users u ON r.user_id = u.id
                               JOIN events e ON r.event_id = e.id
                               ORDER BY r.registered_at DESC")->fetch_all(MYSQLI_ASSOC);

$pending_events = $conn->query("SELECT * FROM events WHERE status = 'pending' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — CampusConnect</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
*,*::before,*::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
    --ink: #0f0e17; --cream: #fffcf0; --amber: #e8a020; --amber-lt: #fdf3dc;
    --indigo: #1e2557; --indigo2: #2d3a8c; --muted: #6b6a75;
    --border: #e2dfd6; --sand: #f4f1e8; --danger: #c0392b; --ok: #1a6b3a;
}

body { font-family: 'DM Sans', sans-serif; background: var(--sand); color: var(--ink); line-height: 1.6; }

.main { min-height: 100vh; display: flex; flex-direction: column; }

.topbar { display: flex; align-items: center; justify-content: space-between; padding: .85rem 2.2rem; background: var(--cream); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 200; }
.tb-logo { font-family: 'Fraunces', serif; font-weight: 700; font-size: 1.2rem; color: var(--indigo); text-decoration: none; }
.tb-logo span { color: var(--amber); }

.body { padding: 1.8rem 2.2rem 3rem; flex: 1; }
.ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 1.8rem; gap: 1rem; flex-wrap: wrap; }
.ph-title { font-family: 'Fraunces', serif; font-size: 1.9rem; font-weight: 700; color: var(--ink); }
.ph-sub { font-size: .82rem; color: var(--muted); margin-top: .2rem; }

.sg { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.1rem; margin-bottom: 2rem; }
.sc { background: #fff; border-radius: 1.1rem; border: 1px solid var(--border); padding: 1.3rem 1.4rem; display: flex; align-items: center; gap: .95rem; }
.si { width: 44px; height: 44px; border-radius: .75rem; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
.si.in { background: rgba(30,37,87,.1); color: var(--indigo); }
.si.am { background: rgba(232,160,32,.15); color: #9a6800; }
.si.ok { background: rgba(26,107,58,.1); color: var(--ok); }
.si.mu { background: var(--sand); color: var(--muted); }
.sn { font-family: 'Fraunces', serif; font-size: 1.8rem; font-weight: 700; line-height: 1; }
.sl { font-size: .72rem; color: var(--muted); text-transform: uppercase; margin-top: .2rem; }

.tabnav { display: flex; border-bottom: 2px solid var(--border); background: #fff; border-radius: 1.1rem 1.1rem 0 0; overflow-x: auto; }
.tnb { padding: .85rem 1.35rem; border: none; background: none; font-weight: 500; font-size: .86rem; color: var(--muted); cursor: pointer; border-bottom: 3px solid transparent; display: flex; align-items: center; gap: .45rem; white-space: nowrap; }
.tnb.on { color: var(--indigo); border-bottom-color: var(--indigo); }
.tc { background: rgba(0,0,0,.06); border-radius: 2rem; font-size: .7rem; padding: .1rem .45rem; }
.tp { display: none; }
.tp.on { display: block; }

.panel { background: #fff; border-radius: 0 0 1.1rem 1.1rem; border: 1px solid var(--border); border-top: none; overflow: hidden; }
.phd { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--border); }
.ptitle { font-family: 'Fraunces', serif; font-size: 1.05rem; font-weight: 700; }

.adg { position: relative; display: inline-block; }
.adg-btn { background: var(--indigo); color: #fff; border: none; padding: .48rem 1rem; border-radius: 2rem; font-size: .8rem; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: .35rem; }
.adg-btn:hover { background: var(--indigo2); }
.adg-menu { display: none; position: absolute; right: 0; top: calc(100% + .35rem); background: #fff; border: 1px solid var(--border); border-radius: .8rem; min-width: 160px; box-shadow: 0 8px 24px rgba(0,0,0,.1); z-index: 500; overflow: hidden; }
.adg-menu.op { display: block; }
.adg-it { display: flex; align-items: center; gap: .55rem; padding: .65rem 1rem; font-size: .84rem; font-weight: 500; color: var(--ink); cursor: pointer; width: 100%; text-align: left; background: none; border: none; }
.adg-it:hover { background: var(--sand); }
.adg-it.red { color: var(--danger); }

.dt { width: 100%; border-collapse: collapse; font-size: .87rem; }
.dt th { background: var(--sand); padding: .8rem 1.2rem; text-align: left; font-weight: 600; font-size: .72rem; text-transform: uppercase; color: var(--muted); border-bottom: 1px solid var(--border); }
.dt td { padding: .85rem 1.2rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
.bcat { display: inline-block; background: var(--amber-lt); color: #7a4d00; padding: .18rem .65rem; border-radius: 2rem; font-size: .73rem; font-weight: 600; }
.bpast { background: #e2e3e5; color: #383d41; }
.bpending { background: #fff3cd; color: #856404; }
.dept-badge { display: inline-block; background: #e8eaff; color: var(--indigo); padding: .2rem .6rem; border-radius: 1rem; font-size: .7rem; font-weight: 500; }

.rb { padding: .28rem .7rem; border-radius: 2rem; font-size: .75rem; font-weight: 500; border: 1.5px solid; cursor: pointer; background: none; display: inline-flex; align-items: center; gap: .28rem; }
.rb.out { border-color: var(--indigo); color: var(--indigo); }
.rb.out:hover { background: var(--indigo); color: #fff; }
.rb.red { border-color: var(--danger); color: var(--danger); }
.rb.red:hover { background: var(--danger); color: #fff; }
.rb.grn { border-color: var(--ok); color: var(--ok); }
.rb.grn:hover { background: var(--ok); color: #fff; }

.erow td { text-align: center; padding: 2.5rem; color: var(--muted); }

.modal-content { border-radius: 1.1rem !important; }
.modal-header { background: var(--indigo) !important; border: none !important; }
.modal-title { color: #fff !important; }
.form-control, .form-select { border-radius: .7rem !important; border: 1.5px solid var(--border) !important; }
.btn-save { background: var(--indigo); color: #fff; border: none; padding: .65rem 1.7rem; border-radius: 2rem; }
.btn-cancel { background: none; color: var(--muted); border: 1.5px solid var(--border); padding: .65rem 1.4rem; border-radius: 2rem; }

#tbox { position: fixed; top: 1.2rem; right: 1.2rem; z-index: 9999; display: flex; flex-direction: column; gap: .5rem; }
.ti { background: #fff; border: 1px solid var(--border); border-radius: .8rem; padding: .8rem 1.2rem; display: flex; align-items: center; gap: .65rem; font-size: .86rem; font-weight: 500; animation: slideIn .2s ease; }
@keyframes slideIn { from { opacity: 0; transform: translateX(12px); } to { opacity: 1; transform: none; } }
.ti.ok { border-left: 4px solid var(--ok); }
.ti.err { border-left: 4px solid var(--danger); }

.stbl { width: 100%; border-collapse: collapse; font-size: .86rem; }
.stbl th { background: var(--sand); padding: .7rem 1rem; text-align: left; font-weight: 600; font-size: .72rem; text-transform: uppercase; }
.stbl td { padding: .78rem 1rem; border-bottom: 1px solid var(--border); }

.filter-bar { background: var(--sand); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
.filter-group { display: flex; align-items: center; gap: 0.5rem; }
.filter-group label { font-weight: 600; font-size: 0.8rem; color: var(--indigo); }
.filter-group select, .filter-group input { padding: 0.4rem 0.8rem; border: 1px solid var(--border); border-radius: 0.5rem; font-size: 0.8rem; }
.filter-group button { padding: 0.4rem 1rem; background: var(--indigo); color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-size: 0.8rem; }
.filter-group button:hover { background: var(--indigo2); }

.set-card { background: #fff; border: 1px solid var(--border); border-radius: 1.1rem; padding: 1.5rem; margin-bottom: 1rem; }

/* Image Gallery Styles */
.image-gallery { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; }
.gallery-item { position: relative; width: 120px; height: 120px; border-radius: 0.75rem; overflow: hidden; border: 2px solid var(--border); cursor: move; }
.gallery-item img { width: 100%; height: 100%; object-fit: cover; }
.gallery-item .delete-img { position: absolute; top: 5px; right: 5px; background: rgba(192,57,43,0.9); border: none; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 12px; transition: all 0.2s; }
.gallery-item .delete-img:hover { background: var(--danger); transform: scale(1.1); }
.gallery-item.dragging { opacity: 0.5; }
.image-upload-area { border: 2px dashed var(--border); border-radius: 0.75rem; padding: 1rem; text-align: center; background: var(--sand); cursor: pointer; transition: all 0.2s; }
.image-upload-area:hover { border-color: var(--indigo); background: #e8eaff; }
.image-upload-area i { font-size: 2rem; color: var(--muted); }
.image-upload-area p { margin: 0; font-size: 0.85rem; color: var(--muted); }

@media (max-width: 768px) {
    .body { padding: 1.2rem 1rem 2rem; }
    .sg { grid-template-columns: repeat(2,1fr); }
    .filter-bar { flex-direction: column; align-items: stretch; }
}
</style>
</head>
<body>

<div id="tbox"></div>

<div class="main" id="main">
    <header class="topbar">
        <a href="index.php" class="tb-logo">Campus<span>Connect</span></a>
    </header>

    <div class="body">
        <div class="ph">
            <div>
                <div class="ph-title">Event Management</div>
                <div class="ph-sub">Manage events, bulletins and student registrations</div>
            </div>
            <div class="adg" id="ag0">
                <button class="adg-btn" onclick="adgT('ag0')">
                    <i class="bi bi-plus-lg"></i> Create New <i class="bi bi-chevron-down"></i>
                </button>
                <div class="adg-menu" id="ag0-menu">
                    <button class="adg-it" onclick="openEvModal(); adgC('ag0')"><i class="bi bi-calendar-plus"></i> New Event</button>
                    <button class="adg-it" onclick="openBullModal(); adgC('ag0')"><i class="bi bi-newspaper"></i> New Bulletin</button>
                </div>
            </div>
        </div>

        <div class="sg">
            <div class="sc"><div class="si in"><i class="bi bi-calendar-week"></i></div><div><div class="sn"><?php echo $stats['total_events']; ?></div><div class="sl">Total Events</div></div></div>
            <div class="sc"><div class="si am"><i class="bi bi-people-fill"></i></div><div><div class="sn"><?php echo $stats['total_registrations']; ?></div><div class="sl">Registrations</div></div></div>
            <div class="sc"><div class="si ok"><i class="bi bi-lightning-charge-fill"></i></div><div><div class="sn"><?php echo $stats['active_events']; ?></div><div class="sl">Active Events</div></div></div>
            <div class="sc"><div class="si mu"><i class="bi bi-grid-3x3-gap"></i></div><div><div class="sn"><?php echo $stats['categories']; ?></div><div class="sl">Categories</div></div></div>
        </div>

        <div class="tabnav">
            <button class="tnb on" data-tab="ev" onclick="switchTab('ev')"><i class="bi bi-calendar3"></i> Events <span class="tc"><?php echo count($events); ?></span></button>
            <button class="tnb" data-tab="reg" onclick="switchTab('reg')"><i class="bi bi-people"></i> Registrations <span class="tc"><?php echo count($registrations); ?></span></button>
            <button class="tnb" data-tab="bull" onclick="switchTab('bull')"><i class="bi bi-newspaper"></i> Bulletins <span class="tc"><?php echo count($bulletins); ?></span></button>
            <button class="tnb" data-tab="set" onclick="switchTab('set')"><i class="bi bi-gear"></i> Settings</button>
        </div>

        <!-- EVENTS TAB -->
        <div class="tp on" id="tab-ev">
            <div class="panel">
                <div class="phd"><span class="ptitle">All Events</span><button class="adg-btn" onclick="openEvModal()"><i class="bi bi-plus-lg"></i> Add Event</button></div>
                <div style="overflow-x:auto">
                    <table class="dt">
                        <thead><tr><th>Title</th><th>Date</th><th>Category</th><th>Venue</th><th>Reg / Cap</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr class="erow"><td colspan="7">No events yet.<?php else: foreach ($events as $ev): $pct = $ev['capacity'] > 0 ? min(100, round($ev['registrations'] / $ev['capacity'] * 100)) : 0; ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($ev['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ev['event_date']); ?></td>
                                    <td><span class="bcat"><?php echo htmlspecialchars($ev['category']); ?></span></td>
                                    <td><?php echo htmlspecialchars($ev['venue']); ?></td>
                                    <td><?php echo $ev['registrations']; ?> / <?php echo $ev['capacity']; ?><div class="progress mt-1" style="height:4px"><div class="progress-bar" style="width:<?php echo $pct; ?>%;background:var(--indigo)"></div></div></td>
                                    <td><?php echo ($ev['is_past'] ?? 0) == 1 ? '<span class="bcat bpast"><i class="bi bi-clock-history"></i> Past</span>' : '<span class="bcat"><i class="bi bi-calendar-check"></i> Upcoming</span>'; ?></td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="rb grn" onclick="viewStudents(<?php echo $ev['id']; ?>, '<?php echo addslashes($ev['title']); ?>')"><i class="bi bi-people"></i> Students</button>
                                            <button class="rb out" onclick="manageImages(<?php echo $ev['id']; ?>, '<?php echo addslashes($ev['title']); ?>')"><i class="bi bi-images"></i> Images</button>
                                            <div class="adg" id="ag-<?php echo $ev['id']; ?>">
                                                <button class="rb out" onclick="adgT('ag-<?php echo $ev['id']; ?>')"><i class="bi bi-three-dots"></i></button>
                                                <div class="adg-menu" id="ag-<?php echo $ev['id']; ?>-menu">
                                                    <button class="adg-it" onclick="editEv(<?php echo $ev['id']; ?>); adgC('ag-<?php echo $ev['id']; ?>')"><i class="bi bi-pencil"></i> Edit</button>
                                                    <?php if (($ev['is_past'] ?? 0) == 0): ?>
                                                    <button class="adg-it" onclick="markAsPast(<?php echo $ev['id']; ?>); adgC('ag-<?php echo $ev['id']; ?>')"><i class="bi bi-archive"></i> Mark as Past</button>
                                                    <?php else: ?>
                                                    <button class="adg-it" onclick="markAsUpcoming(<?php echo $ev['id']; ?>); adgC('ag-<?php echo $ev['id']; ?>')"><i class="bi bi-calendar"></i> Mark as Upcoming</button>
                                                    <?php endif; ?>
                                                    <button class="adg-it red" onclick="deleteEv(<?php echo $ev['id']; ?>); adgC('ag-<?php echo $ev['id']; ?>')"><i class="bi bi-trash3"></i> Delete</button>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if (!empty($pending_events)): ?>
            <div class="mt-4"><h5><i class="bi bi-hourglass-split"></i> Pending Approvals</h5></div>
            <div class="panel">
                <table class="dt">
                    <thead><tr><th>Title</th><th>Date</th><th>Category</th><th>Venue</th><th>Actions</th></tr></thead>
                    <tbody><?php foreach ($pending_events as $pe): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pe['title']); ?></td>
                            <td><?php echo htmlspecialchars($pe['event_date']); ?></td>
                            <td><span class="bpending"><?php echo htmlspecialchars($pe['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($pe['venue']); ?></td>
                            <td><button class="rb grn" onclick="approveEvent(<?php echo $pe['id']; ?>)">Approve</button> <button class="rb red" onclick="rejectEvent(<?php echo $pe['id']; ?>)">Reject</button></td>
                        </tr>
                    <?php endforeach; ?></tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- REGISTRATIONS TAB -->
        <div class="tp" id="tab-reg">
            <div class="panel">
                <div class="phd"><span class="ptitle">All Registrations</span></div>
                <table class="dt">
                    <thead><tr><th>Student</th><th>Department</th><th>Email</th><th>Event</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody><?php if (empty($registrations)): ?><tr class="erow"><td colspan="6">No registrations.<?php else: foreach ($registrations as $r): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['name']); ?></td>
                            <td><span class="dept-badge"><?php echo htmlspecialchars($r['department']); ?></span></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['event_title']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($r['registered_at'])); ?></td>
                            <td><button class="rb red" onclick="removeReg(<?php echo $r['id']; ?>, this)"><i class="bi bi-person-dash"></i> Remove</button></td>
                        </tr>
                    <?php endforeach; endif; ?></tbody>
                </table>
            </div>
        </div>

        <!-- BULLETINS TAB -->
        <div class="tp" id="tab-bull">
            <div class="panel">
                <div class="phd"><span class="ptitle">Bulletins</span><button class="adg-btn" onclick="openBullModal()"><i class="bi bi-plus-lg"></i> Add Bulletin</button></div>
                <table class="dt">
                    <thead><tr><th>Headline</th><th>Preview</th><th>Created</th><th>Action</th></tr></thead>
                    <tbody><?php if (empty($bulletins)): ?><tr class="erow"><td colspan="4">No bulletins.<?php else: foreach ($bulletins as $b): ?>
                        <tr>
                            <td class="fw-bold"><?php echo htmlspecialchars($b['title']); ?></td>
                            <td><?php echo substr(htmlspecialchars($b['description']), 0, 60); ?>…</td>
                            <td><?php echo date('M d, Y', strtotime($b['created_at'])); ?></td>
                            <td><button class="rb red" onclick="removeBull(<?php echo $b['id']; ?>, this)"><i class="bi bi-trash3"></i> Delete</button></td>
                        </tr>
                    <?php endforeach; endif; ?></tbody>
                </table>
            </div>
        </div>

        <!-- SETTINGS TAB -->
        <div class="tp" id="tab-set">
            <div class="set-card"><h5>Export Data</h5><p>Download JSON snapshot.</p><form method="POST"><input type="hidden" name="action" value="export_data"><button type="submit" class="rb out">Export JSON</button></form></div>
            <div class="set-card"><h5>Reset All Data</h5><p>Delete everything. Cannot be undone.</p><button class="rb red" onclick="resetAll()">Reset Everything</button></div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div class="modal fade" id="evModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="evModalTitle">Create Event</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <input type="text" class="form-control mb-2" id="f-title" placeholder="Event Title">
    <div class="row"><div class="col"><input type="date" class="form-control" id="f-date"></div><div class="col"><input type="time" class="form-control" id="f-time"></div></div>
    <div class="row mt-2"><div class="col"><select class="form-select" id="f-cat"><option value="">Category</option><option>Tech</option><option>Cultural</option><option>Sports</option><option>Workshop</option></select></div><div class="col"><input type="number" class="form-control" id="f-cap" placeholder="Capacity" value="100"></div></div>
    <input type="text" class="form-control mt-2" id="f-venue" placeholder="Venue">
    <textarea class="form-control mt-2" id="f-desc" rows="3" placeholder="Description"></textarea>
    <div class="form-check mt-2"><input type="checkbox" class="form-check-input" id="f-is_past"><label class="form-check-label">Mark as Past Event</label></div>
    <div class="text-danger mt-2" id="ev-err"></div>
</div><div class="modal-footer"><button class="btn-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-save" onclick="submitEv()">Save Event</button></div></div></div></div>

<!-- Image Management Modal -->
<div class="modal fade" id="imageModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="imageModalTitle">Manage Images</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <div id="image-gallery-container" class="image-gallery" style="min-height:150px"></div>
    <form id="imageUploadForm" enctype="multipart/form-data" style="margin-top:1rem">
        <input type="hidden" id="img-event-id" name="event_id">
        <div class="image-upload-area" onclick="document.getElementById('image-file-input').click()">
            <i class="bi bi-cloud-upload"></i>
            <p>Click to upload images (JPG, PNG, GIF, WebP)</p>
            <input type="file" id="image-file-input" name="event_images[]" multiple accept="image/*" style="display:none" onchange="uploadImages()">
        </div>
        <div class="mt-2 text-muted small"><i class="bi bi-arrows-move"></i> Drag to reorder images</div>
    </form>
    <div class="text-danger mt-2" id="img-err"></div>
</div><div class="modal-footer"><button class="btn-cancel" data-bs-dismiss="modal">Close</button></div></div></div></div>

<div class="modal fade" id="bullModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Publish Bulletin</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body">
    <input type="text" class="form-control mb-2" id="b-title" placeholder="Headline">
    <textarea class="form-control" id="b-desc" rows="4" placeholder="Content"></textarea>
    <div class="text-danger mt-2" id="bull-err"></div>
</div><div class="modal-footer"><button class="btn-cancel" data-bs-dismiss="modal">Cancel</button><button class="btn-save" onclick="submitBull()">Publish</button></div></div></div></div>

<!-- STUDENTS MODAL -->
<div class="modal fade" id="stuModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="stu-title">Registered Students</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="filter-bar">
                    <div class="filter-group">
                        <label><i class="bi bi-search"></i> Search:</label>
                        <input type="text" id="search-input" placeholder="Name or Email..." onkeyup="searchStudents()">
                        <button onclick="searchStudents()"><i class="bi bi-search"></i> Go</button>
                        <button onclick="clearFilters()"><i class="bi bi-eraser"></i> Clear</button>
                    </div>
                </div>
                
                <table class="stbl">
                    <thead>
                        <tr><th>#</th><th>Name</th><th>Department</th><th>Email</th><th>Phone</th><th>Registered</th><th>Action</th></tr>
                    </thead>
                    <tbody id="stu-body">
                        <tr><td colspan="7" class="text-center p-4">Click "Students" button to view registered students...</td></tr>
                    </tbody>
                </table>
                
                <div class="p-3 text-end border-top">
                    <button class="rb out" onclick="exportStudentList()"><i class="bi bi-download"></i> Export List (CSV)</button>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const evModal = new bootstrap.Modal(document.getElementById('evModal'));
const bullModal = new bootstrap.Modal(document.getElementById('bullModal'));
const stuModal = new bootstrap.Modal(document.getElementById('stuModal'));
const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));

let editEvId = null;
let currentEventId = null;
let currentEventTitle = null;
let currentStudentsData = [];
let currentImageSortable = null;
let currentEventImages = [];

function switchTab(id) {
    document.querySelectorAll('.tnb').forEach(b => b.classList.toggle('on', b.dataset.tab === id));
    document.querySelectorAll('.tp').forEach(p => p.classList.toggle('on', p.id === 'tab-' + id));
}

function adgT(id) { const m = document.getElementById(id+'-menu'); document.querySelectorAll('.adg-menu.op').forEach(x => {if(x!==m) x.classList.remove('op');}); m.classList.toggle('op'); }
function adgC(id) { document.getElementById(id+'-menu').classList.remove('op'); }
document.addEventListener('click', e => { if(!e.target.closest('.adg')) document.querySelectorAll('.adg-menu.op').forEach(m => m.classList.remove('op')); });

function toast(msg, type) { const el = document.createElement('div'); el.className = 'ti '+(type||'ok'); el.innerHTML = `<i class="bi ${type==='err'?'bi-x-circle-fill':'bi-check-circle-fill'}"></i><span>${msg}</span>`; document.getElementById('tbox').appendChild(el); setTimeout(()=>el.remove(),3500); }

async function post(params) { const body = new URLSearchParams(); Object.entries(params).forEach(([k,v])=>body.append(k,v??'')); const res = await fetch(window.location.href, {method:'POST',body}); try { return await res.json(); } catch { return {success:false, message:'Server error'}; } }

function openEvModal() { editEvId=null; document.getElementById('evModalTitle').innerText='Create Event'; ['f-title','f-date','f-time','f-venue','f-desc'].forEach(id=>document.getElementById(id).value=''); document.getElementById('f-cat').value=''; document.getElementById('f-cap').value='100'; document.getElementById('f-is_past').checked=false; evModal.show(); }

async function editEv(id) { const d = await post({action:'get_event',event_id:id}); if(!d.success||!d.event) {toast('Could not load event','err'); return;} const ev=d.event; editEvId=id; document.getElementById('evModalTitle').innerText='Edit Event'; document.getElementById('f-title').value=ev.title||''; document.getElementById('f-date').value=ev.event_date||''; document.getElementById('f-time').value=ev.event_time||''; document.getElementById('f-cat').value=ev.category||''; document.getElementById('f-cap').value=ev.capacity||100; document.getElementById('f-venue').value=ev.venue||''; document.getElementById('f-desc').value=ev.description||''; document.getElementById('f-is_past').checked=ev.is_past==1; evModal.show(); }

async function submitEv() { const title=document.getElementById('f-title').value.trim(), date=document.getElementById('f-date').value.trim(), cat=document.getElementById('f-cat').value, venue=document.getElementById('f-venue').value.trim(), desc=document.getElementById('f-desc').value.trim(); if(!title||!date||!cat||!venue||!desc) { document.getElementById('ev-err').innerText='Please fill all required fields.'; return; } document.getElementById('ev-err').innerText=''; const params={action:editEvId?'update_event':'create_event',title,description:desc,category:cat,event_date:date,event_time:document.getElementById('f-time').value.trim(),venue,capacity:document.getElementById('f-cap').value,is_past:document.getElementById('f-is_past').checked?1:0}; if(editEvId) params.event_id=editEvId; const d=await post(params); if(d.success) { toast(d.message,'ok'); evModal.hide(); setTimeout(()=>location.reload(),800); } else { document.getElementById('ev-err').innerText=d.message||'Error saving event.'; } }

async function deleteEv(id) { if(!confirm('Delete this event?')) return; const d=await post({action:'delete_event',event_id:id}); if(d.success) { toast(d.message,'ok'); setTimeout(()=>location.reload(),700); } else toast(d.message,'err'); }

async function markAsPast(id) { if(!confirm('Mark as past event?')) return; const d=await post({action:'mark_as_past',event_id:id}); if(d.success) { toast(d.message,'ok'); setTimeout(()=>location.reload(),700); } }

async function markAsUpcoming(id) { if(!confirm('Mark as upcoming event?')) return; const d=await post({action:'mark_as_upcoming',event_id:id}); if(d.success) { toast(d.message,'ok'); setTimeout(()=>location.reload(),700); } }

async function approveEvent(id) { const d=await post({action:'approve_event',event_id:id}); if(d.success) { toast('Event approved','ok'); setTimeout(()=>location.reload(),700); } }

async function rejectEvent(id) { if(!confirm('Reject this event?')) return; const d=await post({action:'reject_event',event_id:id}); if(d.success) { toast('Event rejected','ok'); setTimeout(()=>location.reload(),700); } }

function openBullModal() { document.getElementById('b-title').value=''; document.getElementById('b-desc').value=''; bullModal.show(); }

async function submitBull() { const title=document.getElementById('b-title').value.trim(), desc=document.getElementById('b-desc').value.trim(); if(!title||!desc) { document.getElementById('bull-err').innerText='Both fields required.'; return; } const d=await post({action:'create_bulletin',title,description:desc}); if(d.success) { toast('Bulletin published','ok'); bullModal.hide(); setTimeout(()=>location.reload(),800); } else { document.getElementById('bull-err').innerText=d.message; } }

async function removeBull(id,btn) { if(!confirm('Delete bulletin?')) return; const d=await post({action:'delete_bulletin',bulletin_id:id}); if(d.success) { toast('Deleted','ok'); btn.closest('tr').remove(); } }

async function removeReg(id,btn) { 
    if(!confirm('Remove registration?')) return; 
    
    const d = await post({
        action:'delete_registration',
        registration_id:id
    });

    if(d.success) { 
        toast('Removed','ok');

        if(btn.closest('tr')) {
            btn.closest('tr').remove();
        }

        currentStudentsData = currentStudentsData.filter(s => s.id != id);

        if(currentStudentsData.length === 0){
            document.getElementById('stu-body').innerHTML =
            '<tr><td colspan="7" class="text-center p-4"><i class="bi bi-people"></i> No students registered for this event yet.</td></tr>';
        }
    }
}
// IMAGE MANAGEMENT FUNCTIONS
async function manageImages(eventId, eventTitle) {
    currentEventId = eventId;
    document.getElementById('imageModalTitle').innerText = 'Manage Images - ' + eventTitle;
    document.getElementById('img-event-id').value = eventId;
    document.getElementById('image-gallery-container').innerHTML = '<div class="text-center p-4">Loading images...</div>';
    imageModal.show();
    
    const d = await post({action:'get_event', event_id: eventId});
    if(d.success && d.event && d.event.images) {
        try {
            currentEventImages = JSON.parse(d.event.images) || [];
        } catch(e) { currentEventImages = []; }
    } else {
        currentEventImages = [];
    }
    renderImageGallery();
}

function renderImageGallery() {
    const container = document.getElementById('image-gallery-container');
    if(!currentEventImages.length) {
        container.innerHTML = '<div class="text-center p-4 text-muted"><i class="bi bi-images" style="font-size:2rem"></i><p>No images uploaded yet.</p></div>';
        return;
    }
    
    container.innerHTML = currentEventImages.map((img, idx) => `
        <div class="gallery-item" data-idx="${idx}" data-path="${img}">
            <img src="${img}" alt="Event image ${idx+1}" onerror="this.src='https://placehold.co/120x120?text=No+Image'">
            <button class="delete-img" onclick="deleteImage('${img.replace(/'/g, "\\'")}', event)"><i class="bi bi-x"></i></button>
        </div>
    `).join('');
    
    if(currentImageSortable) currentImageSortable.destroy();
    currentImageSortable = new Sortable(container, {
        animation: 300,
        onEnd: function() { saveImageOrder(); }
    });
}

async function saveImageOrder() {
    const items = document.querySelectorAll('.gallery-item');
    const newOrder = Array.from(items).map(item => item.dataset.path);
    if(JSON.stringify(newOrder) === JSON.stringify(currentEventImages)) return;
    
    currentEventImages = newOrder;
    const formData = new FormData();
    formData.append('action', 'upload_images');
    formData.append('event_id', currentEventId);
    formData.append('image_order', JSON.stringify(newOrder));
    
    const res = await fetch(window.location.href, {method:'POST', body:formData});
    const data = await res.json();
    if(data.success) {
        toast('Image order saved', 'ok');
    } else {
        toast('Failed to save order', 'err');
    }
}

async function uploadImages() {
    const fileInput = document.getElementById('image-file-input');
    if(!fileInput.files.length) return;
    
    const formData = new FormData();
    formData.append('action', 'upload_images');
    formData.append('event_id', currentEventId);
    for(let i = 0; i < fileInput.files.length; i++) {
        formData.append('event_images[]', fileInput.files[i]);
    }
    
    const res = await fetch(window.location.href, {method:'POST', body:formData});
    const data = await res.json();
    if(data.success) {
        currentEventImages = data.images || [];
        renderImageGallery();
        toast(data.message, 'ok');
        fileInput.value = '';
    } else {
        toast(data.message || 'Upload failed', 'err');
    }
}

async function deleteImage(imagePath, event) {
    event.stopPropagation();
    if(!confirm('Delete this image?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_event_image');
    formData.append('event_id', currentEventId);
    formData.append('image_path', imagePath);
    
    const res = await fetch(window.location.href, {method:'POST', body:formData});
    const data = await res.json();
    if(data.success) {
        currentEventImages = data.images || [];
        renderImageGallery();
        toast('Image deleted', 'ok');
    } else {
        toast('Delete failed', 'err');
    }
}

// STUDENT MANAGEMENT FUNCTIONS - FIXED
async function viewStudents(eventId, title) {
    currentEventId = eventId;
    currentEventTitle = title;
    document.getElementById('stu-title').innerText = 'Registered Students - ' + title;
    document.getElementById('stu-body').innerHTML = '<tr><td colspan="7" class="text-center p-4"><i class="bi bi-hourglass-split"></i> Loading students...</td></tr>';
    stuModal.show();
    
    try {
        const d = await post({action: 'get_registrations', event_id: eventId});
        console.log('Response:', d);
        
        if (!d.success) {
            document.getElementById('stu-body').innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">Failed to load students: ' + (d.message || 'Unknown error') + '</td></tr>';
            currentStudentsData = [];
            return;
        }
        
        if (!d.registrations || d.registrations.length === 0) {
            document.getElementById('stu-body').innerHTML = '<tr><td colspan="7" class="text-center p-4"><i class="bi bi-people"></i> No students registered for this event yet.</td></tr>';
            currentStudentsData = [];
            return;
        }
        
        currentStudentsData = d.registrations;
        document.getElementById('search-input').value = '';
        displayStudents(currentStudentsData);
        toast('Found ' + currentStudentsData.length + ' registered student(s)', 'ok');
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('stu-body').innerHTML = '<tr><td colspan="7" class="text-center p-4 text-danger">Error loading students. Please try again.</td></tr>';
        currentStudentsData = [];
    }
}

function displayStudents(students) {
    if(!students || students.length === 0) {
        document.getElementById('stu-body').innerHTML = '<tr><td colspan="7" class="text-center p-4">No students match the filters.</td></tr>';
        return;
    }
    
    document.getElementById('stu-body').innerHTML = students.map((s,i)=>`
        <tr>
            <td style="color:var(--muted); font-size:.78rem">${i+1}</td>
            <td style="font-weight:500">${escapeHtml(s.name)}</td>
            <td><span class="dept-badge">${escapeHtml(s.department||'N/A')}</span></td>
            <td style="color:var(--muted); font-size:.84rem">${escapeHtml(s.email)}</td>
            <td style="color:var(--muted); font-size:.84rem">${escapeHtml(s.phone||'—')}</td>
            <td style="color:var(--muted); font-size:.82rem">${new Date(s.registered_at).toLocaleDateString()}</td>
            <td><button class="rb red" onclick="removeReg(${s.id}, this)"><i class="bi bi-person-dash"></i> Remove</button></td>
        </tr>
    `).join('');
}

async function searchStudents() {
    const search = document.getElementById('search-input').value;
    
    if(!search.trim()) {
        displayStudents(currentStudentsData);
        return;
    }
    
    const d = await post({action: 'search_students', event_id: currentEventId, search: search});
    if(d.success && d.registrations) {
        displayStudents(d.registrations);
    } else {
        displayStudents([]);
    }
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    displayStudents(currentStudentsData);
}

function exportStudentList() {
    if(!currentStudentsData || currentStudentsData.length === 0) { 
        toast('No students to export', 'err'); 
        return; 
    }
    
    const search = document.getElementById('search-input').value;
    let exportData = [...currentStudentsData];
    
    if(search) {
        const searchLower = search.toLowerCase();
        exportData = exportData.filter(s => 
            s.name.toLowerCase().includes(searchLower) || 
            s.email.toLowerCase().includes(searchLower)
        );
    }
    
    let csv = "Name,Department,Email,Phone,Registration Date\n";
    exportData.forEach(s => { 
        csv += `"${s.name}","${s.department||'N/A'}","${s.email}","${s.phone||''}","${new Date(s.registered_at).toLocaleDateString()}"\n`; 
    });
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${currentEventTitle}_students.csv`;
    a.click();
    URL.revokeObjectURL(url);
    toast('Export started!', 'ok');
}

function escapeHtml(s) { if(!s) return ''; return String(s).replace(/[&<>]/g, function(m) { if(m==='&') return '&amp;'; if(m==='<') return '&lt;'; if(m==='>') return '&gt;'; return m;}); }

async function resetAll() { if(!confirm('⚠️ Delete ALL data?')) return; if(!confirm('Last chance!')) return; const d=await post({action:'reset_data',confirm:'yes'}); if(d.success) { toast(d.message,'ok'); setTimeout(()=>location.reload(),900); } }
</script>
</body>
</html>