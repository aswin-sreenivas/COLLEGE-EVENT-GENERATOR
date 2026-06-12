<?php
require_once 'config.php';

/* ================= AUTH ================= */

function isLoggedIn(){
    return isset($_SESSION['user_id']);
}

function requireLogin(){
    if(!isLoggedIn()){
        header("Location: login.php");
        exit;
    }
}

function requireRole($roles){
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], (array)$roles)) {
        header("Location: index.php");
        exit;
    }
}

/* ================= USERS ================= */

function getCurrentUser(){
    global $conn;

    if(!isset($_SESSION['user_id'])) return null;

    $stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

/* ================= EVENTS ================= */
/* ================= EVENTS ================= */

function getEvents($status = 'approved') {
    global $conn;

    $sql = "
        SELECT e.*, COUNT(r.id) AS registrations
        FROM events e
        LEFT JOIN registrations r ON e.id = r.event_id
        WHERE e.status=?
        GROUP BY e.id
        ORDER BY e.date ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getAllEvents(){
    global $conn;

    // Admin view (all events)
    $stmt = $conn->prepare("
        SELECT * FROM events 
        ORDER BY event_date DESC
    ");
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getPendingEvents(){
    global $conn;

    $stmt = $conn->prepare("
        SELECT * FROM events 
        WHERE status='pending' 
        ORDER BY event_date ASC
    ");
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function createEvent($title,$desc,$cat,$date,$time,$venue,$cap,$org){
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO events
        (title,description,category,event_date,event_time,venue,capacity,organizer_id,status)
        VALUES(?,?,?,?,?,?,?,?, 'pending')
    ");

    $stmt->bind_param("ssssssii",$title,$desc,$cat,$date,$time,$venue,$cap,$org);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Event created (awaiting approval)"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

function approveEvent($id){
    global $conn;

    $stmt = $conn->prepare("
        UPDATE events 
        SET status='approved' 
        WHERE id=?
    ");

    $stmt->bind_param("i",$id);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Event approved"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

function rejectEvent($id){
    global $conn;

    $stmt = $conn->prepare("
        UPDATE events 
        SET status='rejected' 
        WHERE id=?
    ");

    $stmt->bind_param("i",$id);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Event rejected"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

function updateEvent($id,$title,$desc,$cat,$date,$time,$venue,$cap){
    global $conn;

    // Reset to pending after edit
    $stmt = $conn->prepare("
        UPDATE events 
        SET title=?, description=?, category=?, event_date=?, event_time=?, venue=?, capacity=?, status='pending'
        WHERE id=?
    ");

    $stmt->bind_param("ssssssii",$title,$desc,$cat,$date,$time,$venue,$cap,$id);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Updated (needs re-approval)"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

/* ================= REGISTRATION ================= */

function registerForEvent($user_id, $event_id){
    global $conn;

    // check event exists
    $stmt = $conn->prepare("SELECT capacity FROM events WHERE id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();

    if(!$event){
        return ["success"=>false,"message"=>"Event not found"];
    }

    // check capacity
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE event_id=?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'];

    if($count >= $event['capacity']){
        return ["success"=>false,"message"=>"Event is full"];
    }

    // prevent duplicate registration
    $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id=? AND event_id=?");
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();

    if($stmt->get_result()->num_rows > 0){
        return ["success"=>false,"message"=>"Already registered"];
    }

    // insert registration
    $stmt = $conn->prepare("INSERT INTO registrations(user_id,event_id) VALUES(?,?)");
    $stmt->bind_param("ii", $user_id, $event_id);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Registered successfully"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}
/* ================= BULLETINS ================= */

function getBulletins(){
    global $conn;

    $check = $conn->query("SHOW TABLES LIKE 'bulletins'");
    if($check->num_rows == 0){
        return [];
    }

    $stmt = $conn->prepare("SELECT * FROM bulletins ORDER BY created_at DESC");
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function createBulletin($title,$desc){
    global $conn;

    $stmt = $conn->prepare("INSERT INTO bulletins(title,description,created_at) VALUES(?,?,NOW())");

    if(!$stmt){
        return ["success"=>false,"message"=>$conn->error];
    }

    $stmt->bind_param("ss",$title,$desc);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Bulletin created successfully"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

function deleteBulletin($id){
    global $conn;

    $stmt = $conn->prepare("DELETE FROM bulletins WHERE id=?");
    $stmt->bind_param("i",$id);

    if($stmt->execute()){
        return ["success"=>true,"message"=>"Bulletin deleted"];
    }

    return ["success"=>false,"message"=>$stmt->error];
}

/* ================= ADMIN STATS ================= */

function getAdminStats() {
    global $conn;

    $stats = [];

    // Total Events
    $res = $conn->query("SELECT COUNT(*) as total FROM events");
    $stats['total_events'] = $res->fetch_assoc()['total'] ?? 0;

    // Total Registrations
    $res = $conn->query("SELECT COUNT(*) as total FROM registrations");
    $stats['total_registrations'] = $res->fetch_assoc()['total'] ?? 0;

    // Active Events (approved + future)
    $res = $conn->query("
        SELECT COUNT(*) as total 
        FROM events 
        WHERE status='approved' AND event_date >= CURDATE()
    ");
    $stats['active_events'] = $res->fetch_assoc()['total'] ?? 0;

    // Categories (distinct)
    $res = $conn->query("SELECT COUNT(DISTINCT category) as total FROM events");
    $stats['categories'] = $res->fetch_assoc()['total'] ?? 0;

    return $stats;
}
function getOrganizerEvents($user_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM events WHERE organizer_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getEventById($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
// In functions.php, update getUserEvents function:
function getUserEvents($user_id) {
    global $conn;
    $query = "SELECT e.*, r.id as registration_id 
              FROM events e 
              JOIN registrations r ON e.id = r.event_id 
              WHERE r.user_id = ? 
              ORDER BY e.event_date ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>