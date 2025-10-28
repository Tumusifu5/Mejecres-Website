<?php
// gallery.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Gallery - MEJECRES SCHOOL</title>
<link rel="icon" href="logo.jpg" type="image/x-icon" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
  --blue-600: #0b4bd8;
  --blue-700: #023eaa;
  --gold: #ffd166;
  --bg: #f4f7fb;
  --muted: #6b7280;
  --radius: 12px;
  --shadow: 0 12px 36px rgba(4, 12, 30, 0.06);
  --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.1);
}

* {
  box-sizing: border-box;
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
  border: 2px solid rgba(255,255,255,0.2);
}

.brand h1 {
  font-size: 24px;
  font-weight: 700;
  margin: 0;
  text-shadow: 1px 1px 3px rgba(0,0,0,0.2);
}

.hamburger {
  display: none;
  background: none;
  border: none;
  font-size: 30px;
  color: white;
  cursor: pointer;
}

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
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}

nav a:hover,
nav a.active {
  background: rgba(255,255,255,0.25);
  color: #ffd700;
  transform: translateY(-2px);
}

nav a::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 0;
  height: 3px;
  background: var(--gold);
  transition: var(--transition);
}

nav a.active::after,
nav a:hover::after {
  width: 100%;
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

/* MAIN GALLERY */
main {
  padding: 40px 20px;
  max-width: 1200px;
  margin: auto;
}

.page-title {
  text-align: center;
  color: var(--blue-700);
  margin-bottom: 10px;
  position: relative;
  font-size: 2.5rem;
}

.page-title::after {
  content: '';
  display: block;
  width: 80px;
  height: 4px;
  background: linear-gradient(90deg, var(--blue-700), var(--gold));
  margin: 10px auto;
  border-radius: 2px;
}

.page-subtitle {
  text-align: center;
  color: var(--muted);
  margin-bottom: 40px;
  font-size: 1.1rem;
  max-width: 700px;
  margin-left: auto;
  margin-right: auto;
}

.filter-container {
  display: flex;
  justify-content: center;
  flex-wrap: wrap;
  gap: 10px;
  margin-bottom: 30px;
}

.filter-btn {
  padding: 12px 20px;
  border: none;
  border-radius: 30px;
  cursor: pointer;
  background: white;
  color: var(--blue-700);
  font-weight: 600;
  transition: var(--transition);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
}

.filter-btn.active {
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
}

#gallery {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 25px;
  justify-content: center;
}

.img-box {
  position: relative;
  overflow: hidden;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  cursor: pointer;
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.8s ease, transform 0.8s ease;
  background: white;
}
.img-box.visible {
  opacity: 1;
  transform: translateY(0);
}

.img-box img {
  width: 100%;
  border-radius: var(--radius) var(--radius) 0 0;
  height: 220px;
  object-fit: cover;
  transition: var(--transition);
}

.img-box:hover img {
  transform: scale(1.08);
}

.img-content {
  padding: 15px;
  background: white;
}

.img-title {
  font-weight: 600;
  color: var(--blue-700);
  margin: 0 0 8px 0;
  font-size: 1.1rem;
}

.img-description {
  color: var(--muted);
  font-size: 0.9rem;
  margin: 0;
  line-height: 1.5;
}

.img-meta {
  display: flex;
  justify-content: space-between;
  margin-top: 12px;
  font-size: 0.8rem;
  color: #999;
}

.img-category {
  background: rgba(11, 75, 216, 0.1);
  color: var(--blue-700);
  padding: 3px 10px;
  border-radius: 20px;
  font-weight: 500;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.7);
  color: white;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: var(--transition);
  padding: 20px;
  text-align: center;
}

.img-box:hover .overlay {
  opacity: 1;
}

.overlay-title {
  font-size: 1.4rem;
  font-weight: 700;
  margin-bottom: 10px;
}

.overlay-description {
  font-size: 0.95rem;
  margin-bottom: 15px;
  max-width: 80%;
}

