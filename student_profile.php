<?php
require_once 'config.php';
require_once 'functions.php';

requireLogin();

$user = getCurrentUser();

$success = '';
$error = '';

/* ADD PROFILE PHOTO COLUMN IF NOT EXISTS */

$check_photo = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");

if ($check_photo->num_rows == 0) {
    $conn->query("
        ALTER TABLE users
        ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL
    ");
}

/* PROFILE PHOTO UPLOAD */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'upload_photo'
) {

    header('Content-Type: application/json');

    if (
        isset($_FILES['profile_photo']) &&
        $_FILES['profile_photo']['error'] === 0
    ) {

        $allowed = ['jpg','jpeg','png','gif','webp'];

        $file = $_FILES['profile_photo'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid image type'
            ]);

            exit;
        }

        $upload_dir = 'uploads/profiles/';

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $new_name =
            'user_' .
            $_SESSION['user_id'] .
            '_' .
            time() .
            '.' .
            $ext;

        $upload_path = $upload_dir . $new_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {

            $stmt = $conn->prepare("
                UPDATE users
                SET profile_photo = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $upload_path,
                $_SESSION['user_id']
            );

            $stmt->execute();

            echo json_encode([
                'success' => true,
                'message' => 'Photo uploaded',
                'path' => $upload_path
            ]);

            exit;

        } else {

            echo json_encode([
                'success' => false,
                'message' => 'Upload failed'
            ]);

            exit;
        }

    } else {

        echo json_encode([
            'success' => false,
            'message' => 'No image selected'
        ]);

        exit;
    }
}

/* WITHDRAW EVENT */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'withdraw'
) {

    $registration_id = intval($_POST['registration_id']);

    $stmt = $conn->prepare("
        DELETE FROM registrations
        WHERE id = ?
        AND user_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $registration_id,
        $_SESSION['user_id']
    );

    if ($stmt->execute()) {
        $success = "You have been withdrawn from the event.";
    } else {
        $error = "Failed to withdraw.";
    }
}

/* USER EVENTS */

$events = getUserEvents($_SESSION['user_id']);

$profile_photo =
    !empty($user['profile_photo'])
    ? $user['profile_photo']
    : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Profile - CampusConnect</title>

<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

:root{
    --ink:#0f0e17;
    --cream:#fffcf0;
    --amber:#e86920;
    --amber-light:#fdf3dc;
    --indigo:#1e2557;
    --indigo-mid:#5e2d8c;
    --muted:#6b6a75;
    --border:#e2dfd6;
    --sand:#f4f1e8;
    --danger:#dc3545;
}

body{
    font-family:'DM Sans',sans-serif;
    background:var(--cream);
    color:var(--ink);
    line-height:1.6;
}

/* NAV */

.top-nav{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:1.1rem 3rem;
    border-bottom:1px solid var(--border);
    background:var(--cream);
    position:sticky;
    top:0;
    z-index:100;
}

.logo{
    font-family:'Fraunces',serif;
    font-weight:700;
    font-size:1.4rem;
    color:var(--indigo);
    text-decoration:none;
}

.logo span{
    color:var(--amber);
}

/* PAGE */

.page-content{
    padding:2rem 3rem;
}

/* ALERT */

.alert{
    padding:1rem 1.5rem;
    border-radius:1rem;
    margin-bottom:1.5rem;
}

.alert-success{
    background:#d4edda;
    color:#155724;
    border-left:4px solid #28a745;
}

.alert-error{
    background:#f8d7da;
    color:#721c24;
    border-left:4px solid #dc3545;
}

/* GRID */

.profile-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:2rem;
    margin-bottom:2rem;
}

/* CARD */

.profile-card,
.events-section{
    background:#fff;
    border-radius:1.25rem;
    padding:2rem;
    border:1px solid var(--border);
    box-shadow:0 2px 16px rgba(0,0,0,.06);
}

.profile-card h3,
.events-section h3{
    font-family:'Fraunces',serif;
    font-size:1.5rem;
    margin-bottom:1.5rem;
    display:flex;
    align-items:center;
    gap:.7rem;
}

/* PHOTO */

.profile-photo-section{
    text-align:center;
    margin-bottom:1.5rem;
}

