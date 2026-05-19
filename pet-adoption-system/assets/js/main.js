// Form validation and UI enhancements
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Confirmation for delete actions
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if(!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        });
    });
    
    // Search form validation
    const searchForm = document.querySelector('.search-form');
    if(searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if(searchInput && searchInput.value.trim() === '') {
                e.preventDefault();
            }
        });
    }
    
    // Filter form enhancements
    const filterForm = document.querySelector('form[action="pets.php"]');
    if(filterForm) {
        filterForm.addEventListener('submit', function() {
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'text-center mt-3';
            loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            document.querySelector('.col-md-9').appendChild(loadingDiv);
        });
    }
});

// Function to format dates
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Function to show notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}