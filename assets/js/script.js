// Enhanced CRM JavaScript - Clean Version
document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize sidebar state
    initializeSidebar();
    
    // Global delete button handler
    initDeleteButtons();
    
    // Auto hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-danger')) {
            setTimeout(() => {
                if (alert && alert.parentElement) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }
            }, 5000);
        }
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Format currency inputs
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                this.value = value;
            }
        });
        
        // Remove formatting on submit
        const form = input.closest('form');
        if (form) {
            form.addEventListener('submit', function() {
                input.value = input.value.replace(/[^\d]/g, '');
            });
        }
    });
    
    // Loading button states
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(button => {
        button.addEventListener('click', function() {
            const form = this.form;
            if (form && form.checkValidity()) {
                this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
                this.disabled = true;
            }
        });
    });
    
    // Mobile menu handling
    if (window.innerWidth <= 768) {
        setupMobileMenu();
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            setupMobileMenu();
        } else {
            resetDesktopMenu();
        }
    });
    
    // Initialize table features
    initTableFeatures();
    
});

// ============= DELETE HANDLER =============
function initDeleteButtons() {
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const href = this.getAttribute('href');
            const itemName = this.getAttribute('data-name') || 'item ini';
            const customMessage = this.getAttribute('data-message');
            
            // Use custom message if provided, otherwise use default
            const confirmMessage = customMessage || `Apakah Anda yakin ingin menghapus ${itemName}?`;
            
            if (confirm(confirmMessage)) {
                // Show loading state
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                this.disabled = true;
                
                // Navigate to delete URL
                window.location.href = href;
            }
        });
    });
}

// ============= SIDEBAR FUNCTIONS =============
function initializeSidebar() {
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    if (sidebarCollapsed === 'true') {
        collapseSidebar();
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (!sidebar) return;
    
    if (window.innerWidth <= 768) {
        // Mobile toggle
        sidebar.classList.toggle('show');
        const overlay = document.getElementById('sidebarOverlay');
        if (overlay) {
            overlay.classList.toggle('show');
        }
    } else {
        // Desktop toggle
        sidebar.classList.toggle('collapsed');
        if (mainContent) {
            mainContent.classList.toggle('expanded');
        }
        
        // Save state to localStorage
        const isCollapsed = sidebar.classList.contains('collapsed');
        localStorage.setItem('sidebarCollapsed', isCollapsed);
    }
}

function collapseSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar) sidebar.classList.add('collapsed');
    if (mainContent) mainContent.classList.add('expanded');
}

function expandSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar) sidebar.classList.remove('collapsed');
    if (mainContent) mainContent.classList.remove('expanded');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar) sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
}

function setupMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    // Reset desktop classes
    if (sidebar) sidebar.classList.remove('collapsed');
    if (mainContent) mainContent.classList.remove('expanded');
    
    // Close mobile sidebar if open
    closeSidebar();
}

function resetDesktopMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    // Close mobile menu
    if (sidebar) sidebar.classList.remove('show');
    if (overlay) overlay.classList.remove('show');
    
    // Restore desktop state
    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
    if (sidebarCollapsed === 'true') {
        collapseSidebar();
    }
}

// Mobile menu button for responsive
function showMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.add('show');
        overlay.classList.add('show');
    }
}

// Hide mobile menu
function hideMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (sidebar && overlay) {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
    }
}

// ============= UTILITY FUNCTIONS =============
function showLoading(button) {
    if (button) {
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
        button.disabled = true;
    }
}

function hideLoading(button, originalText) {
    if (button) {
        button.innerHTML = originalText;
        button.disabled = false;
    }
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// ============= TABLE FEATURES =============
function initTableFeatures() {
    // Row hover effects
    const tableRows = document.querySelectorAll('.table tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = '#f8f9fa';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
        });
    });
}

// Search functionality for tables
function filterTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (input && table) {
        input.addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                row.style.display = found ? '' : 'none';
            }
        });
    }
}

// ============= NOTIFICATION SYSTEM =============
function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification && notification.parentElement) {
            notification.remove();
        }
    }, duration);
}

// ============= SMOOTH SCROLLING =============
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// ============= DEBUG FUNCTION =============
function debugSidebar() {
    console.log('Sidebar element:', document.getElementById('sidebar'));
    console.log('Overlay element:', document.getElementById('sidebarOverlay'));
    console.log('Screen width:', window.innerWidth);
}