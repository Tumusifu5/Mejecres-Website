<?php
session_start();
include 'connection.php'; // DB connection

if (!isset($_SESSION['admin_username'])) {
  header("Location: admin-login.php");
  exit;
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
  $id = intval($_POST['delete_id']);
  $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
  $id = intval($_POST['mark_read']);
  $stmt = $conn->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $stmt->close();
}

// Fetch messages
$result = $conn->query("SELECT id, name, email, message, status, created_at FROM messages ORDER BY created_at DESC");
$messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Get message stats
$total_messages = count($messages);
$unread_messages = count(array_filter($messages, function($msg) { 
    return ($msg['status'] ?? 'unread') === 'unread'; 
}));
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Messages | MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
  --primary: #4361ee;
  --primary-dark: #3a56d4;
  --secondary: #7209b7;
  --success: #4cc9f0;
  --danger: #f72585;
  --warning: #ff9e00;
  --light: #f8f9fa;
  --dark: #212529;
  --bg: #f5f7fa;
  --card-bg: #ffffff;
  --sidebar-width: 260px;
  --card-shadow: 0 6px 18px rgba(0,0,0,0.08);
  --card-hover: 0 12px 24px rgba(0,0,0,0.12);
  --whatsapp-green: #25d366;
  --whatsapp-light: #dcf8c6;
  --whatsapp-dark: #075e54;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'Poppins', sans-serif;
  background: var(--bg);
  color: var(--dark);
  display: flex;
  min-height: 100vh;
  overflow-x: hidden;
}

/* Sidebar Styles */
.sidebar {
  width: var(--sidebar-width);
  background: linear-gradient(180deg, var(--primary-dark), var(--primary));
  padding: 2rem 0;
  display: flex;
  flex-direction: column;
  transition: all 0.3s ease;
  box-shadow: 2px 0 10px rgba(0,0,0,0.1);
  z-index: 100;
  position: fixed;
  height: 100vh;
  overflow-y: auto;
}

.sidebar-header {
  padding: 0 1.5rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  margin-bottom: 1rem;
}

