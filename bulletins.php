<?php
// bulletins.php
require_once 'config.php';
require_once 'functions.php';
requireLogin();

$user = getCurrentUser();

// Ensure bulletins table has all required columns
$conn->query("
    CREATE TABLE IF NOT EXISTS bulletins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        category VARCHAR(50) DEFAULT 'general',
        created_by INT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )
");

// Check if category column exists, if not add it
$check_category = $conn->query("SHOW COLUMNS FROM bulletins LIKE 'category'");
if ($check_category->num_rows == 0) {
    $conn->query("ALTER TABLE bulletins ADD COLUMN category VARCHAR(50) DEFAULT 'general'");
}

// Check if created_by column exists, if not add it
$check_created_by = $conn->query("SHOW COLUMNS FROM bulletins LIKE 'created_by'");
if ($check_created_by->num_rows == 0) {
    $conn->query("ALTER TABLE bulletins ADD COLUMN created_by INT DEFAULT NULL");
    $conn->query("ALTER TABLE bulletins ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['action'] === 'create_bulletin') {
        $stmt = $conn->prepare("INSERT INTO bulletins (title, description, category, created_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $_POST['title'], $_POST['description'], $_POST['category'], $_SESSION['user_id']);
        $stmt->execute();
        $success = "Bulletin created successfully!";
    } elseif ($_POST['action'] === 'update_bulletin') {
        $stmt = $conn->prepare("UPDATE bulletins SET title=?, description=?, category=? WHERE id=?");
        $stmt->bind_param("sssi", $_POST['title'], $_POST['description'], $_POST['category'], $_POST['bulletin_id']);
        $stmt->execute();
        $success = "Bulletin updated successfully!";
    } elseif ($_POST['action'] === 'delete_bulletin') {
        $stmt = $conn->prepare("DELETE FROM bulletins WHERE id=?");
        $stmt->bind_param("i", $_POST['bulletin_id']);
        $stmt->execute();
        $success = "Bulletin deleted successfully!";
    }
}

// Fetch bulletins with LEFT JOIN to handle missing created_by
$result = $conn->query("SELECT b.*, u.name as author_name 
                        FROM bulletins b 
                        LEFT JOIN users u ON b.created_by = u.id 
                        ORDER BY b.created_at DESC");

if ($result) {
    $bulletins = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $bulletins = [];
}

// If no author_name is set (old records), set default
foreach ($bulletins as &$bulletin) {
    if (empty($bulletin['author_name'])) {
        $bulletin['author_name'] = 'Administrator';
    }
    if (empty($bulletin['category'])) {
        $bulletin['category'] = 'general';
    }
}

// Get categories for filter
$categories_result = $conn->query("SELECT DISTINCT category FROM bulletins WHERE category IS NOT NULL AND category != ''");
$categories = $categories_result ? $categories_result->fetch_all(MYSQLI_ASSOC) : [];

// Apply category filter
$filter_category = $_GET['category'] ?? '';
if ($filter_category && !empty($bulletins)) {
    $bulletins = array_filter($bulletins, function($b) use ($filter_category) {
        return $b['category'] === $filter_category;
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Bulletin - CampusConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,700;1,9..144,400&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    --indigo-mid: #2d3a8c;
    --indigo-light: #e8eaff;
    --muted: #6b6a75;
    --border: #e2dfd6;
    --sand: #f4f1e8;
    --teal: #2d6a6a;
    --teal-light: #e8f4f4;
    --success: #28a745;
    --danger: #dc3545;
    --warning: #ffc107;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 20px rgba(0,0,0,0.08);
    --shadow-lg: 0 8px 40px rgba(0,0,0,0.12);
    --shadow-hover: 0 12px 48px rgba(0,0,0,0.15);
}

body {
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(135deg, var(--cream) 0%, #fff8ec 100%);
    color: var(--ink);
    line-height: 1.6;
}

/* Animated Background */
body::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle at 20% 50%, rgba(232,105,32,0.03) 0%, transparent 50%);
    pointer-events: none;
    z-index: -1;
}

/* Navigation */
.top-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.2rem 3rem;
    background: rgba(255, 252, 240, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    position: sticky;
    top: 0;
    z-index: 100;
    animation: slideDown 0.5s ease;
}

@keyframes slideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.top-nav .logo {
    font-family: 'Fraunces', serif;
    font-weight: 700;
    font-size: 1.6rem;
    color: var(--indigo);
    text-decoration: none;
    transition: transform 0.2s;
}

.top-nav .logo:hover { transform: scale(1.05); }
.top-nav .logo span { color: var(--amber); }

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-mid) 100%);
    padding: 3rem 3rem;
    text-align: center;
    color: white;
    margin-bottom: 2rem;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '📢';
    position: absolute;
    font-size: 8rem;
    opacity: 0.1;
    right: -20px;
    bottom: -20px;
    transform: rotate(-15deg);
}

