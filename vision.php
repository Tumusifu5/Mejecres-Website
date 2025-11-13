<?php
// vision.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vision & Values - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
  --blue-600: #0b4bd8;
  --blue-700: #023eaa;
  --gold: #ffd166;
  --muted: #6b7280;
  --bg: #f4f7fb;
  --radius: 12px;
  --shadow: 0 12px 36px rgba(4,12,30,0.06);
  --success: #28a745;
  --warning: #ffc107;
  --info: #17a2b8;
}

body {
  font-family: "Poppins", Arial, sans-serif;
  margin: 0;
  background: linear-gradient(180deg, var(--bg), #eef4ff 60%);
  color: #123;
  scroll-behavior: smooth;
  overflow-x: hidden;
}

/* HEADER */
header {
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
  padding: 16px 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  position: sticky;
  top: 0;
  z-index: 999;
  box-shadow: 0 8px 28px rgba(2,46,102,0.12);
}

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand img {
  width: 64px;
  height: 64px;
  border-radius: 10px;
  object-fit: cover;
}

.brand h1 {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
}

/* HAMBURGER MENU */
.hamburger {
  display: none;
  background: none;
  border: none;
  font-size: 30px;
  color: white;
  cursor: pointer;
}

/* NAVIGATION */
nav {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  justify-content: center;
  transition: all 0.3s ease;
}

nav a {
  padding: 8px 14px;
  font-weight: 600;
  color: white;
  border-radius: 10px;
  background: rgba(255,255,255,0.1);
  text-decoration: none;
  transition: 0.3s ease;
}

nav a:hover {
  background: rgba(255,255,255,0.25);
  color: #ffd700;
}

nav a.active {
  background: rgba(255,255,255,0.25);
  color: #ffd700;
}

/* Responsive Nav */
@media (max-width: 768px) {
  nav {
    display: none;
    flex-direction: column;
    width: 100%;
    background: var(--blue-700);
    padding: 10px 0;
    text-align: center;
  }

  nav.show {
    display: flex;
  }

  .hamburger {
    display: block;
  }
}

/* MAIN CONTENT */
.container {
  max-width: 1100px;
  margin: 40px auto;
  padding: 0 18px 80px;
}

.hero-section {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, var(--blue-700), var(--blue-600));
  color: white;
  border-radius: var(--radius);
  margin-bottom: 40px;
  box-shadow: var(--shadow);
}

.hero-section h1 {
  font-size: 2.5rem;
  margin-bottom: 15px;
  font-weight: 700;
}

.hero-section .subtitle {
  font-size: 1.2rem;
  opacity: 0.9;
  margin-bottom: 0;
}

.section {
  background: white;
  padding: 40px;
  border-radius: var(--radius);
  margin-bottom: 30px;
  box-shadow: var(--shadow);
}

.section h2 {
  color: var(--blue-700);
  margin-bottom: 20px;
  font-size: 28px;
  text-align: center;
  border-bottom: 3px solid var(--gold);
  padding-bottom: 10px;
  display: inline-block;
}

.section p {
  font-size: 16px;
  line-height: 1.7;
  color: var(--muted);
  margin-bottom: 20px;
}

/* CORE VALUES */
.values-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 25px;
  margin-top: 30px;
}

.box {
  background: white;
  padding: 30px 20px;
  border-radius: var(--radius);
  width: 280px;
  box-shadow: var(--shadow);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  text-align: center;
  border-top: 5px solid var(--blue-700);
}

.box i {
  font-size: 2.5rem;
  color: var(--blue-700);
  margin-bottom: 15px;
}

.box h3 {
  color: var(--blue-700);
  margin-bottom: 15px;
  font-size: 22px;
}

.box p {
  font-size: 15px;
  color: var(--muted);
  line-height: 1.6;
  margin-bottom: 0;
}

.box:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(2,46,102,0.15);
}

/* MISSION SECTION */
.mission-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
  margin-top: 30px;
}

