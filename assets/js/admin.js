// Admin Panel JavaScript

// Initialize tooltips and other interactive elements
document.addEventListener('DOMContentLoaded', function() {
    // Add any initialization code here
    console.log('Admin panel loaded');
});

// Mobile menu toggle (for responsive design)
function toggleSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    sidebar.classList.toggle('active');
}

// Close sidebar when clicking outside (mobile)
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.admin-sidebar');
    const menuButton = document.querySelector('.menu-toggle');
    
    if (window.innerWidth <= 768) {
        if (sidebar && !sidebar.contains(event.target) && !menuButton?.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    }
});

