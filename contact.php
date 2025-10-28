<?php
// ------------------ DATABASE CONNECTION ------------------
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mejecres_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ------------------ HANDLE FORM SUBMIT ------------------
$success = $error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $message = trim($_POST['message'] ?? '');
  $status = 'unread'; // default status

  if ($name === '' || $email === '' || $message === '') {
    $error = "Please fill all required fields!";
  } else {
    // SQL query matching your table structure
    $stmt = $conn->prepare("INSERT INTO messages (name, email, message, status, created_at) VALUES (?, ?, ?, ?, NOW())");
    
    if ($stmt) {
      $stmt->bind_param("ssss", $name, $email, $message, $status);
      
      if ($stmt->execute()) {
        $success = "✅ Your message has been sent successfully! We'll get back to you within 24 hours.";
        // Clear form fields
        $_POST = array();
      } else {
        $error = "❌ Something went wrong. Please try again.";
      }
      $stmt->close();
    } else {
      $error = "❌ Database error: " . $conn->error;
    }
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Contact - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
:root {
  --primary-1: #023eaa;
  --primary-2: #0b4bd8;
  --accent: #ffd700;
  --bg: #f4f6f8;
  --card-bg: #ffffff;
  --muted: #666;
  --success: #28a745;
  --danger: #dc3545;
  --warning: #ffc107;
  --info: #17a2b8;
}

* { box-sizing: border-box; }
body { 
  font-family: 'Poppins', sans-serif; 
  margin: 0; 
  background: var(--bg); 
  color: #333; 
  line-height: 1.6;
}

/* HEADER */
header.site-header { 
  background: linear-gradient(90deg, var(--primary-1), var(--primary-2)); 
  color: white; 
  padding: 12px 20px; 
  display: flex; 
  flex-direction: column; 
  align-items: center; 
  gap: 10px; 
  box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
  position: sticky; 
  top: 0; 
  z-index: 999; 
}
.header-inner { 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  width: 100%; 
  max-width: 1100px; 
  flex-wrap: wrap; 
}
.brand { display: flex; align-items: center; gap: 12px; }
header img.logo { height: 60px; width: 60px; border-radius: 10px; object-fit: cover; }
header h1 { font-size: 24px; margin: 0; font-weight: 700; text-shadow: 1px 1px 2px rgba(0,0,0,0.3); }
nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
nav a { 
  color: white; 
  text-decoration: none; 
  font-weight: bold; 
  font-size: 16px; 
  padding: 8px 12px; 
  border-radius: 8px; 
  background: rgba(255,255,255,0.1); 
  transition: all 0.3s ease; 
}
nav a:hover, nav a.active { color: var(--accent); background: rgba(255,255,255,0.2); }
.hamburger { display: none; font-size: 28px; cursor: pointer; background: none; border: none; color: white; }

@media(max-width:768px){
  nav { display: none; flex-direction: column; width: 100%; background: var(--primary-2); padding: 10px 0; }
  nav.show { display: flex; }
  .hamburger { display: block; }
}

/* MAIN CONTENT */
.container { 
  max-width: 1200px; 
  margin: 0 auto; 
  padding: 40px 20px; 
}

.hero-section {
  text-align: center;
  padding: 50px 20px;
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  border-radius: 15px;
  margin-bottom: 40px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.hero-section h1 {
  font-size: 2.8rem;
  margin-bottom: 15px;
  font-weight: 700;
}

.hero-section .subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 0;
}

/* CONTACT GRID */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 50px;
}

@media (max-width: 968px) {
  .contact-grid {
    grid-template-columns: 1fr;
    gap: 30px;
  }
}

/* CONTACT INFO */
.contact-info { 
  background: white;
  padding: 40px 30px;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.contact-info h2 {
  color: var(--primary-1);
  margin-bottom: 30px;
  font-size: 28px;
  text-align: center;
  border-bottom: 3px solid var(--accent);
  padding-bottom: 10px;
}

.info-item {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 30px;
  padding: 20px;
  border-radius: 10px;
  background: #f8f9fa;
  transition: all 0.3s ease;
}

.info-item:hover {
  background: #e9ecef;
  transform: translateX(5px);
}

.info-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  font-size: 24px;
  flex-shrink: 0;
}

.info-content h3 {
  color: var(--primary-1);
  margin: 0 0 8px 0;
  font-size: 18px;
}

.info-content p {
  color: var(--muted);
  margin: 0;
  font-size: 15px;
}

