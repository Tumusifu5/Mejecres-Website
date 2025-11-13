<?php
// about.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>About - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

<style>
:root {
  --blue-600: #0b4bd8;
  --blue-700: #023eaa;
  --gold: #ffd166;
  --bg: #f4f7fb;
  --muted: #6b7280;
  --radius: 12px;
  --shadow: 0 12px 36px rgba(4, 12, 30, 0.06);
  --success: #28a745;
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
  box-shadow: 0 8px 28px rgba(2, 46, 102, 0.12);
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

nav a:hover,
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
main {
  padding: 40px 20px;
  max-width: 1200px;
  margin: auto;
}

/* HERO SECTION */
.hero {
  background: linear-gradient(135deg, var(--blue-700), var(--blue-600));
  color: white;
  border-radius: var(--radius);
  padding: 50px 40px;
  text-align: center;
  margin-bottom: 40px;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.05"><polygon points="1000,0 1000,100 0,100"/></svg>');
  background-size: cover;
}

.hero h1 {
  font-size: 3rem;
  margin: 0 0 15px 0;
  font-weight: 700;
  position: relative;
}

.hero p {
  font-size: 1.2rem;
  margin: 0 auto 25px;
  max-width: 800px;
  opacity: 0.9;
  line-height: 1.6;
  position: relative;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  background: rgba(255,255,255,0.2);
  padding: 12px 24px;
  border-radius: 50px;
  backdrop-filter: blur(10px);
  position: relative;
}

.hero-badge strong {
  font-size: 1.1rem;
}

/* MISSION SECTION */
.mission-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 40px;
  margin-bottom: 50px;
}

.mission-card {
  background: white;
  border-radius: var(--radius);
  padding: 40px;
  box-shadow: var(--shadow);
  text-align: center;
  transition: transform 0.3s ease;
}

.mission-card:hover {
  transform: translateY(-5px);
}

.mission-card i {
  font-size: 3rem;
  color: var(--blue-700);
  margin-bottom: 20px;
}

.mission-card h2 {
  color: var(--blue-700);
  margin-bottom: 15px;
  font-size: 1.8rem;
}

.mission-card p {
  color: var(--muted);
  line-height: 1.7;
  font-size: 1rem;
}

/* ABOUT GRID */
.about-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 30px;
  margin-bottom: 50px;
}

.card {
  background: white;
  border-radius: var(--radius);
  padding: 30px;
  box-shadow: var(--shadow);
}

.card h2 {
  color: var(--blue-700);
  margin-bottom: 20px;
  font-size: 1.8rem;
  border-bottom: 3px solid var(--gold);
  padding-bottom: 10px;
  display: inline-block;
}

.card p {
  color: var(--muted);
  line-height: 1.7;
  font-size: 1rem;
  margin-bottom: 20px;
}

/* FACILITIES */
.facilities {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-top: 25px;
}

