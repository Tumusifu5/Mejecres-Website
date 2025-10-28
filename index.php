<?php
include_once 'connection.php';

// Fetch announcements and gallery from database
$announcements = [];
$gallery = [];

try {
    // Get announcements
    $annResult = $conn->query("SELECT title, details, date FROM announcements ORDER BY date DESC LIMIT 10");
    if ($annResult) {
        $announcements = $annResult->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get gallery images
    $galleryResult = $conn->query("SELECT filename FROM gallery ORDER BY id DESC LIMIT 24");
    if ($galleryResult) {
        $gallery = $galleryResult->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    // Handle errors silently
    error_log("Database error in index.php: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>MEJECRES SCHOOL</title>
  <link rel="icon" href="logo.jpg" type="image/x-icon">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root{
      --primary-1: #023eaa;
      --primary-2: #0b4bd8;
      --primary-3: #003399;
      --accent: #ffd700;
      --accent-dark: #e6c200;
      --bg: #f4f6f8;
      --card-bg: #ffffff;
      --muted: #666;
      --light-gray: #eef2f7;
      --max-width: 1200px;
      --shadow-sm: 0 4px 6px rgba(0,0,0,0.05);
      --shadow-md: 0 8px 24px rgba(11,75,216,0.12);
      --shadow-lg: 0 16px 40px rgba(11,75,216,0.15);
      --border-radius: 16px;
      --transition: all 0.3s ease;
    }

    *{box-sizing:border-box}
    body {
      font-family: 'Poppins', sans-serif;
      margin: 0;
      background: var(--bg);
      color: #333;
      display:flex;
      flex-direction:column;
      min-height:100vh;
      overflow-x: hidden;
    }

    /* Header */
    header.site-header {
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      color: white;
      padding: 10px 16px;
      display: flex;
      justify-content: center;
      box-shadow: var(--shadow-md);
      position: sticky;
      top: 0;
      z-index: 1000;
      transition: var(--transition);
    }

    .header-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      max-width: var(--max-width);
      gap: 12px;
      padding: 6px 0;
      position: relative;
    }

    .brand { 
      display:flex; 
      align-items:center; 
      gap:12px; 
      transition: var(--transition);
    }
    
    header img.logo { 
      height:56px; 
      width:56px; 
      object-fit:cover; 
      border-radius:12px; 
      flex-shrink:0; 
      border: 2px solid rgba(255,255,255,0.2);
      transition: var(--transition);
    }
    
    header h1 { 
      font-size:clamp(18px,2.4vw,24px); 
      margin:0; 
      font-weight:800; 
      white-space:nowrap; 
      text-shadow:1px 1px 2px rgba(0,0,0,0.25);
      letter-spacing: 0.5px;
    }

    /* Navigation - FIXED: All links on one line */
    nav {
      display:flex; 
      flex-wrap: nowrap; /* Prevent wrapping */
      justify-content:center; 
      gap:6px; /* Reduced gap */
      align-items:center;
      position: relative;
      white-space: nowrap; /* Prevent text wrapping */
    }

    nav a {
      color:white; 
      text-decoration:none; 
      font-weight:600; 
      font-size:13px; /* Smaller font */
      padding:6px 10px; /* Compact padding */
      border-radius:8px;
      background:rgba(255,255,255,0.08);
      transition: var(--transition);
      position: relative;
      overflow: hidden;
      white-space: nowrap;
    }

    nav a:hover { 
      background:rgba(255,255,255,0.18); 
      color:white; 
      transform: translateY(-2px);
    }

    nav a::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      width: 0;
      height: 3px;
      background: var(--accent);
      transition: var(--transition);
    }

    nav a:hover::after {
      width: 100%;
    }

    /* Hide icons on medium screens to save space */
    @media (max-width: 1000px) {
      nav a i {
        display: none;
      }
    }

    .hamburger {
      display: none;
      font-size:28px; 
      cursor:pointer; 
      background:none; 
      border:none;
      color:white; 
      padding:6px 8px; 
      border-radius:8px;
      z-index:1000;
      transition: var(--transition);
    }

    .hamburger:hover {
      background: rgba(255,255,255,0.1);
    }

    /* Close button for mobile navigation */
    .close-menu {
      display: none;
      position: absolute;
      top: 20px;
      right: 20px;
      font-size: 28px;
      background: none;
      border: none;
      color: white;
      cursor: pointer;
      z-index: 1001;
      padding: 8px;
      border-radius: 8px;
      transition: var(--transition);
    }
    
    .close-menu:hover {
      background: rgba(255,255,255,0.1);
      transform: scale(1.1);
    }

    /* Updated mobile navigation styles */
    @media (max-width:768px){
      nav {
        display:none;
        flex-direction: column;
        gap: 0; /* Remove gap between links */
        background: var(--primary-2);
        position:fixed; /* Changed to fixed */
        top:0;
        left:0;
        width:100%;
        height:100vh; /* Full height */
        padding: 80px 0 20px 0; /* Added top padding */
        border-radius:0;
        box-shadow: var(--shadow-lg);
        white-space: normal;
        z-index: 999;
        overflow-y: auto;
      }

      nav.show { 
        display:flex; 
        animation: slideIn 0.3s ease; 
      }
      
      .close-menu.show {
        display: block;
        animation: fadeIn 0.3s ease;
      }
      
      /* Hide hamburger when menu is open */
      .hamburger.hide {
        display: none;
      }

      .hamburger { 
        display:block;
        position: relative;
        z-index: 1000;
      }
      
      nav a {
        font-size: 18px;
        padding: 16px 24px;
        white-space: normal;
        border-radius: 0;
        margin: 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        background: transparent;
      }

      nav a:last-child {
        border-bottom: none;
      }
      
      /* Show icons in mobile menu */
      nav a i {
        display: inline-block;
        margin-right: 12px;
        width: 20px;
        text-align: center;
      }

      nav a:hover {
        background: rgba(255,255,255,0.15);
        transform: none;
      }
    }

    @keyframes slideIn { 
      from{transform:translateX(-100%);} 
      to{transform:translateX(0);} 
    }

    @keyframes fadeIn { 
      from{opacity:0;transform:translateY(-10px);} 
      to{opacity:1;transform:translateY(0);} 
    }

    /* Hero Section with Image Slider - IMPROVED */
    .hero { 
      position:relative; 
      text-align:center; 
      overflow:hidden; 
      color:white; 
      height: 90vh;
      min-height: 600px;
      max-height: 900px;
    }
    
    /* Image Slider Styles - IMPROVED */
    .slider-container {
      position: relative;
      width: 100%;
      height: 100%;
      overflow: hidden;
    }
    
    .slider {
      display: flex;
      transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
      height: 100%;
    }
    
    .slide {
      min-width: 100%;
      height: 100%;
      position: relative;
    }
    
    .slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 10s ease;
      filter: brightness(0.8); /* ADDED: Subtle darken effect */
    }
    
    .slider:hover .slide img {
      transform: scale(1.05);
    }
    
    .slider-nav {
      position: absolute;
      bottom: 30px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 12px;
      z-index: 10;
    }
    
    .slider-dot {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.5);
      border: 2px solid white;
      cursor: pointer;
      transition: var(--transition);
    }
    
    .slider-dot.active {
      background: var(--accent);
      border-color: var(--accent);
      transform: scale(1.3);
    }
    
    .slider-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0, 0, 0, 0.4);
      color: white;
      border: none;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      font-size: 22px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
      z-index: 10;
      backdrop-filter: blur(4px);
    }
    
    .slider-arrow:hover {
      background: rgba(0, 0, 0, 0.7);
      transform: translateY(-50%) scale(1.1);
    }
    
    .slider-arrow.prev {
      left: 30px;
    }
    
    .slider-arrow.next {
      right: 30px;
    }
    
    @media (max-width: 768px) {
      .slider-arrow {
        width: 50px;
        height: 50px;
        font-size: 18px;
      }
      
      .slider-arrow.prev {
        left: 15px;
      }
      
      .slider-arrow.next {
        right: 15px;
      }
      
      .hero {
        height: 70vh;
        min-height: 500px;
      }
    }

    /* Hero Overlay - UPDATED: Subtle dark overlay */
    .hero-overlay { 
      position:absolute; 
      inset:0; 
      display:flex; 
      flex-direction:column; 
      align-items:center; 
      justify-content:center; 
      padding:24px; 
      background: rgba(0, 0, 0, 0.3); /* ADDED: Subtle dark overlay */
      text-align:center; 
      z-index: 5;
    }
    
    .hero-overlay h1 { 
      font-size:clamp(28px,5.2vw,56px); 
      margin:0 0 16px 0; 
      font-weight:800; 
      text-shadow: 2px 2px 8px rgba(0,0,0,0.4);
      line-height: 1.2;
    }
    
    .hero-overlay p { 
      font-size:clamp(16px,2.2vw,20px); 
      max-width:720px; 
      margin:0; 
      color:#fff; 
      opacity:0.95; 
      font-weight: 400;
      line-height: 1.6;
    }
    
    .hero-ctas { 
      margin-top:28px; 
      display:flex; 
      gap:16px; 
      flex-wrap:wrap; 
      justify-content:center; 
    }
    
    .btn { 
      border:none; 
      padding:14px 28px; 
      border-radius:12px; 
      cursor:pointer; 
      font-size:16px; 
      font-weight:700; 
      transition: var(--transition);
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: var(--shadow-sm);
    }
    
    .btn-primary { 
      background:var(--accent); 
      color:var(--primary-1); 
    }
    
    .btn-primary:hover { 
      background: var(--accent-dark);
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }
    
    .btn-ghost { 
      background:transparent; 
      border:2px solid rgba(255,255,255,0.9); 
      color:white; 
      padding:12px 24px; 
    }
    
    .btn-ghost:hover {
      background: rgba(255,255,255,0.15);
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }

    .container { 
      width:92%; 
      max-width:var(--max-width); 
      margin:40px auto; 
      flex:1 0 auto; 
    }

    /* Announcements - IMPROVED */
    .announcements { 
      background:var(--card-bg); 
      border-radius:var(--border-radius); 
      box-shadow:var(--shadow-md); 
      padding:40px; 
      position: relative;
      overflow: hidden;
    }
    
    .announcements::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-1), var(--accent));
    }
    
    .section-title { 
      font-size:clamp(24px,2.6vw,36px); 
      text-align:center; 
      color:var(--primary-1); 
      margin-bottom:30px; 
      font-weight:800;
      position: relative;
      display: inline-block;
      left: 50%;
      transform: translateX(-50%);
    }
    
    .section-title::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 0;
      width: 100%;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-1), var(--accent));
      border-radius: 2px;
    }
    
    .announcement-grid { 
      display:grid; 
      grid-template-columns:1fr; 
      gap:28px; 
    }
    
    .announcement-card { 
      background:#fff; 
      border-radius:14px; 
      box-shadow:var(--shadow-sm); 
      padding:24px; 
      border-left:6px solid var(--primary-2); 
      animation:fadeUp .55s ease forwards; 
      transition: var(--transition);
      position: relative;
      overflow: hidden;
    }
    
    .announcement-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(11,75,216,0.03), rgba(255,215,0,0.03));
      z-index: 0;
    }
    
    .announcement-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-md);
    }
    
    .announcement-card h3 { 
      margin:0 0 12px 0; 
      color:var(--primary-1); 
      font-size:1.4rem; 
      text-transform:uppercase; 
      font-weight: 700;
      position: relative;
      z-index: 1;
    }
    
    .announcement-card p { 
      margin:0 0 12px 0; 
      color:#333; 
      line-height:1.7; 
      position: relative;
      z-index: 1;
    }
    
    .announcement-card small { 
      color:var(--muted); 
      font-weight:500; 
      position: relative;
      z-index: 1;
    }

    /* Gallery - IMPROVED */
    .gallery-section {
      background:var(--card-bg); 
      border-radius:var(--border-radius); 
      box-shadow:var(--shadow-md); 
      padding:40px;
      margin-top: 40px;
      position: relative;
      overflow: hidden;
    }
    
    .gallery-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--accent), var(--primary-1));
    }
    
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 24px;
      justify-content: center;
    }
    
    .gallery-card {
      background: #fff;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: var(--shadow-sm);
      transition: var(--transition);
      display: flex;
      flex-direction: column;
      min-height: 240px;
      position: relative;
    }
    
    .gallery-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, rgba(11,75,216,0.1), rgba(255,215,0,0.1));
      opacity: 0;
      transition: var(--transition);
      z-index: 1;
    }
    
    .gallery-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
      flex: 1;
    }
    
    .gallery-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-lg);
    }
    
    .gallery-card:hover::before {
      opacity: 1;
    }
    
    .gallery-card:hover img {
      transform: scale(1.08);
    }

    /* Responsive Gallery for Mobile */
    @media (max-width: 1024px) {
      .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
      }
    }
    
    @media (max-width: 768px) {
      .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
      }
      
      .gallery-card {
        border-radius: 12px;
        min-height: 200px;
      }
      
      .announcements, .gallery-section {
        padding: 28px;
      }
    }
    
    @media (max-width: 480px) {
      .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }
      
      .gallery-card {
        border-radius: 10px;
        min-height: 160px;
      }
      
      .announcements, .gallery-section {
        padding: 20px;
      }
    }
    
    @media (max-width: 360px) {
      .gallery-grid {
        grid-template-columns: 1fr;
        gap: 12px;
      }
      
      .gallery-card {
        min-height: 220px;
      }
    }

    /* Google Map Section - IMPROVED */
    .map-section {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      box-shadow: var(--shadow-md);
      padding: 40px;
      margin-top: 40px;
      position: relative;
      overflow: hidden;
    }
    
    .map-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, var(--primary-1), var(--accent));
    }
    
    .map-container {
      width: 100%;
      height: 450px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: var(--shadow-md);
      border: 3px solid var(--primary-2);
      transition: var(--transition);
    }
    
    .map-container:hover {
      box-shadow: var(--shadow-lg);
    }
    
    .map-container iframe {
      width: 100%;
      height: 100%;
      border: none;
    }
    
    .map-info {
      text-align: center;
      margin-top: 20px;
      color: var(--muted);
      font-size: 16px;
      font-weight: 500;
    }
    
    /* Responsive Map */
    @media (max-width: 768px) {
      .map-container {
        height: 350px;
      }
      
      .map-section {
        padding: 28px;
      }
    }
    
    @media (max-width: 480px) {
      .map-container {
        height: 280px;
      }
    }

    /* Floating Action Button */
    .fab {
      position: fixed;
      bottom: 30px;
      right: 30px;
      width: 60px;
      height: 60px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--primary-1), var(--primary-2));
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      box-shadow: var(--shadow-lg);
      cursor: pointer;
      z-index: 100;
      transition: var(--transition);
      border: none;
      display: none; /* Hidden by default */
    }
    
    .fab:hover {
      transform: translateY(-5px) scale(1.1);
      box-shadow: 0 12px 30px rgba(11,75,216,0.3);
    }
    
    .fab.show {
      display: flex;
    }
    
    @media (max-width: 768px) {
      .fab {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 20px;
      }
    }

    /* Footer - IMPROVED */
    footer { 
      background: linear-gradient(135deg, var(--primary-3), var(--primary-1));
      color:white; 
      padding:24px 10px; 
      text-align:center; 
      font-size:15px; 
      margin-top: 60px;
    }
    
    footer p {
      margin: 0;
      font-weight: 500;
    }
    
    @keyframes fadeUp { 
      from{opacity:0;transform:translateY(18px);} 
      to{opacity:1;transform:translateY(0);} 
    }
    
    /* Loading Animation */
    .loading {
      display: inline-block;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255,255,255,.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
    
    /* Pulse Animation */
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    
    .pulse {
      animation: pulse 2s infinite;
    }
  </style>
</head>

<body>
  <header class="site-header">
    <div class="header-inner">
      <div class="brand">
        <img src="logo.jpg" alt="MEJECRES Logo" class="logo">
        <h1>MEJECRES SCHOOL</h1>
      </div>

      <button class="hamburger" id="hamburger">☰</button>

      <nav id="NavMenu">
        <button class="close-menu" id="closeMenu">✕</button>
        <a href="index.php"><i class="fas fa-home"></i> Home</a>
        <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
        <a href="gallery.php"><i class="fas fa-images"></i> School Gallery</a>
        <a href="teacher-login.php"><i class="fas fa-chalkboard-teacher"></i> Teachers</a>
        <a href="vision.php"><i class="fas fa-eye"></i> Vision & Values</a>
        <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
        <a href="admin-login.php"><i class="fas fa-user-shield"></i> Admin</a>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="slider-container">
      <div class="slider" id="slider">
        <div class="slide">
          <img src="74.jpg" alt="MEJECRES Campus View 1" loading="lazy">
        </div>
        <div class="slide">
          <img src="78.jpg" alt="MEJECRES Campus View 2" loading="lazy">
        </div>
        <div class="slide">
          <img src="90.jpg" alt="MEJECRES Campus View 3" loading="lazy">
        </div>
      </div>
      
      <!-- Navigation Arrows -->
      <button class="slider-arrow prev" onclick="prevSlide()">❮</button>
      <button class="slider-arrow next" onclick="nextSlide()">❯</button>
      
      <!-- Dots Navigation -->
      <div class="slider-nav">
        <div class="slider-dot active" onclick="goToSlide(0)"></div>
        <div class="slider-dot" onclick="goToSlide(1)"></div>
        <div class="slider-dot" onclick="goToSlide(2)"></div>
      </div>
    </div>
    
    <div class="hero-overlay">
      <h1>Welcome to MEJECRES SCHOOL</h1>
      <p>Nurturing future leaders with knowledge, discipline, and character. We provide quality education that empowers students to excel in academics and life.</p>
      <div class="hero-ctas">
        <button class="btn btn-primary" onclick="scrollToSection('announcements')">
          <i class="fas fa-bullhorn"></i> See Announcements
        </button>
        <button class="btn btn-ghost" onclick="scrollToSection('gallery-section')">
          <i class="fas fa-images"></i> Our Gallery
        </button>
        <button class="btn btn-ghost" onclick="scrollToSection('location-section')">
          <i class="fas fa-map-marker-alt"></i> Our Location
        </button>
      </div>
    </div>
  </section>

  <main class="container">
    <section id="announcements" class="announcements">
      <h2 class="section-title">📢 School Announcements</h2>
      <div class="announcement-grid">
        <?php if(empty($announcements)): ?>
          <p style="text-align:center;color:gray;padding:40px;">No announcements yet. Check back later for updates.</p>
        <?php else: ?>
          <?php foreach($announcements as $announcement): ?>
            <div class="announcement-card">
              <h3><?= strtoupper(htmlspecialchars($announcement['title'])) ?></h3>
              <p><?= nl2br(htmlspecialchars($announcement['details'] ?? 'No details available')) ?></p>
              <small><i class="fas fa-calendar-alt"></i> <?= htmlspecialchars($announcement['date']) ?></small>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <section id="gallery-section" class="gallery-section">
      <h2 class="section-title">📸 School Gallery</h2>
      <div class="gallery-grid">
        <?php if(empty($gallery)): ?>
          <p style="text-align:center;color:gray;grid-column:1/-1;padding:40px;">No photos uploaded yet. Check back later for updates.</p>
        <?php else: ?>
          <?php foreach($gallery as $image): ?>
            <?php
            $path = 'uploads/' . htmlspecialchars($image['filename']);
            // Check if file exists, if not use a placeholder
            $imgSrc = file_exists($path) ? $path : 'https://via.placeholder.com/300x200/0b4bd8/ffffff?text=MEJECRES+SCHOOL';
            ?>
            <div class="gallery-card">
              <img src="<?= $imgSrc ?>" alt="School Photo" loading="lazy">
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- Google Map Section -->
    <section id="location-section" class="map-section">
      <h2 class="section-title">📍 Our Location</h2>
      <div class="map-container">
        <iframe 
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d638.4486545021813!2d30.075727399999998!3d-2.0075253!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19dca7f7653d4183%3A0x1a13e810817fc511!2sMEJECRES%20SCHOOL!5e0!3m2!1sen!2srw!4v1700000000000!5m2!1sen!2srw" 
          allowfullscreen="" 
          loading="lazy" 
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
      <div class="map-info">
        <p><i class="fas fa-map-marker-alt"></i> MEJECRES SCHOOL, Rwanda | Find us easily using Google Maps</p>
      </div>
    </section>
  </main>

  <!-- Floating Action Button -->
  <button class="fab" id="fab" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
  </button>

  <footer>
    <p>&copy; 2025 MEJECRES SCHOOL | Designed by MUGISHA && TUMUSIFU </p>
  </footer>

<script>
// Image Slider Functionality - IMPROVED
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.slider-dot');
const totalSlides = slides.length;
let slideInterval;

function showSlide(index) {
  // Reset to first slide if at the end
  if (index >= totalSlides) {
    currentSlide = 0;
  } else if (index < 0) {
    currentSlide = totalSlides - 1;
  } else {
    currentSlide = index;
  }
  
  // Move the slider
  const slider = document.getElementById('slider');
  slider.style.transform = `translateX(-${currentSlide * 100}%)`;
  
  // Update dots
  dots.forEach((dot, i) => {
    dot.classList.toggle('active', i === currentSlide);
  });
}

function nextSlide() {
  showSlide(currentSlide + 1);
  resetAutoSlide();
}

function prevSlide() {
  showSlide(currentSlide - 1);
  resetAutoSlide();
}

function goToSlide(index) {
  showSlide(index);
  resetAutoSlide();
}

function startAutoSlide() {
  slideInterval = setInterval(() => {
    nextSlide();
  }, 6000); // Change slide every 6 seconds
}

function resetAutoSlide() {
  clearInterval(slideInterval);
  startAutoSlide();
}

// Initialize slider
document.addEventListener('DOMContentLoaded', () => {
  startAutoSlide();
});

// Mobile Navigation
const hamburger = document.getElementById('hamburger');
const nav = document.getElementById('NavMenu');
const closeMenu = document.getElementById('closeMenu');

function openMenu() {
  nav.classList.add('show');
  closeMenu.classList.add('show');
  hamburger.classList.add('hide');
}

function closeMenuFunc() {
  nav.classList.remove('show');
  closeMenu.classList.remove('show');
  hamburger.classList.remove('hide');
}

hamburger.addEventListener('click', (e)=>{
  e.stopPropagation();
  openMenu();
});

// Close menu when clicking the X button
closeMenu.addEventListener('click', (e)=>{
  e.stopPropagation();
  closeMenuFunc();
});

// Close menu when clicking outside
document.addEventListener('click', (e) => {
  if (window.innerWidth <= 768 && nav.classList.contains('show')) {
    if (!nav.contains(e.target) && e.target !== hamburger) {
      closeMenuFunc();
    }
  }
});

// Close menu when clicking on links
document.querySelectorAll('#NavMenu a').forEach(link=>{
  link.addEventListener('click', ()=>{
    if(window.innerWidth <= 768){
      closeMenuFunc();
    }
  });
});

// Existing navigation functions
function scrollToSection(id){
  const el = document.getElementById(id);
  if(el) el.scrollIntoView({behavior:'smooth'});
}

function scrollToTop() {
  window.scrollTo({top: 0, behavior: 'smooth'});
}

// Show/hide floating action button based on scroll position
const fab = document.getElementById('fab');
let lastScrollTop = 0;

window.addEventListener('scroll', () => {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  
  // Show/hide FAB
  if (scrollTop > 300) {
    fab.classList.add('show');
  } else {
    fab.classList.remove('show');
  }
  
  // Hide hamburger on scroll down
  if (window.innerWidth <= 768) {
    if (scrollTop > lastScrollTop && scrollTop > 100) {
      // Scrolling down - hide hamburger
      hamburger.style.opacity = '0';
      hamburger.style.transform = 'translateY(-10px)';
    } else {
      // Scrolling up - show hamburger
      hamburger.style.opacity = '1';
      hamburger.style.transform = 'translateY(0)';
    }
  }
  
  lastScrollTop = scrollTop;
});

window.addEventListener('resize', ()=>{
  if(window.innerWidth > 768){
    closeMenuFunc();
    hamburger.style.opacity = '1';
    hamburger.style.transform = 'translateY(0)';
  }
});

// Add loading animation to buttons on click
document.querySelectorAll('.btn').forEach(button => {
  button.addEventListener('click', function(e) {
    // Only add loading for buttons that trigger navigation
    if (this.getAttribute('onclick') && !this.getAttribute('onclick').includes('scrollTo')) {
      const originalText = this.innerHTML;
      this.innerHTML = '<span class="loading"></span> Loading...';
      this.disabled = true;
      
      // Reset after 2 seconds (for demo purposes)
      setTimeout(() => {
        this.innerHTML = originalText;
        this.disabled = false;
      }, 2000);
    }
  });
});
</script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    $conn->close();
}