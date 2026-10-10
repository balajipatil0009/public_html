// Admin Panel JavaScript

// Mobile menu toggle (if needed in future)
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Sidebar Toggle
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.createElement('button');
    toggleBtn.className = 'sidebar-toggle-btn';
    toggleBtn.innerHTML = '☰';
    toggleBtn.style.cssText = 'position: fixed; bottom: 20px; right: 20px; width: 50px; height: 50px; border-radius: 50%; background: #0ea5e9; color: white; border: none; font-size: 24px; cursor: pointer; z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: none;';
    
    if (window.innerWidth <= 768) {
        toggleBtn.style.display = 'block';
        document.body.appendChild(toggleBtn);
    }

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        toggleBtn.innerHTML = sidebar.classList.contains('open') ? '✕' : '☰';
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth <= 768) {
            toggleBtn.style.display = 'block';
            if (!document.body.contains(toggleBtn)) document.body.appendChild(toggleBtn);
        } else {
            toggleBtn.style.display = 'none';
            sidebar.classList.remove('open');
        }
    });

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Confirm delete actions
    const deleteLinks = document.querySelectorAll('a[href*="delete"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
});