.view-btn {
  background: var(--gold);
  color: #333;
  border: none;
  padding: 8px 20px;
  border-radius: 30px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.view-btn:hover {
  background: white;
  transform: scale(1.05);
}

/* Lightbox Modal */
.modal {
  display: none;
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.9);
  z-index: 1000;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.modal.active {
  display: flex;
  opacity: 1;
}

.modal-content {
  max-width: 90%;
  max-height: 90%;
  border-radius: 10px;
  box-shadow: 0 10px 50px rgba(0,0,0,0.5);
}

.modal-info {
  background: white;
  padding: 20px;
  border-radius: 0 0 10px 10px;
  max-width: 700px;
  margin: 0 auto;
}

.modal-title {
  font-size: 1.5rem;
  color: var(--blue-700);
  margin: 0 0 10px 0;
}

.modal-description {
  color: var(--muted);
  margin: 0 0 15px 0;
  line-height: 1.6;
}

.modal-close {
  position: absolute;
  top: 20px;
  right: 30px;
  color: white;
  font-size: 40px;
  font-weight: bold;
  cursor: pointer;
  transition: var(--transition);
}

.modal-close:hover {
  color: var(--gold);
  transform: rotate(90deg);
}

.modal-nav {
  position: absolute;
  top: 50%;
  width: 100%;
  display: flex;
  justify-content: space-between;
  padding: 0 20px;
  transform: translateY(-50%);
}

.modal-prev, .modal-next {
  background: rgba(255,255,255,0.2);
  color: white;
  border: none;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  font-size: 24px;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-prev:hover, .modal-next:hover {
  background: rgba(255,255,255,0.3);
  transform: scale(1.1);
}

/* Load More Button */
.load-more-container {
  text-align: center;
  margin-top: 40px;
}

#loadMore {
  padding: 14px 35px;
  border: none;
  border-radius: 30px;
  cursor: pointer;
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
  font-weight: 600;
  font-size: 1rem;
  transition: var(--transition);
  box-shadow: 0 5px 15px rgba(11, 75, 216, 0.3);
  display: inline-flex;
  align-items: center;
  gap: 10px;
}

#loadMore:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 20px rgba(11, 75, 216, 0.4);
}

/* FOOTER */
footer {
  background: linear-gradient(90deg, var(--blue-700), var(--blue-600));
  color: white;
  text-align: center;
  padding: 25px;
  font-size: 14px;
  margin-top: 60px;
  border-radius: 10px 10px 0 0;
}

/* Animation for new items */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.fade-in-up {
  animation: fadeInUp 0.6s ease forwards;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }
  
  #gallery {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
  }
  
  .modal-nav {
    padding: 0 10px;
  }
  
  .modal-prev, .modal-next {
    width: 40px;
    height: 40px;
    font-size: 20px;
  }
  
  .modal-close {
    top: 10px;
    right: 15px;
    font-size: 30px;
  }
}

@media (max-width: 480px) {
  #gallery {
    grid-template-columns: 1fr;
  }
  
  .filter-container {
    flex-direction: column;
    align-items: center;
  }
  
  .filter-btn {
    width: 80%;
  }
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
    <a href="index.php">Home</a>
    <a href="about.php">About</a>
    <a href="gallery.php" class="active">Gallery</a>
    <a href="contact.php">Contact</a>
  </nav>
</header>