.profile-photo{
    width:120px;
    height:120px;
    border-radius:50%;
    object-fit:cover;
    border:3px solid var(--amber);
    background:var(--sand);
    margin-bottom:1rem;
}

.photo-placeholder{
    width:120px;
    height:120px;
    border-radius:50%;
    border:3px solid var(--amber);
    background:var(--sand);
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 1rem;
}

.photo-placeholder i{
    font-size:3rem;
    color:var(--muted);
}

.upload-btn{
    background:var(--indigo);
    color:#fff;
    border:none;
    padding:.5rem 1rem;
    border-radius:2rem;
    cursor:pointer;
    font-size:.8rem;
    display:inline-flex;
    align-items:center;
    gap:.5rem;
}

.upload-btn:hover{
    background:var(--indigo-mid);
}

.file-input{
    display:none;
}

/* PROFILE */

.profile-item{
    margin-bottom:1.2rem;
    padding-bottom:1.2rem;
    border-bottom:1px solid var(--border);
}

.profile-label{
    font-size:.8rem;
    color:var(--muted);
    margin-bottom:.3rem;
    text-transform:uppercase;
    font-weight:600;
}

.profile-value{
    font-size:1.05rem;
    font-weight:500;
}

/* EVENTS */

.event-list{
    list-style:none;
}

.event-list li{
    background:var(--sand);
    border-left:3px solid var(--amber);
    border-radius:.75rem;
    padding:1rem 1.2rem;
    margin-bottom:.8rem;
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:1rem;
    flex-wrap:wrap;
}

.event-title{
    font-size:1rem;
    font-weight:500;
}

.event-date,
.event-venue{
    color:var(--muted);
    font-size:.85rem;
    margin-top:.2rem;
}

.withdraw-btn{
    background:none;
    border:1px solid var(--danger);
    color:var(--danger);
    padding:.4rem 1rem;
    border-radius:2rem;
    cursor:pointer;
    font-size:.75rem;
    display:inline-flex;
    align-items:center;
    gap:.4rem;
}

.withdraw-btn:hover{
    background:var(--danger);
    color:#fff;
}

.no-events{
    text-align:center;
    color:var(--muted);
    padding:2rem;
}

/* MODAL */

.modal{
    display:none;
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,.5);
    z-index:999;
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:#fff;
    padding:2rem;
    border-radius:1.25rem;
    width:90%;
    max-width:400px;
    text-align:center;
}

.modal-buttons{
    display:flex;
    justify-content:center;
    gap:1rem;
    margin-top:1.5rem;
}

.btn-confirm{
    background:var(--danger);
    color:#fff;
    border:none;
    padding:.5rem 1.5rem;
    border-radius:2rem;
    cursor:pointer;
}

.btn-cancel{
    background:var(--sand);
    border:none;
    padding:.5rem 1.5rem;
    border-radius:2rem;
    cursor:pointer;
}

/* RESPONSIVE */

@media(max-width:900px){

    .profile-grid{
        grid-template-columns:1fr;
    }

    .top-nav{
        padding:1rem 1.5rem;
    }

    .page-content{
        padding:2rem 1.5rem;
    }
}

@media(max-width:600px){

    .top-nav{
        padding:.8rem 1rem;
    }

    .page-content{
        padding:1.5rem 1rem;
    }
}

</style>

</head>

<body>

<?php include 'Dashboard.php'; ?>

<div class="top-nav">

    <a href="index.php" class="logo">
        Campus<span>Connect</span>
    </a>

    <div style="font-size:.95rem;color:var(--muted);">
        My Profile
    </div>

</div>

