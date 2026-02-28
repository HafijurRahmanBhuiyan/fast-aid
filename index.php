<?php
session_start();
require_once 'config/database.php';

if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            redirect('admin/dashboard.php');
            break;
        case 'volunteer':
            redirect('volunteer/dashboard.php');
            break;
        case 'patient':
            redirect('patient/dashboard.php');
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FastAid - Emergency Medical Assistance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heart-pulse me-2"></i>FastAid
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="#how-it-works">How It Works</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
                </ul>
                <div class="d-flex gap-2">
                    <a href="signin.php" class="btn btn-outline-light">Sign In</a>
                    <a href="signup.php" class="btn btn-light text-danger fw-bold">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6 hero-content">
                    <div class="emergency-badge mb-4">
                        <i class="fas fa-phone-alt me-2"></i>24/7 Emergency Service
                    </div>
                    <h1 class="display-3 fw-bold text-white mb-4">Emergency Medical Assistance <span class="text-warning">On Demand</span></h1>
                    <p class="lead text-white-50 mb-4">
                        Connect instantly with nearby certified medical volunteers during emergencies. 
                        Get immediate assistance when every second counts.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="signup.php?role=patient" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-hand-holding-medical me-2"></i>Get Help Now
                        </a>
                        <a href="signup.php?role=volunteer" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-md me-2"></i>Become a Volunteer
                        </a>
                    </div>
                    <div class="d-flex gap-4 mt-4">
                        <div class="hero-stat">
                            <h3 class="text-white fw-bold">500+</h3>
                            <p class="text-white-50 mb-0">Active Volunteers</p>
                        </div>
                        <div class="hero-stat">
                            <h3 class="text-white fw-bold">10K+</h3>
                            <p class="text-white-50 mb-0">Lives Saved</p>
                        </div>
                        <div class="hero-stat">
                            <h3 class="text-white fw-bold">&lt;5min</h3>
                            <p class="text-white-50 mb-0">Response Time</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 text-center position-relative">
                    <div class="hero-illustration">
                        <div class="pulse-circle"></div>
                        <i class="fas fa-ambulance hero-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="about-image position-relative">
                        <img src="https://images.unsplash.com/photo-1631815588090-d4bfec5b1ccb?w=600&h=500&fit=crop" alt="Emergency Medical" class="img-fluid rounded-4 shadow">
                        <div class="experience-badge">
                            <span class="fw-bold">10+</span>
                            <small>Years Experience</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-header mb-4">
                        <span class="badge bg-danger px-3 py-2 mb-3">About FastAid</span>
                        <h2 class="fw-bold">Connecting Patients with Medical Heroes</h2>
                    </div>
                    <p class="text-muted mb-4">
                        FastAid is a revolutionary emergency medical assistance platform that bridges the gap between patients in need and certified medical volunteers. In critical situations, every second matters.
                    </p>
                    <p class="text-muted mb-4">
                        Our platform enables anyone to request immediate medical assistance from nearby qualified volunteers, creating a network of rapid response that saves lives.
                    </p>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="feature-box">
                                <i class="fas fa-clock text-danger"></i>
                                <h6>Quick Response</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box">
                                <i class="fas fa-shield-alt text-danger"></i>
                                <h6>Verified Volunteers</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <h6>Location Based</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="feature-box">
                                <i class="fas fa-heart text-danger"></i>
                                <h6>Patient First</h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section id="how-it-works" class="how-it-works-section py-5 bg-light">
        <div class="container py-5">
            <div class="text-center mb-5">
                <span class="badge bg-danger px-3 py-2 mb-3">How It Works</span>
                <h2 class="fw-bold">Simple Steps to Get Help</h2>
                <p class="text-muted">Get emergency assistance in three easy steps</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">1</div>
                        <div class="step-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h4>Request Help</h4>
                        <p class="text-muted mb-0">Enter your location and describe your emergency. We'll instantly notify nearby volunteers.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">2</div>
                        <div class="step-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h4>Get Matched</h4>
                        <p class="text-muted mb-0">A certified volunteer near you accepts your request and heads to your location.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-card text-center p-4">
                        <div class="step-number">3</div>
                        <div class="step-icon">
                            <i class="fas fa-hand-holding-medical"></i>
                        </div>
                        <h4>Receive Care</h4>
                        <p class="text-muted mb-0">Get immediate medical assistance while waiting for emergency services to arrive.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section py-5">
        <div class="container py-5">
            <div class="text-center mb-5">
                <span class="badge bg-danger px-3 py-2 mb-3">Our Services</span>
                <h2 class="fw-bold">Comprehensive Emergency Support</h2>
                <p class="text-muted">Multiple ways we help during medical emergencies</p>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h4>First Aid Response</h4>
                        <p class="text-muted">Immediate first aid from trained volunteers while emergency services are en route.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-heartbeat"></i>
                        </div>
                        <h4>CPR Assistance</h4>
                        <p class="text-muted">Qualified volunteers can provide CPR and life-saving techniques in cardiac emergencies.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-ambulance"></i>
                        </div>
                        <h4>Emergency Coordination</h4>
                        <p class="text-muted">We coordinate with ambulance services to ensure seamless care transition.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-pills"></i>
                        </div>
                        <h4>Medication Guidance</h4>
                        <p class="text-muted">Volunteers can provide guidance on basic medications and first aid supplies.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <h4>Triage Support</h4>
                        <p class="text-muted">Initial assessment and triage to determine the severity of the emergency.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="service-card p-4">
                        <div class="service-icon">
                            <i class="fas fa-hands-helping"></i>
                        </div>
                        <h4>Community Volunteers</h4>
                        <p class="text-muted">Network of certified medical professionals ready to help in your area.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section py-5">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="text-white fw-bold mb-3">Ready to Make a Difference?</h2>
                    <p class="text-white-50 mb-0">Join our network of volunteers and help save lives in your community.</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="signup.php?role=volunteer" class="btn btn-light btn-lg px-4">
                        <i class="fas fa-user-plus me-2"></i>Become a Volunteer
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="footer-section py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h4 class="text-white mb-4">
                        <i class="fas fa-heart-pulse me-2"></i>FastAid
                    </h4>
                    <p class="text-white-50 mb-4">Revolutionizing emergency medical assistance by connecting patients with qualified volunteers in their time of need.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2">
                    <h6 class="text-white mb-4">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#how-it-works">How It Works</a></li>
                        <li><a href="#services">Services</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="text-white mb-4">For Users</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="signin.php">Sign In</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                        <li><a href="signup.php?role=patient">Request Help</a></li>
                        <li><a href="signup.php?role=volunteer">Become Volunteer</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h6 class="text-white mb-4">Contact Us</h6>
                    <ul class="list-unstyled contact-info">
                        <li><i class="fas fa-envelope me-2"></i>support@fastaid.com</li>
                        <li><i class="fas fa-phone me-2"></i>1-800-FAST-AID</li>
                        <li><i class="fas fa-map-marker me-2"></i>Emergency Response Center</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4 border-secondary">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-white-50 mb-0">&copy; 2026 FastAid. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white-50 me-3">Privacy Policy</a>
                    <a href="#" class="text-white-50">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
