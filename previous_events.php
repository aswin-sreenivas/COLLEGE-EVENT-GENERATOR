<?php
// past_events.php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

$user = getCurrentUser();

// Handle rating submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    $event_id = $_POST['event_id'] ?? 0;
    $rating = $_POST['rating'] ?? 0;
    $user_id = $_SESSION['user_id'];
    
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if ($event_id && $rating >= 1 && $rating <= 5) {
        // Check if user already rated this event
        $check_stmt = $conn->prepare("SELECT id FROM reviews WHERE event_id = ? AND user_id = ?");
        $check_stmt->bind_param("ii", $event_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Update existing rating
            $stmt = $conn->prepare("UPDATE reviews SET rating = ?, created_at = NOW() WHERE event_id = ? AND user_id = ?");
            $stmt->bind_param("iii", $rating, $event_id, $user_id);
        } else {
            // Insert new rating
            $stmt = $conn->prepare("INSERT INTO reviews (event_id, user_id, rating, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iii", $event_id, $user_id, $rating);
        }
        
        if ($stmt->execute()) {
            // Get updated average rating
            $avg_stmt = $conn->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as rating_count FROM reviews WHERE event_id = ?");
            $avg_stmt->bind_param("i", $event_id);
            $avg_stmt->execute();
            $avg_result = $avg_stmt->get_result()->fetch_assoc();
            
            $response = [
                'success' => true, 
                'message' => 'Rating submitted successfully!',
                'avg_rating' => round($avg_result['avg_rating'], 1),
                'rating_count' => $avg_result['rating_count']
            ];
        } else {
            $response = ['success' => false, 'message' => 'Failed to submit rating'];
        }
        $stmt->close();
    }
    
    echo json_encode($response);
    exit;
}

// Fetch past events (events with date < today)
$past_events = $conn->query("
    SELECT e.*, 
           COUNT(DISTINCT r.id) as registrations,
           COALESCE(AVG(rv.rating), 0) as avg_rating,
           COUNT(DISTINCT rv.id) as rating_count
    FROM events e
    LEFT JOIN registrations r ON e.id = r.event_id
    LEFT JOIN reviews rv ON e.id = rv.event_id
    WHERE e.event_date < CURDATE() AND e.status = 'approved'
    GROUP BY e.id
    ORDER BY e.event_date DESC
")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Past Events - CampusConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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
    --shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    --shadow-hover: 0 8px 30px rgba(0, 0, 0, 0.12);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(135deg, var(--cream) 0%, #fff8ec 100%);
    color: var(--ink);
    line-height: 1.6;
}

/* Navbar */
.top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.1rem 3rem;
    border-bottom: 1px solid var(--border);
    background: rgba(255, 252, 240, 0.95);
    backdrop-filter: blur(10px);
    position: sticky;
    top: 0;
    z-index: 100;
}

.top-nav .logo {
    font-family: 'Fraunces', serif;
    font-weight: 700;
    font-size: 1.6rem;
    color: var(--indigo);
    letter-spacing: -0.02em;
    text-decoration: none;
    transition: transform 0.2s;
}

.top-nav .logo:hover { transform: scale(1.02); }

.top-nav .logo span { color: var(--amber); }

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-mid) 100%);
    padding: 3rem 3rem 4rem;
    text-align: center;
    color: white;
    margin-bottom: 2rem;
}

.hero-section h1 {
    font-family: 'Fraunces', serif;
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-section p {
    font-size: 1.2rem;
    opacity: 0.9;
}

/* Events Grid */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 3rem;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 2rem;
}

.event-card {
    background: white;
    border-radius: 1.25rem;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border: 1px solid var(--border);
    position: relative;
}

.event-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.event-image {
    position: relative;
    height: 220px;
    overflow: hidden;
    background: linear-gradient(135deg, var(--teal-light), var(--sand));
    cursor: pointer;
}

.event-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s;
}

.event-card:hover .event-image img {
    transform: scale(1.05);
}

.event-image .image-slideshow {
    width: 100%;
    height: 100%;
    position: relative;
}

.event-image .slide {
    width: 100%;
    height: 100%;
    display: none;
}