<div class="page-content">

    <?php if($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">

        <!-- PERSONAL -->

        <div class="profile-card">

            <h3>
                <i class="bi bi-person-fill" style="color:var(--amber);"></i>
                Personal Info
            </h3>

            <div class="profile-photo-section">

                <?php if(!empty($profile_photo)): ?>

                    <img
                        src="<?php echo htmlspecialchars($profile_photo); ?>?t=<?php echo time(); ?>"
                        class="profile-photo"
                        id="profilePreview"
                    >

                <?php else: ?>

                    <div class="photo-placeholder" id="profilePreview">
                        <i class="bi bi-person-circle"></i>
                    </div>

                <?php endif; ?>

                <div>

                    <input
                        type="file"
                        id="photoInput"
                        class="file-input"
                        name="profile_photo"
                        accept="image/*"
                        onchange="uploadPhoto(this)"
                    >

                    <button
                        class="upload-btn"
                        onclick="document.getElementById('photoInput').click()"
                    >
                        <i class="bi bi-camera"></i>
                        Upload Photo
                    </button>

                </div>

            </div>

            <div class="profile-item">
                <div class="profile-label">Full Name</div>
                <div class="profile-value">
                    <?php echo htmlspecialchars($user['name']); ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Email</div>
                <div class="profile-value">
                    <?php echo htmlspecialchars($user['email']); ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Role</div>
                <div class="profile-value" style="text-transform:capitalize;">
                    <?php echo htmlspecialchars($user['role']); ?>
                </div>
            </div>

        </div>

        <!-- ACADEMIC -->

        <div class="profile-card">

            <h3>
                <i class="bi bi-building" style="color:var(--amber);"></i>
                Academic Info
            </h3>

            <div class="profile-item">
                <div class="profile-label">Department</div>
                <div class="profile-value">
                    <?php echo htmlspecialchars($user['department'] ?? 'Not specified'); ?>
                </div>
            </div>

            <div class="profile-item">
                <div class="profile-label">Member Since</div>
                <div class="profile-value">
                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                </div>
            </div>

        </div>

    </div>

    <!-- EVENTS -->

    <div class="events-section">

        <h3>
            <i class="bi bi-calendar-event" style="color:var(--amber);"></i>
            My Registered Events
        </h3>

        <?php if(empty($events)): ?>

            <p class="no-events">
                You haven't registered for any events yet.
            </p>

        <?php else: ?>

            <ul class="event-list">

                <?php foreach($events as $e): ?>

                    <li>

                        <div>

                            <div class="event-title">
                                <?php echo htmlspecialchars($e['title']); ?>
                            </div>

                            <div class="event-date">
                                📅 <?php echo htmlspecialchars($e['event_date']); ?>
                                at
                                <?php echo htmlspecialchars($e['event_time']); ?>
                            </div>

                            <div class="event-venue">
                                📍 <?php echo htmlspecialchars($e['venue']); ?>
                            </div>

                        </div>

                        <button
                            class="withdraw-btn"
                            onclick="confirmWithdraw(
                                <?php echo $e['registration_id']; ?>,
                                '<?php echo addslashes($e['title']); ?>'
                            )"
                        >
                            <i class="bi bi-person-x"></i>
                            Withdraw
                        </button>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

    </div>

</div>

<!-- MODAL -->

<div id="withdrawModal" class="modal">

    <div class="modal-content">

        <i
            class="bi bi-exclamation-triangle"
            style="font-size:3rem;color:var(--danger);">
        </i>

        <h4 style="margin:1rem 0;">
            Withdraw from Event?
        </h4>

        <p id="withdrawEventName"></p>

        <form method="POST">

            <input type="hidden" name="action" value="withdraw">

            <input
                type="hidden"
                name="registration_id"
                id="withdrawRegId"
            >

            <div class="modal-buttons">

                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeModal()"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-confirm"
                >
                    Withdraw
                </button>

            </div>

        </form>

    </div>

</div>

<script>

function confirmWithdraw(regId, eventTitle){

    document.getElementById('withdrawRegId').value = regId;

    document.getElementById('withdrawEventName').innerHTML =
        '<strong>' + eventTitle + '</strong>';

    document.getElementById('withdrawModal').style.display = 'flex';
}

function closeModal(){
    document.getElementById('withdrawModal').style.display = 'none';
}

/* PHOTO UPLOAD */

function uploadPhoto(input){

    if(!input.files || !input.files[0]) return;

    const formData = new FormData();

    formData.append('action', 'upload_photo');

    formData.append('profile_photo', input.files[0]);

    fetch(window.location.href,{
        method:'POST',
        body:formData
    })

    .then(res => res.json())

    .then(data => {

        if(data.success){

            location.reload();

        }else{

            alert(data.message || 'Upload failed');
        }
    })

    .catch(() => {

        alert('Upload failed');
    });
}

window.onclick = function(event){

    const modal = document.getElementById('withdrawModal');

    if(event.target == modal){
        modal.style.display = 'none';
    }
}

</script>

</body>
</html>