<main>
  <h2 class="page-title">Our School Gallery</h2>
  <p class="page-subtitle">Explore the vibrant moments, achievements, and memories that make MEJECRES SCHOOL a special place for learning and growth.</p>

  <div class="filter-container">
    <button class="filter-btn active" data-filter="all">
      <i class="fas fa-th"></i> All Events
    </button>
    <button class="filter-btn" data-filter="annual">
      <i class="fas fa-calendar-alt"></i> Annual Day
    </button>
    <button class="filter-btn" data-filter="science">
      <i class="fas fa-flask"></i> Science Fair
    </button>
    <button class="filter-btn" data-filter="sports">
      <i class="fas fa-running"></i> Sports Events
    </button>
  </div>

  <div id="gallery">
    <!-- Gallery Images -->
    <div class="img-box" data-category="annual">
      <img src="50.jpg" alt="Annual Day Celebration">
      <div class="img-content">
        <h3 class="img-title">Annual Day Celebration</h3>
        <p class="img-description">Our students showcasing their talents in music, dance, and drama during the annual cultural festival.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> March 15, 2024</span>
          <span class="img-category">Annual Day</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Annual Day Celebration</h3>
        <p class="overlay-description">A spectacular evening of performances celebrating our school's cultural diversity and talent.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="science">
      <img src="60.jpg" alt="Science Fair Exhibition">
      <div class="img-content">
        <h3 class="img-title">Science Fair Exhibition</h3>
        <p class="img-description">Innovative projects and experiments displayed by our young scientists and innovators.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> February 22, 2024</span>
          <span class="img-category">Science Fair</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Science Fair Exhibition</h3>
        <p class="overlay-description">Students demonstrating their scientific knowledge through creative projects and experiments.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="sports">
      <img src="01.jpg" alt="Sports Day Event">
      <div class="img-content">
        <h3 class="img-title">Sports Day Event</h3>
        <p class="img-description">Annual sports competition showcasing athletic talent and sportsmanship.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> January 30, 2024</span>
          <span class="img-category">Sports</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Sports Day Event</h3>
        <p class="overlay-description">A day filled with energy, competition, and celebration of physical fitness.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="sports">
      <img src="02.jpg" alt="Soccer Championship">
      <div class="img-content">
        <h3 class="img-title">Soccer Championship</h3>
        <p class="img-description">Inter-school soccer tournament where our team showcased exceptional skills.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> November 12, 2023</span>
          <span class="img-category">Sports</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Soccer Championship</h3>
        <p class="overlay-description">Our talented soccer team competing in the regional inter-school championship.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="science">
      <img src="03.jpg" alt="Robotics Project">
      <div class="img-content">
        <h3 class="img-title">Robotics Project</h3>
        <p class="img-description">Students presenting their innovative robotics project at the tech exhibition.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> October 5, 2023</span>
          <span class="img-category">Science Fair</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Robotics Project</h3>
        <p class="overlay-description">Our students demonstrating their programming and engineering skills.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="annual">
      <img src="04.jpg" alt="Cultural Dance">
      <div class="img-content">
        <h3 class="img-title">Cultural Dance</h3>
        <p class="img-description">Traditional dance performance celebrating our rich cultural heritage.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> March 15, 2024</span>
          <span class="img-category">Annual Day</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Cultural Dance</h3>
        <p class="overlay-description">Students performing traditional dances from different regions of our country.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="sports">
      <img src="05.jpg" alt="Track & Field Race">
      <div class="img-content">
        <h3 class="img-title">Track & Field Race</h3>
        <p class="img-description">Athletes competing in various track and field events during sports day.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> January 30, 2024</span>
          <span class="img-category">Sports</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Track & Field Race</h3>
        <p class="overlay-description">Students demonstrating speed, agility, and determination in athletic competitions.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="annual">
      <img src="06.jpg" alt="Teachers' Appreciation">
      <div class="img-content">
        <h3 class="img-title">Teachers' Appreciation</h3>
        <p class="img-description">Special ceremony honoring our dedicated teaching staff for their contributions.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> September 5, 2023</span>
          <span class="img-category">Annual Day</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Teachers' Appreciation</h3>
        <p class="overlay-description">Celebrating the hard work and dedication of our exceptional teaching staff.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
    
    <div class="img-box" data-category="science">
      <img src="07.jpg" alt="Physics Lab Day">
      <div class="img-content">
        <h3 class="img-title">Physics Lab Day</h3>
        <p class="img-description">Hands-on experiments and demonstrations in our well-equipped physics laboratory.</p>
        <div class="img-meta">
          <span><i class="far fa-calendar"></i> February 22, 2024</span>
          <span class="img-category">Science Fair</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">Physics Lab Day</h3>
        <p class="overlay-description">Students exploring fundamental physics concepts through practical experiments.</p>
        <button class="view-btn">View Details</button>
      </div>
    </div>
  </div>

  <div class="load-more-container">
    <button id="loadMore" class="filter-btn">
      <i class="fas fa-plus"></i> Load More Images
    </button>
  </div>