.sidebar-header h2 {
  color: white;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-header h2 i {
  color: #4cc9f0;
}

.sidebar a {
  color: white;
  text-decoration: none;
  padding: 14px 20px;
  border-radius: 8px;
  font-weight: 500;
  transition: 0.2s;
  margin: 4px 15px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.sidebar a i {
  width: 20px;
  text-align: center;
}

.sidebar a:hover {
  background: rgba(255,255,255,0.15);
  transform: translateX(5px);
}

.sidebar a.active {
  background: rgba(255,255,255,0.2);
  border-left: 4px solid var(--success);
}

.sidebar-footer {
  margin-top: auto;
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(255,255,255,0.1);
  color: rgba(255,255,255,0.7);
  font-size: 0.85rem;
}

.sidebar-toggle {
  display: none;
  position: fixed;
  top: 15px;
  left: 15px;
  background: white;
  border: none;
  border-radius: 8px;
  padding: 10px;
  cursor: pointer;
  z-index: 1000;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  width: 44px;
  height: 44px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 4px;
}

.toggle-line {
  width: 22px;
  height: 2px;
  background-color: var(--primary);
  transition: all 0.3s ease;
}

.sidebar-toggle.active .toggle-line:nth-child(1) {
  transform: translateY(6px) rotate(45deg);
}

.sidebar-toggle.active .toggle-line:nth-child(2) {
  opacity: 0;
}

.sidebar-toggle.active .toggle-line:nth-child(3) {
  transform: translateY(-6px) rotate(-45deg);
}

/* Main Content Styles */
.main-content {
  flex: 1;
  padding: 20px;
  transition: all 0.3s ease;
  margin-left: var(--sidebar-width);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1rem 0;
  border-bottom: 1px solid #e0e0e0;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: linear-gradient(45deg, var(--primary), var(--secondary));
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
}

.page-title {
  background: linear-gradient(135deg, var(--whatsapp-dark), var(--whatsapp-green));
  color: white;
  font-size: 24px;
  font-weight: 600;
  text-align: center;
  padding: 25px;
  border-radius: 16px;
  margin-bottom: 2rem;
  box-shadow: var(--card-shadow);
}

/* Stats Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 25px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  text-align: center;
  transition: 0.3s;
  border-left: 4px solid var(--whatsapp-green);
}

.stat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--card-hover);
}

.stat-card i {
  font-size: 2.5rem;
  margin-bottom: 15px;
  color: var(--whatsapp-green);
}

.stat-number {
  font-size: 2rem;
  font-weight: 700;
  color: var(--dark);
  margin-bottom: 5px;
}

.stat-label {
  color: var(--dark);
  font-weight: 500;
  font-size: 0.9rem;
}

/* WhatsApp-style Messages Container */
.messages-container {
  background: white;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  overflow: hidden;
  margin-bottom: 25px;
}

.messages-header {
  background: linear-gradient(135deg, var(--whatsapp-dark), var(--whatsapp-green));
  color: white;
  padding: 20px;
  display: flex;
  align-items: center;
  gap: 15px;
}

.messages-header i {
  font-size: 1.5rem;
}

.messages-header h2 {
  margin: 0;
  font-size: 1.3rem;
  font-weight: 600;
}

.messages-list {
  max-height: 600px;
  overflow-y: auto;
  padding: 0;
}

/* Individual Message Styles */
.message-item {
  padding: 20px;
  border-bottom: 1px solid #e9ecef;
  transition: all 0.3s ease;
  position: relative;
}

.message-item:hover {
  background: #f8f9fa;
}

.message-item.unread {
  background: #f0f9ff;
  border-left: 4px solid var(--whatsapp-green);
}

.message-header {
  display: flex;
  justify-content: between;
  align-items: flex-start;
  margin-bottom: 10px;
  gap: 10px;
}

.sender-info {
  flex: 1;
}

.sender-name {
  font-weight: 600;
  color: var(--dark);
  font-size: 1.1rem;
  margin-bottom: 4px;
}

.sender-email {
  color: var(--primary);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.message-time {
  color: #6c757d;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 4px;
}

.message-content {
  background: var(--whatsapp-light);
  padding: 15px;
  border-radius: 12px;
  margin: 10px 0;
  border: 1px solid #e9ecef;
  position: relative;
}

.message-content::before {
  content: '';
  position: absolute;
  top: -8px;
  left: 20px;
  width: 0;
  height: 0;
  border-left: 8px solid transparent;
  border-right: 8px solid transparent;
  border-bottom: 8px solid var(--whatsapp-light);
}

.message-text {
  color: var(--dark);
  line-height: 1.5;
  margin: 0;
}

.message-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 15px;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 20px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.85rem;
  text-decoration: none;
}

.btn-read {
  background: var(--whatsapp-green);
  color: white;
}

.btn-read:hover {
  background: #1ebe5d;
  transform: translateY(-2px);
}

.btn-delete {
  background: var(--danger);
  color: white;
}

.btn-delete:hover {
  background: #e0006d;
  transform: translateY(-2px);
}

.btn-reply {
  background: var(--primary);
  color: white;
}

.btn-reply:hover {
  background: var(--primary-dark);
  transform: translateY(-2px);
}

.status-badge {
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.status-unread {
  background: #ffe6e6;
  color: #d63031;
}

.status-read {
  background: #e8f5e9;
  color: #2e7d32;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #6c757d;
}

.empty-state i {
  font-size: 4rem;
  margin-bottom: 20px;
  color: #dee2e6;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 10px;
  color: #6c757d;
}

.empty-state p {
  font-size: 1rem;
  color: #6c757d;
}

/* Search Container */
.search-container {
  background: white;
  padding: 20px;
  border-radius: 16px;
  box-shadow: var(--card-shadow);
  margin-bottom: 2rem;
}

.search-input {
  width: 100%;
  padding: 14px 20px;
  border: 2px solid #e9ecef;
  border-radius: 25px;
  font-size: 15px;
  transition: 0.3s;
  background: #f8f9fa;
}

.search-input:focus {
  border-color: var(--whatsapp-green);
  outline: none;
  box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.1);
  background: white;
}

/* Responsive Styles */
@media (max-width: 1024px) {
  .sidebar {
    left: -270px;
  }
  
  .sidebar.active {
    left: 0;
  }
  
  .sidebar-toggle {
    display: flex;
  }
  
  .main-content {
    margin-left: 0;
    padding-top: 70px;
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .message-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .message-time {
    align-self: flex-end;
  }
  
  .message-actions {
    flex-wrap: wrap;
    justify-content: center;
  }
  
  .btn {
    flex: 1;
    min-width: 120px;
    justify-content: center;
  }
}

@media (min-width: 1025px) {
  .sidebar-toggle {
    display: none;
  }
}

/* Custom scrollbar for messages */
.messages-list::-webkit-scrollbar {
  width: 6px;
}

.messages-list::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.messages-list::-webkit-scrollbar-thumb {
  background: var(--whatsapp-green);
  border-radius: 3px;
}

.messages-list::-webkit-scrollbar-thumb:hover {
  background: #1ebe5d;
}
</style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
        <div class="toggle-line"></div>
        <div class="toggle-line"></div>
        <div class="toggle-line"></div>
    </button>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-graduation-cap"></i> MEJECRES ADMIN</h2>
        </div>
        <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="all-teachers.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
        <a href="all-students.php"><i class="fas fa-user-graduate"></i> All Students</a>
        <a href="student.php"><i class="fas fa-plus-circle"></i> Add Students</a>
        <a href="attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a>
        <a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        
        <div class="sidebar-footer">
            <p>&copy; 2023 MEJECRES SCHOOL</p>
            <p>Admin Panel v2.0</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <div class="header">
            <h1>Message Center</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($_SESSION['admin_username'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['admin_username']) ?></div>
                    <div style="font-size: 0.85rem; color: #6c757d;">Message Administrator</div>
                </div>
            </div>
        </div>

        <div class="page-title">
           User Messages & Inquiries
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-envelope"></i>
                <div class="stat-number"><?= $total_messages ?></div>
                <div class="stat-label">Total Messages</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope-open"></i>
                <div class="stat-number"><?= $total_messages - $unread_messages ?></div>
                <div class="stat-label">Read Messages</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-envelope"></i>
                <div class="stat-number"><?= $unread_messages ?></div>
                <div class="stat-label">Unread Messages</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="stat-number">24h</div>
                <div class="stat-label">Response Time</div>
            </div>
        </div>

        <!-- Search Container -->
        <div class="search-container">
            <input type="text" id="searchInput" class="search-input" placeholder="Search messages by name, email, or content...">
        </div>

        <!-- Messages Container -->
        <div class="messages-container">
            <div class="messages-header">
                <i class="fab fa-whatsapp"></i>
                <h2>User Conversations</h2>
            </div>
            
            <?php if(empty($messages)): ?>
            <div class="empty-state">
                <i class="fas fa-comments"></i>
                <h3>No Messages Yet</h3>
                <p>When users send messages, they will appear here.</p>
            </div>
            <?php else: ?>
            <div class="messages-list" id="messagesList">
                <?php foreach($messages as $message): 
                    $isUnread = ($message['status'] ?? 'unread') === 'unread';
                    $messageTime = date('M j, Y g:i A', strtotime($message['created_at']));
                ?>
                <div class="message-item <?= $isUnread ? 'unread' : '' ?>" data-search="<?= htmlspecialchars(strtolower($message['name'] . ' ' . $message['email'] . ' ' . $message['message'])) ?>">
                    <div class="message-header">
                        <div class="sender-info">
                            <div class="sender-name">
                                <?= htmlspecialchars($message['name']) ?>
                                <span class="status-badge <?= $isUnread ? 'status-unread' : 'status-read' ?>">
                                    <i class="fas fa-<?= $isUnread ? 'envelope' : 'envelope-open' ?>"></i>
                                    <?= $isUnread ? 'Unread' : 'Read' ?>
                                </span>
                            </div>
                            <div class="sender-email">
                                <i class="fas fa-envelope"></i>
                                <?= htmlspecialchars($message['email']) ?>
                            </div>
                        </div>
                        <div class="message-time">
                            <i class="fas fa-clock"></i>
                            <?= $messageTime ?>
                        </div>
                    </div>
                    
                    <div class="message-content">
                        <p class="message-text"><?= htmlspecialchars($message['message']) ?></p>
                    </div>
                    
                    <div class="message-actions">
                        <?php if($isUnread): ?>
                        <form method="post" style="display: inline;">
                            <input type="hidden" name="mark_read" value="<?= $message['id'] ?>">
                            <button type="submit" class="btn btn-read">
                                <i class="fas fa-check"></i> Mark as Read
                            </button>
                        </form>
                        <?php endif; ?>
                        
                        <button class="btn btn-reply" onclick="replyToMessage('<?= htmlspecialchars($message['email']) ?>', '<?= htmlspecialchars($message['name']) ?>')">
                            <i class="fas fa-reply"></i> Reply
                        </button>
                        
                        <form method="post" onsubmit="return confirm('Are you sure you want to delete this message?')" style="display: inline;">
                            <input type="hidden" name="delete_id" value="<?= $message['id'] ?>">
                            <button type="submit" class="btn btn-delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Toggle sidebar on mobile
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarToggle.classList.toggle('active');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (event) => {
            if (window.innerWidth <= 1024) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickInsideToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickInsideToggle && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                    sidebarToggle.classList.remove('active');
                }
            }
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.innerWidth > 1024) {
                sidebar.classList.remove('active');
                sidebarToggle.classList.remove('active');
            }
        });

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const messageItems = document.querySelectorAll('.message-item');
            
            messageItems.forEach(item => {
                const searchData = item.getAttribute('data-search');
                if (searchData.includes(searchTerm)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        function replyToMessage(email, name) {
            const subject = encodeURIComponent(`Re: Your message to MEJECRES SCHOOL`);
            const body = encodeURIComponent(`Dear ${name},\n\nThank you for your message. We have received it and will get back to you soon.\n\nBest regards,\nMEJECRES SCHOOL Admin`);
            window.open(`mailto:${email}?subject=${subject}&body=${body}`, '_blank');
        }

        // Auto-scroll to top when searching
        document.getElementById('searchInput').addEventListener('input', function() {
            document.getElementById('messagesList').scrollTop = 0;
        });

        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const messageItems = document.querySelectorAll('.message-item');
            messageItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.1}s`;
                item.style.animation = 'fadeInUp 0.5s ease forwards';
            });
        });

        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .message-item {
                opacity: 0;
            }
        `;
        document.head.appendChild(style);
    </script>

</body>
</html>