// Define the backend API base URL dynamically
const API_BASE_URL = window.location.origin.includes('localhost') 
    ? 'http://localhost:5000' 
    : 'https://api.digitalnexifyk.com';

// Mobile Menu Toggle
const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
const navLinks = document.querySelector('.nav-links');

mobileMenuBtn.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    mobileMenuBtn.innerHTML = navLinks.classList.contains('active') ? 
        '<i class="fas fa-times"></i>' : '<i class="fas fa-bars"></i>';
});

// ...existing JavaScript code...