.event-image .slide.active {
    display: block;
}

.event-image .slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.event-image .nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(0,0,0,0.5);
    color: white;
    border: none;
    padding: 0.5rem 0.8rem;
    cursor: pointer;
    border-radius: 50%;
    font-size: 1rem;
    z-index: 10;
}

.event-image .prev { left: 10px; }
.event-image .next { right: 10px; }

.event-image .nav-btn:hover {
    background: rgba(0,0,0,0.8);
}

.image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 4rem;
    background: linear-gradient(135deg, var(--teal-light), var(--sand));
    color: var(--teal);
}

.event-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: var(--amber);
    color: var(--ink);
    padding: 0.4rem 1rem;
    border-radius: 2rem;
    font-size: 0.8rem;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    z-index: 5;
}

.event-content {
    padding: 1.5rem;
}

.event-title {
    font-family: 'Fraunces', serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: var(--indigo);
}

.event-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    font-size: 0.85rem;
    color: var(--muted);
    flex-wrap: wrap;
}

.event-meta i {
    margin-right: 0.3rem;
    color: var(--amber);
}

.event-description {
    color: var(--ink);
    margin-bottom: 1rem;
    line-height: 1.5;
}

.event-stats {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-top: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
    margin-bottom: 1rem;
}

.stat {
    text-align: center;
}

.stat-value {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--indigo);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--muted);
}

.rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    justify-content: center;
}

.stars {
    color: #ffc107;
    letter-spacing: 2px;
    font-size: 1rem;
}

.rating-count {
    font-size: 0.8rem;
    color: var(--muted);
}