/* BUSINESS HOURS */
.business-hours {
  background: white;
  padding: 30px;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
  margin-top: 30px;
}

.business-hours h3 {
  color: var(--primary-1);
  margin-bottom: 20px;
  text-align: center;
  font-size: 22px;
}

.hours-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.hours-list li {
  display: flex;
  justify-content: space-between;
  padding: 12px 0;
  border-bottom: 1px solid #e9ecef;
  color: var(--muted);
}

.hours-list li:last-child {
  border-bottom: none;
}

.hours-list .day {
  font-weight: 600;
  color: var(--primary-1);
}

/* CONTACT FORM */
.contact-form { 
  background: white;
  padding: 40px 30px;
  border-radius: 15px;
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.contact-form h2 {
  color: var(--primary-1);
  margin-bottom: 30px;
  font-size: 28px;
  text-align: center;
  border-bottom: 3px solid var(--accent);
  padding-bottom: 10px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  color: var(--primary-1);
  font-weight: 600;
  font-size: 14px;
}

.form-group input, 
.form-group textarea {
  width: 100%;
  padding: 14px 16px;
  border: 2px solid #e1e5e9;
  border-radius: 8px;
  font-size: 16px;
  font-family: 'Poppins', sans-serif;
  transition: all 0.3s ease;
  background: #f8f9fa;
}

.form-group input:focus, 
.form-group textarea:focus {
  outline: none;
  border-color: var(--primary-2);
  background: white;
  box-shadow: 0 0 0 3px rgba(11, 75, 216, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 120px;
}

.submit-btn {
  background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
  color: white;
  border: none;
  padding: 16px 32px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 16px;
  font-weight: 600;
  width: 100%;
  transition: all 0.3s ease;
  margin-top: 10px;
}

.submit-btn:hover {
  background: linear-gradient(135deg, var(--primary-2), var(--primary-1));
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(11, 75, 216, 0.3);
}

/* MAP SECTION */
.map-section {
  margin-top: 50px;
  text-align: center;
}

.map-section h2 {
  color: var(--primary-1);
  margin-bottom: 30px;
  font-size: 28px;
}

.map-placeholder {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 15px;
  padding: 60px 20px;
  color: white;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.map-placeholder i {
  font-size: 4rem;
  margin-bottom: 20px;
  display: block;
}

.map-placeholder h3 {
  font-size: 1.5rem;
  margin-bottom: 10px;
}

/* ALERTS */
.alert { 
  margin: 20px 0; 
  padding: 16px 20px; 
  border-radius: 10px; 
  font-weight: 600;
  text-align: center;
  border-left: 5px solid;
}

.alert.success { 
  background: #e6f7ea; 
  color: var(--success); 
  border-color: #b7e1c6; 
}
.alert.error { 
  background: #ffe6e6; 
  color: var(--danger); 
  border-color: #f1bcbc; 
}

/* FOOTER */
footer { 
  background: linear-gradient(90deg, var(--primary-1), var(--primary-2)); 
  color: white; 
  text-align: center; 
  padding: 30px 20px; 
  margin-top: 60px;
  border-radius: 15px 15px 0 0;
}

footer p {
  margin: 5px 0;
  font-weight: 500;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .hero-section h1 {
    font-size: 2.2rem;
  }
  
  .contact-info,
  .contact-form {
    padding: 25px 20px;
  }
  
  .info-item {
    flex-direction: column;
    text-align: center;
    gap: 15px;
  }
  
  .map-placeholder {
    padding: 40px 20px;
  }
}
</style>
</head>
<body>

<header class="site-header" id="siteHeader">
  <div class="header-inner">
    <div class="brand">
      <img src="logo.jpg" alt="MEJECRES Logo" class="logo">
      <h1>MEJECRES SCHOOL</h1>
    </div>
    <button class="hamburger" id="hamburger">☰</button>
    <nav id="NavMenu">
      <a href="index.php">Home</a>
      <a href="about.php">About</a>
      <a href="vision.php">Vision & Values</a>
      <a href="contact.php" class="active">Contact</a>
      <a href="admin-login.php">Admin</a>
    </nav>
  </div>
</header>

<div class="container">
  <!-- HERO SECTION -->
  <div class="hero-section">
    <h1>Contact Us</h1>
    <div class="subtitle">We're here to help! Get in touch with MEJECRES SCHOOL</div>
  </div>

  <!-- CONTACT GRID -->
  <div class="contact-grid">
    <!-- CONTACT INFORMATION -->
    <div class="contact-info">
      <h2>Get In Touch</h2>
      
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="info-content">
          <h3>Our Location</h3>
          <p>MEJECRES SCHOOL<br>Kigali, Rwanda<br>East Africa</p>
        </div>
      </div>
      
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-phone"></i>
        </div>
        <div class="info-content">
          <h3>Phone Numbers</h3>
          <p>+250 788 123 456 (Main)<br>+250 789 987 654 (Admissions)<br>+250 788 555 666 (Emergency)</p>
        </div>
      </div>
      
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="info-content">
          <h3>Email Addresses</h3>
          <p>info@mejecres.ac.rw (General)<br>admissions@mejecres.ac.rw (Admissions)<br>principal@mejecres.ac.rw (Principal)</p>
        </div>
      </div>
      
      <div class="info-item">
        <div class="info-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="info-content">
          <h3>Office Hours</h3>
          <p>Monday - Friday: 7:00 AM - 5:00 PM<br>Saturday: 8:00 AM - 1:00 PM<br>Sunday: Closed</p>
        </div>
      </div>

      <!-- BUSINESS HOURS -->
      <div class="business-hours">
        <h3>School Hours</h3>
        <ul class="hours-list">
          <li><span class="day">Monday - Friday</span> <span>7:00 AM - 4:00 PM</span></li>
          <li><span class="day">Extra Classes</span> <span>4:00 PM - 5:30 PM</span></li>
          <li><span class="day">Saturday</span> <span>8:00 AM - 1:00 PM</span></li>
          <li><span class="day">Sunday</span> <span>Closed</span></li>
          <li><span class="day">Holidays</span> <span>As per academic calendar</span></li>
        </ul>
      </div>
    </div>

    <!-- CONTACT FORM -->
    <div class="contact-form">
      <h2>Send Us a Message</h2>
      
      <?php if ($success): ?>
        <div class="alert success"><?= $success ?></div>
      <?php endif; ?>
      
      <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="name">Full Name *</label>
          <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        </div>
        
        <div class="form-group">
          <label for="email">Email Address *</label>
          <input type="email" id="email" name="email" placeholder="Enter your email address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        
        <div class="form-group">
          <label for="message">Your Message *</label>
          <textarea id="message" name="message" placeholder="Please type your message here..." required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        </div>
        
        <button type="submit" class="submit-btn">
          <i class="fas fa-paper-plane"></i> Send Message
        </button>
      </form>
    </div>
  </div>

  <!-- MAP SECTION -->
  <div class="map-section">
    <h2>Find Our Location</h2>
    <div class="map-placeholder">
      <i class="fas fa-map-marked-alt"></i>
      <h3>MEJECRES SCHOOL Campus</h3>
      <p>Kigali, Rwanda | East Africa</p>
      <p style="margin-top: 15px; opacity: 0.9;">
        <i class="fas fa-info-circle"></i> 
        Interactive map coming soon. For now, please use the contact information above.
      </p>
    </div>
  </div>
</div>

<footer>
  <p>&copy; 2025 MEJECRES SCHOOL | Excellence in Education Since 2023</p>
  <p>Designed by MUGISHA & TUMUSIFU</p>
  <p style="margin-top: 15px; font-size: 0.9rem; opacity: 0.8;">
    <i class="fas fa-clock"></i> Response Time: We typically respond within 24 hours during business days
  </p>
</footer>

<script>
const hamburger = document.getElementById('hamburger');
const nav = document.getElementById('NavMenu');

hamburger.addEventListener('click', () => {
  nav.classList.toggle('show');
  hamburger.textContent = nav.classList.contains('show') ? '✖' : '☰';
});

document.querySelectorAll('#NavMenu a').forEach(link => {
  link.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
      nav.classList.remove('show');
      hamburger.textContent = '☰';
    }
  });
});

// Close menu when clicking outside on mobile
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 768 && nav.classList.contains('show')) {
    if (!nav.contains(e.target) && !hamburger.contains(e.target)) {
      nav.classList.remove('show');
      hamburger.textContent = '☰';
    }
  }
});

// Form validation enhancement
document.querySelector('form').addEventListener('submit', function(e) {
  const email = document.getElementById('email').value;
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  
  if (!emailRegex.test(email)) {
    e.preventDefault();
    alert('Please enter a valid email address.');
    return false;
  }
});
</script>

</body>
</html>