</main>

<!-- Lightbox Modal -->
<div class="modal" id="imageModal">
  <span class="modal-close" id="modalClose">&times;</span>
  <div class="modal-nav">
    <button class="modal-prev" id="modalPrev"><i class="fas fa-chevron-left"></i></button>
    <button class="modal-next" id="modalNext"><i class="fas fa-chevron-right"></i></button>
  </div>
  <div class="modal-content-container">
    <img class="modal-content" id="modalImage" src="" alt="">
    <div class="modal-info">
      <h3 class="modal-title" id="modalTitle"></h3>
      <p class="modal-description" id="modalDescription"></p>
      <div class="img-meta">
        <span id="modalDate"></span>
        <span class="img-category" id="modalCategory"></span>
      </div>
    </div>
  </div>
</div>

<footer>
  &copy; 2025 MEJECRES SCHOOL | Designed by MUGISHA
</footer>

<script>
// Responsive Nav
const hamburger = document.getElementById('hamburger');
const nav = document.getElementById('NavMenu');
hamburger.addEventListener('click', () => {
  nav.classList.toggle('show');
  hamburger.textContent = nav.classList.contains('show') ? '✖' : '☰';
});

// Filter functionality
const buttons = document.querySelectorAll('.filter-btn');
const boxes = document.querySelectorAll('.img-box');

buttons.forEach(btn => {
  btn.addEventListener('click', () => {
    // Update active button
    buttons.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    
    const filter = btn.dataset.filter;
    
    // Filter boxes
    boxes.forEach(box => {
      if (filter === 'all' || box.dataset.category === filter) {
        box.style.display = 'block';
        setTimeout(() => box.classList.add('visible'), 100);
      } else {
        box.classList.remove('visible');
        setTimeout(() => box.style.display = 'none', 400);
      }
    });
  });
});

// Fade In on Scroll
function fadeInOnScroll() {
  const boxes = document.querySelectorAll('.img-box');
  const windowBottom = window.innerHeight + window.scrollY;
  
  boxes.forEach(box => {
    const boxTop = box.offsetTop + box.offsetHeight / 4;
    if (windowBottom > boxTop) {
      box.classList.add('visible');
    }
  });
}

fadeInOnScroll();
window.addEventListener('scroll', fadeInOnScroll);

// Lightbox functionality
const modal = document.getElementById('imageModal');
const modalImage = document.getElementById('modalImage');
const modalTitle = document.getElementById('modalTitle');
const modalDescription = document.getElementById('modalDescription');
const modalDate = document.getElementById('modalDate');
const modalCategory = document.getElementById('modalCategory');
const modalClose = document.getElementById('modalClose');
const modalPrev = document.getElementById('modalPrev');
const modalNext = document.getElementById('modalNext');

let currentImageIndex = 0;
const imageBoxes = Array.from(document.querySelectorAll('.img-box'));

// Open modal with image details
function openModal(index) {
  const box = imageBoxes[index];
  const img = box.querySelector('img');
  const title = box.querySelector('.img-title').textContent;
  const description = box.querySelector('.img-description').textContent;
  const date = box.querySelector('.img-meta span:first-child').textContent;
  const category = box.querySelector('.img-category').textContent;
  
  modalImage.src = img.src;
  modalImage.alt = img.alt;
  modalTitle.textContent = title;
  modalDescription.textContent = description;
  modalDate.textContent = date;
  modalCategory.textContent = category;
  
  currentImageIndex = index;
  modal.classList.add('active');
  document.body.style.overflow = 'hidden';
}

// Close modal
modalClose.addEventListener('click', () => {
  modal.classList.remove('active');
  document.body.style.overflow = 'auto';
});

// Navigate modal images
modalPrev.addEventListener('click', () => {
  currentImageIndex = (currentImageIndex - 1 + imageBoxes.length) % imageBoxes.length;
  openModal(currentImageIndex);
});

modalNext.addEventListener('click', () => {
  currentImageIndex = (currentImageIndex + 1) % imageBoxes.length;
  openModal(currentImageIndex);
});

