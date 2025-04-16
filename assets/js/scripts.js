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

// Load users from the backend API
function loadUsers() {
    fetch('https://yourdomain.com/api/getUsers') // Replace with your Namecheap domain API endpoint
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to fetch users');
            }
            return response.json();
        })
        .then(users => {
            const usersTableBody = document.getElementById('usersTableBody');
            usersTableBody.innerHTML = ''; // Clear existing rows

            users.forEach(user => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${user.id}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>${user.name}</td>
                    <td>${new Date(user.createdAt).toLocaleString()}</td>
                `;
                usersTableBody.appendChild(row);
            });
        })
        .catch(error => {
            console.error('Error fetching users:', error);
            alert('Failed to load users. Please try again later.');
        });
}

// Add event listener to load users when the "Users" section is opened
document.querySelector('[data-section="users"]').addEventListener('click', () => {
    loadUsers();
});