.mission-card {
  background: linear-gradient(135deg, #f8f9ff, #eef4ff);
  padding: 25px;
  border-radius: var(--radius);
  border-left: 5px solid var(--blue-700);
}

.mission-card h3 {
  color: var(--blue-700);
  margin-bottom: 15px;
  font-size: 20px;
}

.mission-card ul {
  text-align: left;
  padding-left: 20px;
}

.mission-card li {
  margin-bottom: 10px;
  color: var(--muted);
  line-height: 1.5;
}

/* PHILOSOPHY SECTION */
.philosophy-content {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 30px;
  margin-top: 30px;
}

.philosophy-item {
  text-align: center;
  padding: 25px;
}

.philosophy-item i {
  font-size: 3rem;
  color: var(--blue-700);
  margin-bottom: 20px;
}

.philosophy-item h3 {
  color: var(--blue-700);
  margin-bottom: 15px;
  font-size: 22px;
}

/* GOALS SECTION */
.goals-container {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.goal-card {
  background: white;
  padding: 25px;
  border-radius: var(--radius);
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
  text-align: center;
  border: 2px solid transparent;
  transition: all 0.3s ease;
}

.goal-card:hover {
  border-color: var(--blue-700);
  transform: translateY(-5px);
}

.goal-card i {
  font-size: 2.5rem;
  color: var(--blue-700);
  margin-bottom: 15px;
}

.goal-card h3 {
  color: var(--blue-700);
  margin-bottom: 10px;
  font-size: 18px;
}

/* FOOTER */
footer {
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
  text-align: center;
  padding: 30px 20px;
  font-size: 14px;
  margin-top: 60px;
  border-radius: var(--radius) var(--radius) 0 0;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .values-container,
  .mission-grid,
  .philosophy-content,
  .goals-container {
    flex-direction: column;
    align-items: center;
  }
  
  .brand h1 {
    font-size: 20px;
  }
  
  .hero-section h1 {
    font-size: 2rem;
  }
  
  .section {
    padding: 25px 20px;
  }
  
  .box,
  .mission-card,
  .philosophy-item,
  .goal-card {
    width: 100%;
    max-width: 400px;
  }
}

.quote {
  font-style: italic;
  text-align: center;
  padding: 20px;
  background: linear-gradient(135deg, #f8f9ff, #eef4ff);
  border-radius: var(--radius);
  margin: 30px 0;
  border-left: 5px solid var(--gold);
}

.quote p {
  font-size: 1.1rem;
  color: var(--blue-700);
  margin-bottom: 10px;
}

.quote .author {
  color: var(--muted);
  font-weight: 600;
}
</style>
</head>

<body>

<!-- HEADER -->
<header>
  <div class="brand">
    <img src="logo.jpg" alt="MEJECRES Logo">
    <h1>MEJECRES SCHOOL</h1>
  </div>
  <button class="hamburger" id="hamburger">☰</button>
  <nav id="NavMenu">
    <a href="index.php"><i class="fas fa-home"></i> Home</a>
      <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
   <a href="vision.php"><i class="fas fa-eye"></i> Vision & Values</a>
        <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
  </nav>
</header>

<!-- MAIN CONTENT -->
<main class="container">
  <!-- HERO SECTION -->
  <div class="hero-section">
    <h1>Our Vision & Values</h1>
    <div class="subtitle">Shaping Future Leaders Through Excellence and Integrity</div>
  </div>

  <!-- VISION SECTION -->
  <div class="section">
    <h2>Our Vision</h2>
    <p>To become a premier educational institution that nurtures holistic development, fosters innovation, and shapes learners into responsible global citizens who lead with integrity, creativity, and excellence.</p>
    
    <div class="quote">
      <p>"Education is the most powerful weapon which you can use to change the world."</p>
      <div class="author">- Nelson Mandela</div>
    </div>
  </div>

  <!-- MISSION SECTION -->
  <div class="section">
    <h2>Our Mission</h2>
    <p>MEJECRES SCHOOL is committed to providing a transformative educational experience that:</p>
    
    <div class="mission-grid">
      <div class="mission-card">
        <h3>Academic Excellence</h3>
        <ul>
          <li>Deliver quality education through modern teaching methodologies</li>
          <li>Foster critical thinking and problem-solving skills</li>
          <li>Prepare students for higher education and professional success</li>
        </ul>
      </div>
      
      <div class="mission-card">
        <h3>Character Development</h3>
        <ul>
          <li>Instill strong moral values and ethical principles</li>
          <li>Promote discipline, respect, and responsibility</li>
          <li>Develop leadership qualities and social consciousness</li>
        </ul>
      </div>
      
      <div class="mission-card">
        <h3>Holistic Growth</h3>
        <ul>
          <li>Nurture physical, emotional, and spiritual well-being</li>
          <li>Encourage participation in sports and cultural activities</li>
          <li>Develop creative expression and artistic talents</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- CORE VALUES -->
  <div class="section">
    <h2>Our Core Values</h2>
    <p>These fundamental principles guide everything we do at MEJECRES SCHOOL:</p>
    
    <div class="values-container">
      <div class="box">
        <i class="fas fa-shield-alt"></i>
        <h3>Integrity</h3>
        <p>We uphold honesty, truthfulness, and strong moral principles in all our actions and decisions.</p>
      </div>
      
      <div class="box">
        <i class="fas fa-graduation-cap"></i>
        <h3>Excellence</h3>
        <p>We strive for the highest standards in academics, character, and personal development.</p>
      </div>
      
      <div class="box">
        <i class="fas fa-users"></i>
        <h3>Respect</h3>
        <p>We value diversity, treat everyone with dignity, and foster an inclusive community.</p>
      </div>
      
      <div class="box">
        <i class="fas fa-lightbulb"></i>
        <h3>Innovation</h3>
        <p>We embrace creativity, modern technology, and forward-thinking approaches to education.</p>
      </div>
      
      <div class="box">
        <i class="fas fa-hand-holding-heart"></i>
        <h3>Compassion</h3>
        <p>We cultivate empathy, kindness, and social responsibility towards others.</p>
      </div>
      
      <div class="box">
        <i class="fas fa-fist-raised"></i>
        <h3>Perseverance</h3>
        <p>We develop resilience, determination, and the courage to overcome challenges.</p>
      </div>
    </div>
  </div>

  <!-- EDUCATIONAL PHILOSOPHY -->
  <div class="section">
    <h2>Educational Philosophy</h2>
    <p>At MEJECRES SCHOOL, we believe that every child is unique and has immense potential waiting to be unlocked.</p>
    
    <div class="philosophy-content">
      <div class="philosophy-item">
        <i class="fas fa-seedling"></i>
        <h3>Child-Centered Learning</h3>
        <p>We focus on individual learning styles and pace, ensuring each student receives personalized attention.</p>
      </div>
      
      <div class="philosophy-item">
        <i class="fas fa-hands-helping"></i>
        <h3>Learning by Doing</h3>
        <p>Practical experiences and hands-on activities form the core of our teaching methodology.</p>
      </div>
      
      <div class="philosophy-item">
        <i class="fas fa-globe-africa"></i>
        <h3>Global Perspective</h3>
        <p>We prepare students to thrive in an interconnected world with cultural awareness and global citizenship.</p>
      </div>
    </div>
  </div>

  <!-- STRATEGIC GOALS -->
  <div class="section">
    <h2>Strategic Goals</h2>
    <p>Our roadmap to achieving educational excellence and institutional growth:</p>
    
    <div class="goals-container">
      <div class="goal-card">
        <i class="fas fa-chart-line"></i>
        <h3>Academic Achievement</h3>
        <p>Maintain 95%+ pass rate in national examinations</p>
      </div>
      
      <div class="goal-card">
        <i class="fas fa-laptop-code"></i>
        <h3>Digital Transformation</h3>
        <p>Integrate technology across all learning environments</p>
      </div>
      
      <div class="goal-card">
        <i class="fas fa-user-graduate"></i>
        <h3>Teacher Development</h3>
        <p>Continuous professional growth for our educators</p>
      </div>
      
      <div class="goal-card">
        <i class="fas fa-heartbeat"></i>
        <h3>Student Well-being</h3>
        <p>Comprehensive support for mental and physical health</p>
      </div>
      
      <div class="goal-card">
        <i class="fas fa-handshake"></i>
        <h3>Community Engagement</h3>
        <p>Strong partnerships with parents and local community</p>
      </div>
      
      <div class="goal-card">
        <i class="fas fa-trophy"></i>
        <h3>Excellence in Sports</h3>
        <p>Develop champions in various sporting disciplines</p>
      </div>
    </div>
  </div>

  <!-- SCHOOL MOTTO -->
  <div class="section">
    <h2>Our Motto</h2>
    <div class="quote">
      <p>"Excellence In Education"</p>
      <div class="author">- MEJECRES SCHOOL Motto</div>
    </div>
    <p>This motto encapsulates our commitment to balancing academic achievement with moral development, ensuring our students become well-rounded individuals who contribute positively to society.</p>
  </div>
</main>

<!-- FOOTER -->
<footer>
  <p>&copy; 2025 MEJECRES SCHOOL | Excellence in Education Since 2023</p>
  <p>Designed by MUGISHA & TUMUSIFU</p>
</footer>

<!-- SCRIPT -->
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
</script>

</body>
</html>