.btn-rating {
    width: 100%;
    padding: 0.75rem;
    background: var(--indigo);
    color: white;
    border: none;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.btn-rating:hover {
    background: var(--amber);
    transform: translateY(-2px);
}

/* Rating Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background: white;
    border-radius: 1.25rem;
    max-width: 500px;
    width: 90%;
    padding: 2rem;
    position: relative;
    animation: slideIn 0.3s;
}

@keyframes slideIn {
    from { transform: translateY(-50px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    font-family: 'Fraunces', serif;
    font-size: 1.5rem;
    font-weight: 700;
}

.close-modal {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--muted);
}

.rating-input {
    margin: 1rem 0;
}

.rating-input label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.star-rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.5rem;
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

.modal-footer {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1rem;
}

.btn-submit, .btn-cancel {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    font-weight: 500;
}

.btn-submit {
    background: var(--indigo);
    color: white;
}

.btn-cancel {
    background: var(--border);
    color: var(--ink);
}

/* Toast Notification */
.toast {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: var(--teal);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 2000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.toast.show {
    transform: translateX(0);
}

.toast i {
    font-size: 1.2rem;
}

.no-events {
    text-align: center;
    padding: 4rem;
    background: white;
    border-radius: 1.25rem;
    color: var(--muted);
}

@media (max-width: 768px) {
    .hero-section h1 { font-size: 2rem; }
    .hero-section { padding: 2rem 1.5rem; }
    .container { padding: 0 1rem 2rem; }
    .events-grid { grid-template-columns: 1fr; }
    .top-nav { padding: 1rem 1.5rem; }
    .toast { bottom: 1rem; right: 1rem; left: 1rem; transform: translateY(100px); }
    .toast.show { transform: translateY(0); }
}
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="logo">Campus<span>Connect</span></a>
    <div style="font-size: 0.95rem; color: var(--muted);">
        <i class="bi bi-calendar-check"></i> Past Events
    </div>
</div>

<div class="hero-section">
    <h1><i class="bi bi-clock-history"></i> Past Events</h1>
    <p>Relive the memories and rate your favorite events</p>
</div>

<div class="container">
    <?php if (empty($past_events)): ?>
        <div class="no-events">
            <i class="bi bi-calendar-x" style="font-size: 3rem; color: var(--muted);"></i>
            <h3>No Past Events Yet</h3>
            <p>Check back later to see what you missed!</p>
        </div>
    <?php else: ?>
        <div class="events-grid">
            <?php foreach ($past_events as $event): 
                $avg_rating = round($event['avg_rating'], 1);
                $full_stars = floor($avg_rating);
                $half_star = ($avg_rating - $full_stars) >= 0.5;
                
                // Get images from database
                $event_images = [];
                if (!empty($event['images'])) {
                    $event_images = json_decode($event['images'], true);
                    if (!is_array($event_images)) $event_images = [];
                }
                if (empty($event_images)) {
                    // Default icons if no images uploaded
                    $category = strtolower($event['category'] ?? 'default');
                    $default_icons = [
                        'workshop' => ['🎓', '📚', '💡'],
                        'seminar' => ['📚', '🎤', '📊'],
                        'cultural' => ['🎭', '🎨', '🎵'],
                        'sports' => ['⚽', '🏀', '🏆'],
                        'tech' => ['💻', '🤖', '📱'],
                        'default' => ['📅', '🎉', '✨']
                    ];
                    $event_images = $default_icons[$category] ?? $default_icons['default'];
                }
            ?>
                <div class="event-card" data-event-id="<?php echo $event['id']; ?>">
                    <div class="event-image" id="slideshow-<?php echo $event['id']; ?>">
                        <div class="image-slideshow">
                            <?php foreach ($event_images as $idx => $img): ?>
                                <div class="slide <?php echo $idx === 0 ? 'active' : ''; ?>">
                                    <?php if (strpos($img, 'uploads/') === 0 && file_exists($img)): ?>
                                        <img src="<?php echo htmlspecialchars($img); ?>" alt="Event image">
                                    <?php else: ?>
                                        <div class="image-placeholder" style="font-size: 4rem;"><?php echo htmlspecialchars($img); ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($event_images) > 1): ?>
                            <button class="nav-btn prev" onclick="changeSlide(<?php echo $event['id']; ?>, -1, event)">‹</button>
                            <button class="nav-btn next" onclick="changeSlide(<?php echo $event['id']; ?>, 1, event)">›</button>
                        <?php endif; ?>
                        <div class="event-badge">
                            <i class="bi bi-calendar-check"></i> Past Event
                        </div>
                    </div>
                    
                    <div class="event-content">
                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                        
                        <div class="event-meta">
                            <span><i class="bi bi-calendar"></i> <?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                            <span><i class="bi bi-clock"></i> <?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                            <span><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($event['venue']); ?></span>
                        </div>
                        
                        <div class="event-description">
                            <?php echo htmlspecialchars(substr($event['description'], 0, 120)) . '...'; ?>
                        </div>
                        
                        <div class="event-stats">
                            <div class="stat">
                                <div class="stat-value"><?php echo $event['registrations']; ?></div>
                                <div class="stat-label">Attendees</div>
                            </div>
                            <div class="stat">
                                <div class="stat-value rating" id="rating-display-<?php echo $event['id']; ?>">
                                    <?php if ($event['rating_count'] > 0): ?>
                                        <div class="stars">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <?php if($i <= $full_stars): ?>
                                                    ★
                                                <?php elseif($i == $full_stars + 1 && $half_star): ?>
                                                    ⨯
                                                <?php else: ?>
                                                    ☆
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </div>
                                <div class="stat-label" id="rating-count-<?php echo $event['id']; ?>"><?php echo $event['rating_count']; ?> ratings</div>
                            </div>
                        </div>
                        
                        <button class="btn-rating" onclick="openRatingModal(<?php echo $event['id']; ?>, '<?php echo addslashes($event['title']); ?>')">
                            <i class="bi bi-star"></i> Rate this Event
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Rating Modal -->
<div id="ratingModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span>Rate this Event</span>
            <button class="close-modal" onclick="closeRatingModal()">&times;</button>
        </div>
        <form id="ratingForm">
            <input type="hidden" name="event_id" id="modal_event_id">
            <p><strong id="modal_event_title"></strong></p>
            
            <div class="rating-input">
                <label>Your Rating:</label>
                <div class="star-rating">
                    <input type="radio" name="rating" value="5" id="star5"><label for="star5">★</label>
                    <input type="radio" name="rating" value="4" id="star4"><label for="star4">★</label>
                    <input type="radio" name="rating" value="3" id="star3"><label for="star3">★</label>
                    <input type="radio" name="rating" value="2" id="star2"><label for="star2">★</label>
                    <input type="radio" name="rating" value="1" id="star1"><label for="star1">★</label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeRatingModal()">Cancel</button>
                <button type="submit" class="btn-submit">Submit Rating</button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast">
    <i class="bi bi-check-circle"></i>
    <span id="toastMessage">Rating submitted!</span>
</div>

<script>
let currentSubmitting = false;

function showToast(message, isError = false) {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = toast.querySelector('i');
    
    toastMessage.textContent = message;
    if (isError) {
        toastIcon.className = 'bi bi-exclamation-circle';
        toast.style.background = '#c82333';
    } else {
        toastIcon.className = 'bi bi-check-circle';
        toast.style.background = '#2d6a6a';
    }
    
    toast.classList.add('show');
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

function openRatingModal(eventId, eventTitle) {
    document.getElementById('modal_event_id').value = eventId;
    document.getElementById('modal_event_title').innerText = eventTitle;
    document.getElementById('ratingModal').style.display = 'flex';
    // Reset star selection
    document.querySelectorAll('.star-rating input').forEach(radio => radio.checked = false);
}

function closeRatingModal() {
    document.getElementById('ratingModal').style.display = 'none';
}

function updateRatingDisplay(eventId, avgRating, ratingCount) {
    const ratingDisplay = document.getElementById(`rating-display-${eventId}`);
    const ratingCountDisplay = document.getElementById(`rating-count-${eventId}`);
    
    if (ratingDisplay) {
        const fullStars = Math.floor(avgRating);
        const halfStar = (avgRating - fullStars) >= 0.5;
        let starsHtml = '<div class="stars">';
        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                starsHtml += '★';
            } else if (i === fullStars + 1 && halfStar) {
                starsHtml += '⨯';
            } else {
                starsHtml += '☆';
            }
        }
        starsHtml += '</div>';
        ratingDisplay.innerHTML = starsHtml;
    }
    
    if (ratingCountDisplay) {
        ratingCountDisplay.textContent = ratingCount + (ratingCount === 1 ? ' rating' : ' ratings');
    }
}