// Close modal when clicking outside the image
modal.addEventListener('click', (e) => {
  if (e.target === modal) {
    modal.classList.remove('active');
    document.body.style.overflow = 'auto';
  }
});

// Add click events to view buttons and images
document.querySelectorAll('.view-btn, .img-box img').forEach((element, index) => {
  element.addEventListener('click', (e) => {
    e.stopPropagation();
    const box = element.closest('.img-box');
    const boxIndex = imageBoxes.indexOf(box);
    openModal(boxIndex);
  });
});

// Keyboard navigation
document.addEventListener('keydown', (e) => {
  if (modal.classList.contains('active')) {
    if (e.key === 'Escape') {
      modal.classList.remove('active');
      document.body.style.overflow = 'auto';
    } else if (e.key === 'ArrowLeft') {
      modalPrev.click();
    } else if (e.key === 'ArrowRight') {
      modalNext.click();
    }
  }
});

// Load More functionality
document.getElementById('loadMore').addEventListener('click', () => {
  const gallery = document.getElementById('gallery');
  const newImages = [
    {
      src: '08.jpg',
      title: 'Graduation Day',
      description: 'Celebrating the achievements of our graduating class with a memorable ceremony.',
      date: '<i class="far fa-calendar"></i> June 10, 2023',
      category: 'Annual Day'
    },
    {
      src: '09.jpg',
      title: 'Science Expo',
      description: 'Annual science exhibition featuring innovative projects from all grade levels.',
      date: '<i class="far fa-calendar"></i> February 22, 2024',
      category: 'Science Fair'
    },
    {
      src: '10.jpg',
      title: 'Basketball Tournament',
      description: 'Intense basketball matches during the inter-class championship.',
      date: '<i class="far fa-calendar"></i> November 5, 2023',
      category: 'Sports'
    },
    {
      src: '19.jpg',
      title: 'Music Festival',
      description: 'Students showcasing their musical talents in various instruments and vocals.',
      date: '<i class="far fa-calendar"></i> March 15, 2024',
      category: 'Annual Day'
    },
    {
      src: '12.jpg',
      title: 'Coding Challenge',
      description: 'Programming competition to solve complex problems using computational thinking.',
      date: '<i class="far fa-calendar"></i> October 18, 2023',
      category: 'Science Fair'
    },
    {
      src: '13.jpg',
      title: 'Relay Race',
      description: 'Team spirit and coordination in action during the annual relay race competition.',
      date: '<i class="far fa-calendar"></i> January 30, 2024',
      category: 'Sports'
    }
  ];

  newImages.forEach((item, index) => {
    const categoryClass = item.category === 'Annual Day' ? 'annual' : 
                         item.category === 'Science Fair' ? 'science' : 'sports';
    
    const box = document.createElement('div');
    box.className = 'img-box fade-in-up';
    box.dataset.category = categoryClass;
    
    box.innerHTML = `
      <img src="${item.src}" alt="${item.title}">
      <div class="img-content">
        <h3 class="img-title">${item.title}</h3>
        <p class="img-description">${item.description}</p>
        <div class="img-meta">
          <span>${item.date}</span>
          <span class="img-category">${item.category}</span>
        </div>
      </div>
      <div class="overlay">
        <h3 class="overlay-title">${item.title}</h3>
        <p class="overlay-description">${item.description}</p>
        <button class="view-btn">View Details</button>
      </div>
    `;
    
    gallery.appendChild(box);
    
    // Add to imageBoxes array for modal navigation
    imageBoxes.push(box);
    
    // Add event listeners to new elements
    const newIndex = imageBoxes.length - 1;
    box.querySelector('.view-btn').addEventListener('click', () => openModal(newIndex));
    box.querySelector('img').addEventListener('click', () => openModal(newIndex));
    
    // Trigger fade in animation
    setTimeout(() => {
      box.classList.add('visible');
      box.classList.remove('fade-in-up');
    }, 100 * index);
  });
  
  // Hide load more button after loading
  document.getElementById('loadMore').style.display = 'none';
});
</script>
</body>
</html>