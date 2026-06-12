<?php
require_once 'config.php';
require_once 'functions.php';

// Handle contact form submission
$contact_success = '';
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $contact_error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = "Please enter a valid email address.";
    } else {
        // Save to contact_messages table (create if not exists)
        $create_table = "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'unread'
        )";
        $conn->query($create_table);
        
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $contact_success = "Thank you for your message! We'll get back to you soon.";
            // Clear form fields via JavaScript
            echo "<script>document.getElementById('contactForm').reset();</script>";
        } else {
            $contact_error = "Failed to send message. Please try again.";
        }
        $stmt->close();
    }
}

// Handle event feedback submission
$feedback_success = '';
$feedback_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_feedback'])) {
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_id = $_SESSION['user_id'] ?? 0;
    $rating = intval($_POST['rating'] ?? 0);
    $feedback_text = trim($_POST['feedback_text'] ?? '');
    
    if ($event_id && $rating >= 1 && $rating <= 5 && !empty($feedback_text)) {
        // Create reviews table if not exists with feedback column
        $create_table = "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            rating INT DEFAULT 0,
            review_text TEXT,
            feedback_text TEXT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($create_table);
        
        // Check if user already gave feedback for this event
        $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE event_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $event_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, feedback_text = ?, updated_at = NOW() WHERE event_id = ? AND user_id = ?");
            $stmt->bind_param("isii", $rating, $feedback_text, $event_id, $user_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO reviews (event_id, user_id, rating, feedback_text, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiis", $event_id, $user_id, $rating, $feedback_text);
        }
        
        if ($stmt->execute()) {
            $feedback_success = "Thank you for your feedback!";
        } else {
            $feedback_error = "Failed to submit feedback. Please try again.";
        }
        $stmt->close();
    } else {
        $feedback_error = "Please provide a rating and your feedback.";
    }
}

