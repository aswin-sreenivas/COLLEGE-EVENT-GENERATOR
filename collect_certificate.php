<?php
require_once 'config.php';
require_once 'functions.php';

$user = null;
if (isLoggedIn()) {
    $user = getCurrentUser();
}

$event = null;
$certificate_issued = false;

if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    $event = getEventById($event_id);
    
    // Check if user is registered for this event
    if ($user && $event) {
        $result = $conn->query("SELECT id FROM certificates WHERE user_id = {$user['id']} AND event_id = $event_id");
        $certificate_issued = $result->num_rows > 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - CampusConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .certificate {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border: 5px solid #d4af37;
            border-radius: 10px;
            padding: 40px;
            margin: 20px 0;
            text-align: center;
            font-family: Georgia, serif;
        }
        .certificate h2 {
            font-size: 2.5rem;
            color: #333;
            margin-bottom: 20px;
        }
        .certificate-text {
            font-size: 1.1rem;
            color: #555;
            margin: 20px 0;
        }
        .cert-name {
            font-size: 1.5rem;
            font-weight: bold;
            color: #d4af37;
            margin: 20px 0;
        }
        .cert-event {
            font-size: 1.2rem;
            color: #333;
            margin: 15px 0;
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-white sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">CampusConnect</a>
            <div class="collapse navbar-collapse ms-auto">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="previous_events.php">Past Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="student_profile.php">My Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h2 class="text-center mb-4">🏆 Certificate of Participation</h2>

                <?php if (!$user): ?>
                    <div class="alert alert-warning">
                        <p>Please <a href="login.php">login</a> to view your certificate.</p>
                    </div>
                <?php elseif (!$event): ?>
                    <div class="alert alert-danger">
                        <p>Event not found.</p>
                    </div>
                <?php else: ?>
                    <div class="certificate">
                        <h2>Certificate of Participation</h2>
                        <p class="certificate-text">This is to certify that</p>
                        <div class="cert-name"><?php echo htmlspecialchars($user['name']); ?></div>
                        <p class="certificate-text">has successfully participated in</p>
                        <div class="cert-event"><?php echo htmlspecialchars($event['title']); ?></div>
                        <p class="certificate-text">held on <?php echo htmlspecialchars(date('F d, Y', strtotime($event['event_date']))); ?></p>
                        
                        <?php if ($certificate_issued): ?>
                            <p class="certificate-text" style="color: green;">
                                <strong>✓ Certificate Issued</strong><br>
                                <small style="color: #666;">This participation is officially recorded.</small>
                            </p>
                        <?php else: ?>
                            <p class="certificate-text" style="color: #d4af37;">
                                <strong>Certificate Available</strong><br>
                                <small style="color: #666;">Issued for participation in this event.</small>
                            </p>
                        <?php endif; ?>

                        <hr class="my-4">
                        <p style="font-size: 0.95rem; color: #666;">
                            <strong>Event Details:</strong><br>
                            Date: <?php echo htmlspecialchars($event['event_date']); ?><br>
                            Category: <?php echo htmlspecialchars(ucfirst($event['category'])); ?><br>
                            Venue: <?php echo htmlspecialchars($event['venue']); ?>
                        </p>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Certificate</button>
                        <a href="previous_events.php" class="btn btn-outline-secondary">Back to Events</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted small mb-0">&copy; 2026 CampusConnect. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
