// IRCTC Website JavaScript Functions
// Extracted from index.php for better organization and maintainability

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap carousel with smooth transitions
    const carousel = document.querySelector('#heroCarousel');
    if (carousel) {
        new bootstrap.Carousel(carousel, {
            interval: 5000,
            wrap: true,
            touch: true
        });
    }

    // Auto-hide notifications after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Smooth scroll effect for navigation
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    // Intersection Observer for animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe elements for animation
    const animatedElements = document.querySelectorAll('.feature-card, .service-item');
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Header button functions
    window.showLogin = function() {
        showModal('Login', `
            <form class="modal-form">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" placeholder="Enter username">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" placeholder="Enter password">
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                <button type="submit" class="btn btn-primary w-100">Login</button>
                <div class="text-center mt-3">
                    <a href="#" class="text-decoration-none">Forgot Password?</a>
                </div>
            </form>
        `);
    };

    window.showRegister = function() {
        showModal('Register', `
            <form class="modal-form">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" placeholder="First name">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" placeholder="Last name">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" placeholder="Enter email">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mobile Number</label>
                    <input type="tel" class="form-control" placeholder="Enter mobile number">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" placeholder="Create password">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" placeholder="Confirm password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
        `);
    };

    window.showAgentLogin = function() {
        showModal('Agent Login', `
            <form class="modal-form">
                <div class="mb-3">
                    <label class="form-label">Agent ID</label>
                    <input type="text" class="form-control" placeholder="Enter agent ID">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" placeholder="Enter password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Agent Login</button>
            </form>
        `);
    };

    window.showContact = function() {
        showModal('Contact Us', `
            <div class="contact-info">
                <h5>Get in Touch</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fa fa-phone"></i> Customer Care</h6>
                        <p>139 (Railway Enquiry)<br>1323 (Reservation)</p>
                        
                        <h6><i class="fa fa-envelope"></i> Email</h6>
                        <p>care@irctc.co.in</p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fa fa-map-marker"></i> Address</h6>
                        <p>IRCTC Ltd.<br>11th Floor, Statesman House<br>New Delhi - 110001</p>
                        
                        <h6><i class="fa fa-clock"></i> Working Hours</h6>
                        <p>24x7 Customer Support</p>
                    </div>
                </div>
            </div>
        `);
    };

    window.showHelp = function() {
        showModal('Help & Support', `
            <div class="help-content">
                <h5>Frequently Asked Questions</h5>
                <div class="accordion" id="helpAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#help1">
                                How to book train tickets?
                            </button>
                        </h2>
                        <div id="help1" class="accordion-collapse collapse show" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                Click on 'Trains' in the navigation menu, enter your journey details, select train and book tickets.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#help2">
                                How to cancel tickets?
                            </button>
                        </h2>
                        <div id="help2" class="accordion-collapse collapse" data-bs-parent="#helpAccordion">
                            <div class="accordion-body">
                                Go to 'My Account' → 'Booked Tickets' → Select ticket → Click 'Cancel'.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    };

    // Navigation Functions
    window.goToHome = function() {
        document.querySelector('#home').scrollIntoView({ behavior: 'smooth' });
    };
    
    window.showTrains = function() {
        showModal('Train Booking', `
            <div class="train-booking">
                <h5>Book Train Tickets</h5>
                <form class="modal-form">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">From</label>
                            <input type="text" class="form-control" placeholder="Departure station">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">To</label>
                            <input type="text" class="form-control" placeholder="Destination station">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Departure Date</label>
                            <input type="date" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-control">
                                <option>All Classes</option>
                                <option>AC First Class (1A)</option>
                                <option>AC 2 Tier (2A)</option>
                                <option>AC 3 Tier (3A)</option>
                                <option>Sleeper (SL)</option>
                                <option>Second Sitting (2S)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Search Trains</button>
                </form>
            </div>
        `);
    };

    // Real-time clock function
    function updateRealTime() {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = now.toLocaleString('en-US', { month: 'short' });
        const year = now.getFullYear();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        
        const timeString = `${day}-${month}-${year} [${hours}:${minutes}:${seconds}]`;
        const timeDisplay = document.getElementById('realTimeDisplay');
        if (timeDisplay) {
            timeDisplay.textContent = timeString;
        }
    }
    
    // Update time immediately and then every second
    updateRealTime();
    setInterval(updateRealTime, 1000);

    // Modal Helper Function
    function showModal(title, content) {
        const modalHtml = `
            <div class="modal fade" id="dynamicModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">${title}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            ${content}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('dynamicModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add new modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('dynamicModal'));
        modal.show();
        
        // Remove modal from DOM when hidden
        document.getElementById('dynamicModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
});