// Fetch past events for feedback dropdown
$past_events = [];
if (isset($_SESSION['user_id'])) {
    $past_events = $conn->query("
        SELECT DISTINCT e.id, e.title 
        FROM events e
        JOIN registrations r ON e.id = r.event_id
        WHERE r.user_id = {$_SESSION['user_id']} 
        AND e.event_date < CURDATE()
        ORDER BY e.event_date DESC
        LIMIT 10
    ")->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact & Feedback - CampusConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --ink: #0f0e17;
            --cream: #fffcf0;
            --amber: #e86920;
            --amber-light: #fdf3dc;
            --indigo: #1e2557;
            --indigo-mid: #5e2d8c;
            --muted: #6b6a75;
            --border: #e2dfd6;
            --sand: #f4f1e8;
            --teal: #2d6a6a;
            --teal-light: #e8f4f4;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            line-height: 1.6;
            min-height: 100vh;
            position: relative;
        }

        /* Full Page Background Image */
        .hero-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .hero-background img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .hero-background::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(30, 37, 87, 0.85), rgba(94, 45, 140, 0.85));
            pointer-events: none;
        }

        /* Navbar */
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

        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
            position: relative;
            z-index: 5;
        }

        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-header h1 {
            font-family: 'Fraunces', serif;
            font-size: 3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
        }

        .page-header p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 2rem;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .card-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, var(--indigo), var(--indigo-mid));
            color: white;
        }

        .card-header h2 {
            font-family: 'Fraunces', serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-header h2 i {
            font-size: 1.8rem;
        }

        .card-body {
            padding: 1.8rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--indigo);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1.5px solid var(--border);
            border-radius: 0.75rem;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--indigo);
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #ffc107;
        }

        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            background: var(--indigo);
            color: white;
            border: none;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background: var(--amber);
            transform: translateY(-2px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* About Section */
        .about-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-top: 2rem;
            text-align: center;
        }

        .about-section h3 {
            font-family: 'Fraunces', serif;
            font-size: 1.8rem;
            color: var(--indigo);
            margin-bottom: 1rem;
        }

        .about-section p {
            color: var(--muted);
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto 1.5rem;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }

        .feature {
            text-align: center;
            padding: 1rem;
        }

        .feature i {
            font-size: 2.5rem;
            color: var(--amber);
            margin-bottom: 0.5rem;
            display: inline-block;
        }

        .feature h4 {
            font-weight: 700;
            color: var(--indigo);
            margin-bottom: 0.25rem;
        }

        .feature p {
            font-size: 0.85rem;
            color: var(--muted);
            margin: 0;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.7);
            position: relative;
            z-index: 5;
        }

        @media (max-width: 768px) {
            .top-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-links {
                gap: 1rem;
            }
            .main-container {
                padding: 1rem;
            }
            .page-header h1 {
                font-size: 2rem;
            }
            .cards-grid {
                grid-template-columns: 1fr;
            }
            .features {
                gap: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Full Page Background Image -->
<div class="hero-background">
    <img src="https://images.unsplash.com/photo-1523580494863-6f3031224c94?q=80&w=2070" alt="Campus Background">
</div>

<nav class="top-nav">
    <a href="index.php" class="logo">Campus<span>Connect</span></a>
    <div class="nav-links">
        <a href="index.php"><i class="bi bi-house"></i> Home</a>
      
    </div>
</nav>

<div class="main-container">
    <div class="page-header">
        <h1>Connect With Us</h1>
        <p>We'd love to hear from you — share your thoughts, feedback, or just say hello!</p>
    </div>

    <div class="cards-grid">
        <!-- Contact Form Card -->
        <div class="info-card">
            <div class="card-header">
                <h2><i class="bi bi-envelope-paper"></i> Send us a Message</h2>
            </div>
            <div class="card-body">
                <?php if ($contact_success): ?>
                    <div class="alert success"><i class="bi bi-check-circle-fill"></i> <?php echo $contact_success; ?></div>
                <?php endif; ?>
                <?php if ($contact_error): ?>
                    <div class="alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $contact_error; ?></div>
                <?php endif; ?>
                
                <form method="POST" id="contactForm">
                    <div class="form-group">
                        <label for="name"><i class="bi bi-person"></i> Your Name</label>
                        <input type="text" id="name" name="name" required placeholder="John Doe">
                    </div>
                    <div class="form-group">
                        <label for="email"><i class="bi bi-envelope"></i> Email Address</label>
                        <input type="email" id="email" name="email" required placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label for="subject"><i class="bi bi-tag"></i> Subject</label>
                        <input type="text" id="subject" name="subject" required placeholder="What is this about?">
                    </div>
                    <div class="form-group">
                        <label for="message"><i class="bi bi-chat-dots"></i> Message</label>
                        <textarea id="message" name="message" rows="4" required placeholder="Write your message here..."></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="btn-submit">
                        <i class="bi bi-send"></i> Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Event Feedback Card -->
        <div class="info-card">
            <div class="card-header">
                <h2><i class="bi bi-star"></i> Event Feedback</h2>
            </div>
            <div class="card-body">
                <?php if ($feedback_success): ?>
                    <div class="alert success"><i class="bi bi-check-circle-fill"></i> <?php echo $feedback_success; ?></div>
                <?php endif; ?>
                <?php if ($feedback_error): ?>
                    <div class="alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $feedback_error; ?></div>
                <?php endif; ?>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="alert error">
                        <i class="bi bi-lock-fill"></i> Please <a href="login.php" style="color: var(--indigo);">login</a> to submit event feedback.
                    </div>
                <?php elseif (empty($past_events)): ?>
                    <div class="alert error">
                        <i class="bi bi-info-circle-fill"></i> You haven't attended any past events yet. Attend events to leave feedback!
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="event_id"><i class="bi bi-calendar-event"></i> Select Event</label>
                            <select id="event_id" name="event_id" required>
                                <option value="">-- Choose an event --</option>
                                <?php foreach ($past_events as $event): ?>
                                    <option value="<?php echo $event['id']; ?>"><?php echo htmlspecialchars($event['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="bi bi-star-fill"></i> Your Rating</label>
                            <div class="star-rating">
                                <input type="radio" name="rating" value="5" id="star5_fb"><label for="star5_fb">★</label>
                                <input type="radio" name="rating" value="4" id="star4_fb"><label for="star4_fb">★</label>
                                <input type="radio" name="rating" value="3" id="star3_fb"><label for="star3_fb">★</label>
                                <input type="radio" name="rating" value="2" id="star2_fb"><label for="star2_fb">★</label>
                                <input type="radio" name="rating" value="1" id="star1_fb"><label for="star1_fb">★</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback_text"><i class="bi bi-chat-quote"></i> Your Feedback</label>
                            <textarea id="feedback_text" name="feedback_text" rows="3" required placeholder="Share your experience... What did you like? What could be improved?"></textarea>
                        </div>
                        
                        <button type="submit" name="submit_feedback" class="btn-submit">
                            <i class="bi bi-send-check"></i> Submit Feedback
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Info Card -->
        <div class="info-card">
            <div class="card-header">
                <h2><i class="bi bi-info-circle"></i> Get in Touch</h2>
            </div>
            <div class="card-body">
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: var(--indigo); margin-bottom: 1rem;"><i class="bi bi-geo-alt-fill"></i> Visit Us</h4>
                    <p style="color: var(--muted);">CampusConnect Office<br>University Main Campus<br>Building 7, 2nd Floor<br>City, State 12345</p>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: var(--indigo); margin-bottom: 1rem;"><i class="bi bi-telephone-fill"></i> Call Us</h4>
                    <p style="color: var(--muted);">Main Office: <strong>+1 (555) 123-4567</strong><br>Student Support: <strong>+1 (555) 987-6543</strong></p>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: var(--indigo); margin-bottom: 1rem;"><i class="bi bi-clock-fill"></i> Office Hours</h4>
                    <p style="color: var(--muted);">Monday - Friday: 9:00 AM - 6:00 PM<br>Saturday: 10:00 AM - 2:00 PM<br>Sunday: Closed</p>
                </div>
                
                <div>
                    <h4 style="color: var(--indigo); margin-bottom: 1rem;"><i class="bi bi-share-fill"></i> Follow Us</h4>
                    <div style="display: flex; gap: 1rem;">
                        <a href="#" style="color: var(--indigo); font-size: 1.5rem;"><i class="bi bi-facebook"></i></a>
                        <a href="#" style="color: var(--indigo); font-size: 1.5rem;"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" style="color: var(--indigo); font-size: 1.5rem;"><i class="bi bi-instagram"></i></a>
                        <a href="#" style="color: var(--indigo); font-size: 1.5rem;"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- About Section -->
    <div class="about-section">
        <h3>About CampusConnect</h3>
        <p>CampusConnect is your ultimate platform for discovering, registering, and engaging with campus events. 
           We bring together students, faculty, and organizers to create a vibrant campus community. 
           Whether you're looking for academic seminars, cultural festivals, sports tournaments, or workshops, 
           CampusConnect makes it easy to stay connected and never miss an opportunity.</p>
        
        <div class="features">
            <div class="feature">
                <i class="bi bi-calendar-check"></i>
                <h4>Event Discovery</h4>
                <p>Find events that match your interests</p>
            </div>
            <div class="feature">
                <i class="bi bi-people"></i>
                <h4>Community</h4>
                <p>Connect with fellow students</p>
            </div>
            <div class="feature">
                <i class="bi bi-trophy"></i>
                <h4>Achievements</h4>
                <p>Track your participation</p>
            </div>
            <div class="feature">
                <i class="bi bi-chat-dots"></i>
                <h4>Feedback</h4>
                <p>Share your experiences</p>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>&copy; <?php echo date('Y'); ?> CampusConnect. All rights reserved. | Building better campus experiences together.</p>
</footer>

</body>
</html>