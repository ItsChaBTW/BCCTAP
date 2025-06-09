/**
 * Main JavaScript file for BCCTAP
 */

// Close alert messages
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        const closeBtn = alert.querySelector('.close-alert');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                alert.style.display = 'none';
            });
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 500);
        }, 5000);
    });
});

// Toggle mobile navigation
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
        });
    }
    
    // Close alert messages
    const closeButtons = document.querySelectorAll('.close-alert');
    
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const alert = this.closest('.bg-green-50, .bg-red-50');
            if (alert) {
                alert.style.display = 'none';
            }
        });
    });
});

// Function to get current geolocation
function getCurrentLocation(callback) {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                
                if (callback && typeof callback === 'function') {
                    callback({
                        latitude: latitude,
                        longitude: longitude,
                        success: true
                    });
                }
            },
            function(error) {
                console.error('Geolocation error:', error);
                
                if (callback && typeof callback === 'function') {
                    callback({
                        success: false,
                        error: error.message
                    });
                }
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        console.error('Geolocation is not supported by this browser');
        
        if (callback && typeof callback === 'function') {
            callback({
                success: false,
                error: 'Geolocation is not supported by this browser'
            });
        }
    }
}

// Date and time formatting helper
function formatDateTime(date, format = 'datetime') {
    if (!date) date = new Date();
    if (!(date instanceof Date)) date = new Date(date);
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    
    if (format === 'date') {
        return `${year}-${month}-${day}`;
    } else if (format === 'time') {
        return `${hours}:${minutes}:${seconds}`;
    } else {
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }
} 