.hero-section h1 {
    font-family: 'Fraunces', serif;
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 1rem;
    animation: fadeInUp 0.6s ease;
}

.hero-section p {
    font-size: 1.2rem;
    opacity: 0.9;
    animation: fadeInUp 0.6s ease 0.1s both;
}

@keyframes fadeInUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Container */
.container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 2rem 3rem;
}

/* Alert Messages */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(-100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    color: #155724;
    border-left: 4px solid var(--success);
}

/* Create Bulletin Card */
.card-custom {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(5px);
    border-radius: 1.5rem;
    border: 1px solid var(--border);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.card-custom:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
}

.card-custom h2 {
    font-family: 'Fraunces', serif;
    font-size: 1.5rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

/* Form Styles */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    font-family: 'DM Sans', sans-serif;
    transition: all 0.2s;
    background: white;
}

.form-control:focus {
    outline: none;
    border-color: var(--amber);
    box-shadow: 0 0 0 3px rgba(232,105,32,0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Button Styles */
.btn {
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.btn-primary {
    background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-mid) 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(30,37,87,0.3);
}

.btn-warning {
    background: linear-gradient(135deg, var(--amber) 0%, #f0a045 100%);
    color: var(--ink);
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(232,105,32,0.3);
}

.btn-danger {
    background: linear-gradient(135deg, var(--danger) 0%, #c82333 100%);
    color: white;
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(220,53,69,0.3);
}

.btn-outline {
    background: transparent;
    border: 2px solid var(--border);
    color: var(--ink);
}

.btn-outline:hover {
    border-color: var(--amber);
    background: var(--amber-light);
}

/* Filter Bar */
.filter-bar {
    background: linear-gradient(135deg, var(--sand) 0%, #f8f5ed 100%);
    padding: 1rem 1.5rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-group label {
    font-weight: 600;
    color: var(--indigo);
}

.filter-group select {
    padding: 0.5rem 1rem;
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.filter-group select:focus {
    outline: none;
    border-color: var(--amber);
}

.filter-stats {
    color: var(--muted);
    font-size: 0.9rem;
}

/* Bulletin Grid */
.bulletin-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 2rem;
}

.bulletin-card {
    background: white;
    border-radius: 1.25rem;
    overflow: hidden;
    border: 1px solid var(--border);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    animation: fadeInUp 0.6s ease;
}

.bulletin-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-hover);
}

.bulletin-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--indigo) 0%, var(--indigo-mid) 100%);
    position: relative;
    overflow: hidden;
}

.bulletin-header::before {
    content: '✨';
    position: absolute;
    font-size: 5rem;
    opacity: 0.1;
    right: -10px;
    top: -10px;
}

.bulletin-category {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 2rem;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 1rem;
}

.category-academic { background: #e8eaff; color: var(--indigo); }
.category-event { background: #fdf3dc; color: var(--amber); }
.category-announcement { background: #e8f4f4; color: var(--teal); }
.category-urgent { background: #f8d7da; color: var(--danger); }
.category-general { background: var(--sand); color: var(--muted); }

.bulletin-title {
    font-family: 'Fraunces', serif;
    font-size: 1.3rem;
    font-weight: 700;
    color: white;
    line-height: 1.3;
}

.bulletin-body {
    padding: 1.5rem;
}

.bulletin-description {
    color: var(--ink);
    line-height: 1.6;
    margin-bottom: 1rem;
}

.bulletin-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
    margin-top: 1rem;
    font-size: 0.8rem;
    color: var(--muted);
}

.bulletin-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.bulletin-author i {
    color: var(--amber);
}

.bulletin-date {
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.bulletin-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.bulletin-card:hover .bulletin-actions {
    opacity: 1;
}

.action-icon {
    background: rgba(255,255,255,0.9);
    border: none;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.action-icon:hover {
    transform: scale(1.1);
}

.action-edit { color: var(--amber); }
.action-delete { color: var(--danger); }

/* Edit Modal */
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
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    border-radius: 1.5rem;
    max-width: 600px;
    width: 90%;
    padding: 2rem;
    position: relative;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
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
    transition: color 0.2s;
}

.close-modal:hover { color: var(--danger); }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem;
    color: var(--muted);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Responsive */
@media (max-width: 768px) {
    .top-nav { padding: 1rem 1.5rem; }
    .hero-section { padding: 2rem 1.5rem; }
    .hero-section h1 { font-size: 2rem; }
    .container { padding: 0 1rem 2rem; }
    .bulletin-grid { grid-template-columns: 1fr; }
    .form-grid { grid-template-columns: 1fr; }
    .filter-bar { flex-direction: column; align-items: stretch; }
}
    </style>
</head>
<body>

<div class="top-nav">
    <a href="index.php" class="logo">Campus<span>Connect</span></a>
    <div style="display: flex; align-items: center; gap: 1rem;">
        <i class="bi bi-megaphone" style="color: var(--amber);"></i>
        <span style="font-weight: 500;">Campus Bulletin</span>
    </div>
</div>

<div class="hero-section">
    <h1><i class="bi bi-megaphone-fill"></i> Campus Bulletin</h1>
    <p>Stay updated with the latest announcements, events, and news from around campus</p>
</div>

<div class="container">
    <?php if (isset($success)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Create Bulletin Form (Only for Admin/Organizer) -->
    <?php if ($user['role'] === 'admin' || $user['role'] === 'organizer'): ?>
    <div class="card-custom">
        <h2><i class="bi bi-pencil-square" style="color: var(--amber);"></i> Create New Bulletin</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create_bulletin">
            <div class="form-grid">
                <input class="form-control" name="title" placeholder="📢 Bulletin Title" required>
                <select class="form-control" name="category" required>
                    <option value="general">💬 General</option>
                    <option value="academic">📚 Academic</option>
                    <option value="event">🎉 Event</option>
                    <option value="announcement">📢 Announcement</option>
                    <option value="urgent">⚠️ Urgent</option>
                </select>
            </div>
            <textarea class="form-control" name="description" placeholder="Write your bulletin content here..." rows="4" required></textarea>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-send"></i> Publish Bulletin
            </button>
        </form>
    </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <div class="filter-bar">
        <div class="filter-group">
            <i class="bi bi-funnel"></i>
            <label>Filter by Category:</label>
            <form method="GET" style="display: inline;">
                <select name="category" onchange="this.form.submit()">
                    <option value="">📋 All Categories</option>
                    <option value="academic" <?php echo $filter_category === 'academic' ? 'selected' : ''; ?>>📚 Academic</option>
                    <option value="event" <?php echo $filter_category === 'event' ? 'selected' : ''; ?>>🎉 Event</option>
                    <option value="announcement" <?php echo $filter_category === 'announcement' ? 'selected' : ''; ?>>📢 Announcement</option>
                    <option value="urgent" <?php echo $filter_category === 'urgent' ? 'selected' : ''; ?>>⚠️ Urgent</option>
                    <option value="general" <?php echo $filter_category === 'general' ? 'selected' : ''; ?>>💬 General</option>
                </select>
            </form>
        </div>
        <div class="filter-stats">
            <i class="bi bi-newspaper"></i> Showing <?php echo count($bulletins); ?> bulletins
        </div>
    </div>

    <!-- Bulletins Grid -->
    <?php if (empty($bulletins)): ?>
        <div class="card-custom">
            <div class="empty-state">
                <i class="bi bi-megaphone"></i>
                <h3>No Bulletins Found</h3>
                <p>Check back later for updates from campus</p>
                <?php if ($user['role'] === 'admin' || $user['role'] === 'organizer'): ?>
                    <p style="margin-top: 1rem;">📝 Use the form above to create your first bulletin!</p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bulletin-grid">
            <?php foreach ($bulletins as $bulletin): 
                $category_class = '';
                $category_icon = '';
                switch($bulletin['category']) {
                    case 'academic':
                        $category_class = 'category-academic';
                        $category_icon = '📚';
                        break;
                    case 'event':
                        $category_class = 'category-event';
                        $category_icon = '🎉';
                        break;
                    case 'announcement':
                        $category_class = 'category-announcement';
                        $category_icon = '📢';
                        break;
                    case 'urgent':
                        $category_class = 'category-urgent';
                        $category_icon = '⚠️';
                        break;
                    default:
                        $category_class = 'category-general';
                        $category_icon = '💬';
                }
            ?>
                <div class="bulletin-card">
                    <div class="bulletin-header">
                        <span class="bulletin-category <?php echo $category_class; ?>">
                            <?php echo $category_icon; ?> <?php echo ucfirst($bulletin['category'] ?? 'General'); ?>
                        </span>
                        <div class="bulletin-title"><?php echo htmlspecialchars($bulletin['title']); ?></div>
                    </div>
                    <div class="bulletin-body">
                        <div class="bulletin-description">
                            <?php echo nl2br(htmlspecialchars($bulletin['description'])); ?>
                        </div>
                        <div class="bulletin-meta">
                            <div class="bulletin-author">
                                <i class="bi bi-person-circle"></i>
                                <span><?php echo htmlspecialchars($bulletin['author_name'] ?? 'Administrator'); ?></span>
                            </div>
                            <div class="bulletin-date">
                                <i class="bi bi-clock"></i>
                                <span><?php echo date('M d, Y \a\t g:i A', strtotime($bulletin['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php if ($user['role'] === 'admin' || $user['role'] === 'organizer'): ?>
                        <div class="bulletin-actions">
                            <button class="action-icon action-edit" onclick="openEditModal(<?php echo $bulletin['id']; ?>, '<?php echo addslashes($bulletin['title']); ?>', '<?php echo addslashes($bulletin['description']); ?>', '<?php echo $bulletin['category']; ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this bulletin permanently?')">
                                <input type="hidden" name="action" value="delete_bulletin">
                                <input type="hidden" name="bulletin_id" value="<?php echo $bulletin['id']; ?>">
                                <button class="action-icon action-delete" type="submit">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span><i class="bi bi-pencil-square"></i> Edit Bulletin</span>
            <button class="close-modal" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="update_bulletin">
            <input type="hidden" name="bulletin_id" id="edit_bulletin_id">
            
            <div class="form-grid">
                <input class="form-control" name="title" id="edit_title" placeholder="Bulletin Title" required>
                <select class="form-control" name="category" id="edit_category" required>
                    <option value="general">💬 General</option>
                    <option value="academic">📚 Academic</option>
                    <option value="event">🎉 Event</option>
                    <option value="announcement">📢 Announcement</option>
                    <option value="urgent">⚠️ Urgent</option>
                </select>
            </div>
            <textarea class="form-control" name="description" id="edit_description" rows="6" required></textarea>
            
            <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, title, description, category) {
    document.getElementById('edit_bulletin_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_description').value = description;
    document.getElementById('edit_category').value = category || 'general';
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</body>
</html>