// Handle form submission via AJAX
document.getElementById('ratingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (currentSubmitting) return;
    currentSubmitting = true;
    
    const eventId = document.getElementById('modal_event_id').value;
    const selectedRating = document.querySelector('input[name="rating"]:checked');
    
    if (!selectedRating) {
        showToast('Please select a rating', true);
        currentSubmitting = false;
        return;
    }
    
    const formData = new FormData();
    formData.append('event_id', eventId);
    formData.append('rating', selectedRating.value);
    
    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast(data.message);
            updateRatingDisplay(eventId, data.avg_rating, data.rating_count);
            closeRatingModal();
        } else {
            showToast(data.message, true);
        }
    } catch (error) {
        showToast('An error occurred. Please try again.', true);
    } finally {
        currentSubmitting = false;
    }
});

// Slideshow functions
function changeSlide(eventId, direction, event) {
    if (event) {
        event.stopPropagation();
    }
    const slideshow = document.querySelector(`#slideshow-${eventId} .image-slideshow`);
    if (!slideshow) return;
    
    const slides = slideshow.querySelectorAll('.slide');
    if (slides.length === 0) return;
    
    let currentIndex = -1;
    for (let i = 0; i < slides.length; i++) {
        if (slides[i].classList.contains('active')) {
            currentIndex = i;
            break;
        }
    }
    
    slides[currentIndex].classList.remove('active');
    let newIndex = currentIndex + direction;
    if (newIndex < 0) newIndex = slides.length - 1;
    if (newIndex >= slides.length) newIndex = 0;
    slides[newIndex].classList.add('active');
}

// Close modals when clicking outside
window.onclick = function(event) {
    const ratingModal = document.getElementById('ratingModal');
    if (event.target == ratingModal) {
        closeRatingModal();
    }
}
</script>

</body>
</html>