.facility {
  background: linear-gradient(135deg, #ffffff, #f8fbff);
  border-radius: var(--radius);
  padding: 25px 20px;
  text-align: center;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}

.facility:hover {
  transform: translateY(-8px);
  box-shadow: 0 15px 35px rgba(0,0,0,0.15);
  border-color: var(--blue-700);
}

.facility i {
  font-size: 2.5rem;
  color: var(--blue-700);
  margin-bottom: 15px;
  display: block;
}

.facility h3 {
  color: var(--blue-700);
  margin: 0 0 10px 0;
  font-size: 1.1rem;
}

.facility p {
  margin: 0;
  color: var(--muted);
  font-size: 0.9rem;
  line-height: 1.5;
}

/* QUICK FACTS */
.quick-facts {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.fact {
  display: flex;
  gap: 15px;
  align-items: center;
  padding: 20px;
  border-radius: var(--radius);
  background: linear-gradient(135deg, #f8fbff, #ffffff);
  box-shadow: 0 5px 15px rgba(0,0,0,0.06);
  transition: transform 0.3s ease;
}

.fact:hover {
  transform: translateX(5px);
}

.fact .icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
  background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.fact-content {
  flex: 1;
}

.fact strong {
  color: var(--blue-700);
  font-size: 1.1rem;
  display: block;
  margin-bottom: 5px;
}

.fact small {
  color: var(--muted);
  font-size: 0.9rem;
}

/* ACADEMIC PROGRAMS */
.programs-section {
  background: white;
  border-radius: var(--radius);
  padding: 40px;
  box-shadow: var(--shadow);
  margin-bottom: 50px;
}

.programs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 25px;
  margin-top: 30px;
}

.program-card {
  background: linear-gradient(135deg, #f8fbff, #ffffff);
  padding: 25px;
  border-radius: var(--radius);
  border-left: 5px solid var(--blue-700);
  transition: all 0.3s ease;
}

.program-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(0,0,0,0.1);
}

.program-card h3 {
  color: var(--blue-700);
  margin: 0 0 15px 0;
  font-size: 1.3rem;
}

.program-card ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.program-card li {
  padding: 8px 0;
  color: var(--muted);
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 10px;
}

.program-card li:last-child {
  border-bottom: none;
}

.program-card li i {
  color: var(--success);
  font-size: 0.8rem;
}

/* ACHIEVEMENTS */
.achievements {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-top: 30px;
}

.achievement {
  text-align: center;
  padding: 25px;
  background: linear-gradient(135deg, var(--blue-700), var(--blue-600));
  color: white;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
}

.achievement .number {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 10px;
  display: block;
}

.achievement .label {
  font-size: 0.9rem;
  opacity: 0.9;
}

/* FOOTER */
footer {
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
  text-align: center;
  padding: 30px 20px;
  margin-top: 60px;
  border-radius: var(--radius) var(--radius) 0 0;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .about-grid,
  .mission-section {
    grid-template-columns: 1fr;
  }
  
  .hero h1 {
    font-size: 2.2rem;
  }
  
  .hero p {
    font-size: 1rem;
  }
  
  .mission-card {
    padding: 30px 20px;
  }
  
  .programs-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  main {
    padding: 20px 15px;
  }
  
  .hero {
    padding: 30px 20px;
  }
  
  .card {
    padding: 20px;
  }
  
  .facilities {
    grid-template-columns: 1fr;
  }
  
  .achievements {
    grid-template-columns: repeat(2, 1fr);
  }
}

.quote {
  font-style: italic;
  text-align: center;
  padding: 25px;
  background: linear-gradient(135deg, #f8fbff, #ffffff);
  border-radius: var(--radius);
  margin: 30px 0;
  border-left: 5px solid var(--gold);
}

.quote p {
  font-size: 1.1rem;
  color: var(--blue-700);
  margin-bottom: 10px;
  font-weight: 500;
}

.quote .author {
  color: var(--muted);
  font-weight: 600;
}
</style>
</head>
<body>

<header>
  <div class="brand">
    <img src="logo.jpg" alt="MEJECRES Logo" />
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

<main>
  <!-- HERO SECTION -->
  <section class="hero">
    <h1>About MEJECRES SCHOOL</h1>
    <p>Excellence in Education Since 2023 - Nurturing Future Leaders Through Quality Education and Character Development</p>
    <div class="hero-badge">
      <i class="fas fa-graduation-cap"></i>
      <strong>Pioneers in Quality Education</strong>
    </div>
  </section>

  <!-- MISSION SECTION -->
  <section class="mission-section">
    <div class="mission-card">
      <i class="fas fa-bullseye"></i>
      <h2>Our Mission</h2>
      <p>To provide a transformative educational experience that empowers students with knowledge, skills, and values to excel academically and become responsible global citizens who contribute positively to society.</p>
    </div>
    <div class="mission-card">
      <i class="fas fa-eye"></i>
      <h2>Our Vision</h2>
      <p>To be a leading educational institution recognized for academic excellence, innovation, and character development, shaping learners who become catalysts for positive change in their communities and beyond.</p>
    </div>
  </section>

  <!-- INSPIRATIONAL QUOTE -->
  <div class="quote">
    <p>"Education is the passport to the future, for tomorrow belongs to those who prepare for it today."</p>
    <div class="author">- Malcolm X</div>
  </div>

  <!-- ABOUT GRID -->
  <section class="about-grid">
    <article class="card">
      <h2>Who We Are</h2>
      <p>MEJECRES SCHOOL stands as a beacon of educational excellence in Rwanda, committed to nurturing young minds through a holistic approach that balances academic rigor with character development. Founded in 2023, our institution has rapidly grown into a trusted educational partner for families seeking quality education.</p>
      
      <p>We believe that every child is unique and possesses immense potential waiting to be unlocked. Our dedicated team of educators works tirelessly to create a nurturing environment where students can discover their passions, develop critical thinking skills, and build strong moral foundations.</p>

      <h2>Our Educational Philosophy</h2>
      <p>At MEJECRES SCHOOL, we embrace a child-centered learning approach that focuses on:</p>
      
      <div class="facilities">
        <div class="facility">
          <i class="fas fa-brain"></i>
          <h3>Critical Thinking</h3>
          <p>Developing analytical and problem-solving skills</p>
        </div>
        <div class="facility">
          <i class="fas fa-heart"></i>
          <h3>Character Building</h3>
          <p>Instilling strong moral values and ethics</p>
        </div>
        <div class="facility">
          <i class="fas fa-users"></i>
          <h3>Collaboration</h3>
          <p>Fostering teamwork and social skills</p>
        </div>
        <div class="facility">
          <i class="fas fa-lightbulb"></i>
          <h3>Creativity</h3>
          <p>Encouraging innovation and original thinking</p>
        </div>
      </div>

      <!-- ACADEMIC PROGRAMS -->
      <h2 style="margin-top: 40px;">Academic Programs</h2>
      <div class="programs-grid">
        <div class="program-card">
          <h3>Advanced Education (N1-P6)</h3>
          <ul>
            <li><i class="fas fa-check"></i> Comprehensive literacy program</li>
            <li><i class="fas fa-check"></i> Mathematics and sciences</li>
            <li><i class="fas fa-check"></i> Social studies and languages</li>
            <li><i class="fas fa-check"></i> Creative arts integration</li>
          </ul>
        </div>
        <div class="program-card">
          <h3>Enrichment Programs</h3>
          <ul>
            <li><i class="fas fa-check"></i> Computer literacy and coding</li>
            <li><i class="fas fa-check"></i> Sports and physical education</li>
            <li><i class="fas fa-check"></i> Music and performing arts</li>
            <li><i class="fas fa-check"></i> Environmental education</li>
          </ul>
        </div>
      </div>
    </article>

    <!-- QUICK FACTS SIDEBAR -->
    <aside class="card quick-facts">
      <h2>School At a Glance</h2>
      <div class="fact">
        <div class="icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="fact-content">
          <strong>925+ Students</strong>
          <small>Thriving community of learners</small>
        </div>
      </div>
      <div class="fact">
        <div class="icon">
          <i class="fas fa-chalkboard-teacher"></i>
        </div>
        <div class="fact-content">
          <strong>25 Teachers</strong>
          <small>Qualified & experienced educators</small>
        </div>
      </div>
      <div class="fact">
        <div class="icon">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="fact-content">
          <strong>96% Success Rate</strong>
          <small>Academic achievement record</small>
        </div>
      </div>
      <div class="fact">
        <div class="icon">
          <i class="fas fa-laptop"></i>
        </div>
        <div class="fact-content">
          <strong>Digital Learning</strong>
          <small>Modern technology integration</small>
        </div>
      </div>
      <div class="fact">
        <div class="icon">
          <i class="fas fa-trophy"></i>
        </div>
        <div class="fact-content">
          <strong>Multiple Awards</strong>
          <small>Excellence in education</small>
        </div>
      </div>

      <!-- ACHIEVEMENTS -->
      <h2 style="margin-top: 30px;">Our Achievements</h2>
      <div class="achievements">
        <div class="achievement">
          <span class="number">98%</span>
          <span class="label">Parent Satisfaction</span>
        </div>
        <div class="achievement">
          <span class="number">15+</span>
          <span class="label">Extracurricular Clubs</span>
        </div>
        <div class="achievement">
          <span class="number">100%</span>
          <span class="label">Digital Systems</span>
        </div>
        <div class="achievement">
          <span class="number">5★</span>
          <span class="label">Safety Rating</span>
        </div>
      </div>
    </aside>
  </section>

  <!-- FACILITIES SECTION -->
  <section class="programs-section">
    <h2>Our State-of-the-Art Facilities</h2>
    <div class="facilities">
      <div class="facility">
        <i class="fas fa-book"></i>
        <h3>Modern Library</h3>
        <p>Well-stocked library with digital resources and reading spaces to foster love for reading</p>
      </div>
      <div class="facility">
        <i class="fas fa-desktop"></i>
        <h3>Computer Labs</h3>
        <p>Fully equipped computer laboratories with internet access and educational software</p>
      </div>
      <div class="facility">
        <i class="fas fa-flask"></i>
        <h3>Science Laboratory</h3>
        <p>Modern science lab for hands-on experiments and practical learning experiences</p>
      </div>
      <div class="facility">
        <i class="fas fa-basketball-ball"></i>
        <h3>Sports Complex</h3>
        <p>Comprehensive sports facilities including playground, basketball court, and field</p>
      </div>
      <div class="facility">
        <i class="fas fa-paint-brush"></i>
        <h3>Art Studio</h3>
        <p>Creative space for artistic expression with various art materials and equipment</p>
      </div>
      <div class="facility">
        <i class="fas fa-music"></i>
        <h3>Music Room</h3>
        <p>Dedicated space for music education with various instruments and practice areas</p>
      </div>
    </div>
  </section>
</main>

<footer>
  <p>&copy; 2025 MEJECRES SCHOOL | Excellence in Education Since 2023</p>
  <p style="margin-top: 10px; opacity: 0.9;">Designed by MUGISHA & TUMUSIFU</p>
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

// Add scroll animation for facts
const facts = document.querySelectorAll('.fact');
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateX(0)';
    }
  });
}, { threshold: 0.1 });

facts.forEach(fact => {
  fact.style.opacity = '0';
  fact.style.transform = 'translateX(-20px)';
  fact.style.transition = 'all 0.6s ease';
  observer.observe(fact);
});
</script>

</body>
</html>