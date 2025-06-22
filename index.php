<?php
// Include configuration file
require_once 'config/config.php';

// Redirect students to their dashboard if they're already logged in
if (isLoggedIn() && isStudent()) {
    redirect(BASE_URL . 'student/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BCCTAP - Bago City College Time Attendance Platform</title>
    <link href="assets/css/styles.css" rel="stylesheet">
    <link href="assets/css/colors.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
        }
        
        .gradient-text {
            background: linear-gradient(to right, #22c55e, #16a34a);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='40' height='40' viewBox='0 0 40 40'%3E%3Cg fill-rule='evenodd'%3E%3Cg fill='%2322c55e' fill-opacity='0.05'%3E%3Cpath d='M0 38.59l2.83-2.83 1.41 1.41L1.41 40H0v-1.41zM0 1.4l2.83 2.83 1.41-1.41L1.41 0H0v1.41zM38.59 40l-2.83-2.83 1.41-1.41L40 38.59V40h-1.41zM40 1.41l-2.83 2.83-1.41-1.41L38.59 0H40v1.41zM20 18.6l2.83-2.83 1.41 1.41L21.41 20l2.83 2.83-1.41 1.41L20 21.41l-2.83 2.83-1.41-1.41L18.59 20l-2.83-2.83 1.41-1.41L20 18.59z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        .feature-card {
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            z-index: -1;
            opacity: 0.6;
        }
        
        .pulse {
            animation: pulse-animation 2s infinite;
        }
        
        @keyframes pulse-animation {
            0% {
                box-shadow: 0 0 0 0px rgba(99, 102, 241, 0.2);
            }
            100% {
                box-shadow: 0 0 0 20px rgba(99, 102, 241, 0);
            }
        }
        
        .floating {
            animation: floating 3s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translate(0, 0px); }
            50% { transform: translate(0, 15px); }
            100% { transform: translate(0, 0px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <?php include 'includes/header.php'; ?>
        
        <!-- Hero Section -->
        <section class="relative py-20 md:py-28 hero-pattern overflow-hidden">
            <!-- Blob decorations -->
            <div class="blob bg-green-300 w-72 h-72 top-20 left-0"></div>
            <div class="blob bg-green-200 w-96 h-96 bottom-10 right-10"></div>
            
            <div class="container mx-auto px-4 relative z-10">
                <div class="flex flex-col md:flex-row items-center justify-between gap-12">
                    <div class="md:w-1/2 animate__animated animate__fadeInLeft">
                        <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full inline-block mb-4">BAGO CITY COLLEGE</span>
                        <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                            Modern <span class="gradient-text">Attendance</span> Management
                        </h1>
                        <p class="text-lg md:text-xl text-gray-600 mb-8 max-w-lg">
                            Simplifying attendance tracking with QR technology for a seamless campus experience.
                        </p>
                        <div class="flex flex-wrap gap-4">
                            <a href="student/login.php" class="px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition duration-300 flex items-center group">
                                <span>Student Portal</span>
                                <i class="fas fa-arrow-right ml-2 group-hover:ml-4 transition-all"></i>
                            </a>
                            <a href="staff_login.php" class="px-8 py-4 bg-white border border-gray-200 hover:border-green-300 text-gray-700 font-medium rounded-xl shadow hover:shadow-md transition duration-300 flex items-center">
                                <i class="fas fa-user-tie mr-2 text-green-600"></i>
                                <span>Staff Access</span>
                            </a>
                        </div>
                        
                        <div class="mt-10 flex items-center">
                            <div class="flex -space-x-2">
                                <div class="w-10 h-10 rounded-full bg-green-200 flex items-center justify-center text-green-600 font-semibold">S</div>
                                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 font-semibold">T</div>
                                <div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-800 font-semibold">A</div>
                            </div>
                            <div class="ml-4">
                                <p class="text-gray-600 text-sm">Trusted by <span class="font-semibold">500+</span> students and staff</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:w-1/2 animate__animated animate__fadeInRight">
                        <div class="relative">
                            <!-- Phone mockup -->
                            <div class="relative mx-auto w-full max-w-md floating">
                                <div class="relative bg-black rounded-[2.5rem] p-2 shadow-xl">
                                    <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-green-600 rounded-[2.5rem] opacity-20"></div>
                                    <!-- Notch -->
                                    <div class="absolute top-0 inset-x-0 h-6 bg-black rounded-t-[2.5rem] flex justify-center">
                                        <div class="w-16 h-1 rounded-full bg-gray-600 mt-1.5"></div>
                                    </div>
                                    <div class="rounded-[2.3rem] overflow-hidden h-[600px] bg-white">
                                        <!-- App Screen Content -->
                                        <div class="h-full flex flex-col">
                                            <!-- App header -->
                                            <div class="bg-green-700 text-white p-4">
                                                <div class="flex items-center justify-between mb-3">
                                                    <h3 class="font-semibold">BCCTAP Scanner</h3>
                                                    <div class="flex items-center space-x-2">
                                                        <i class="fas fa-wifi"></i>
                                                        <i class="fas fa-battery-three-quarters"></i>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-green-200">Scan QR code to record attendance</p>
                                            </div>
                                            
                                            <!-- Scanner area -->
                                            <div class="flex-1 bg-gray-100 p-4 flex flex-col justify-center items-center">
                                                <div class="w-64 h-64 bg-gray-900 rounded-lg relative flex items-center justify-center">
                                                    <div class="absolute inset-4 border-2 border-dashed border-green-300 rounded-md flex items-center justify-center">
                                                        <div class="pulse w-16 h-16 rounded-md border-2 border-green-400 flex items-center justify-center">
                                                            <i class="fas fa-qrcode text-3xl text-green-400"></i>
                                                        </div>
                                                    </div>
                                                    <!-- Scan line animation -->
                                                    <div class="absolute h-0.5 w-full bg-green-500 top-1/2 animate-ping opacity-70"></div>
                                                </div>
                                                <div class="text-center mt-6">
                                                    <div class="text-green-700 font-semibold mb-1">Position your camera</div>
                                                    <p class="text-xs text-gray-500">to scan the QR code and mark your attendance</p>
                                                    <button class="mt-4 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">
                                                        <i class="fas fa-camera mr-2"></i> Open Camera
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Decorative elements -->
                            <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-green-600 rounded-full opacity-20 animate-pulse"></div>
                            <div class="absolute top-10 -right-10 w-32 h-32 bg-green-600 rounded-full opacity-10"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Wave shape divider -->
            <div class="absolute bottom-0 left-0 right-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" fill="#ffffff">
                    <path fill-opacity="1" d="M0,32L60,42.7C120,53,240,75,360,74.7C480,75,600,53,720,58.7C840,64,960,96,1080,96C1200,96,1320,64,1380,48L1440,32L1440,100L1380,100C1320,100,1200,100,1080,100C960,100,840,100,720,100C600,100,480,100,360,100C240,100,120,100,60,100L0,100Z"></path>
                </svg>
            </div>
        </section>
        
        <!-- Features Section -->
        <section class="py-20 bg-white relative">
            <div class="container mx-auto px-4">
                <div class="text-center mb-16">
                    <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4">FEATURES</span>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Smart Attendance Solutions</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Designed specifically for Bago City College's unique needs</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                        <div class="bg-green-50 w-16 h-16 flex items-center justify-center rounded-xl mb-6 text-green-600">
                            <i class="fas fa-mobile-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Mobile QR Scanning</h3>
                        <p class="text-gray-600 mb-6">Students can quickly record their attendance by scanning event-specific QR codes with their smartphones.</p>
                        <a href="#" class="inline-flex items-center text-green-600 font-medium hover:text-green-700">
                            <span>Learn more</span>
                            <i class="fas fa-chevron-right ml-2 text-xs"></i>
                        </a>
                    </div>
                    
                    <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                        <div class="bg-green-50 w-16 h-16 flex items-center justify-center rounded-xl mb-6 text-green-600">
                            <i class="fas fa-chart-line text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Real-time Analytics</h3>
                        <p class="text-gray-600 mb-6">Comprehensive dashboards with real-time statistics on attendance rates, trends and student participation.</p>
                        <a href="#" class="inline-flex items-center text-green-600 font-medium hover:text-green-700">
                            <span>Learn more</span>
                            <i class="fas fa-chevron-right ml-2 text-xs"></i>
                        </a>
                    </div>
                    
                    <div class="feature-card bg-white p-8 rounded-2xl shadow-lg border border-gray-100">
                        <div class="bg-green-50 w-16 h-16 flex items-center justify-center rounded-xl mb-6 text-green-600">
                            <i class="fas fa-shield-alt text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Secure & Reliable</h3>
                        <p class="text-gray-600 mb-6">Built with security in mind, ensuring attendance data is protected and accurately recorded.</p>
                        <a href="#" class="inline-flex items-center text-green-600 font-medium hover:text-green-700">
                            <span>Learn more</span>
                            <i class="fas fa-chevron-right ml-2 text-xs"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Stats Section -->
                <div class="mt-20 grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                    <div class="p-6">
                        <div class="text-4xl font-bold text-green-600 mb-2">500+</div>
                        <p class="text-gray-600">Students</p>
                    </div>
                    <div class="p-6">
                        <div class="text-4xl font-bold text-green-600 mb-2">50+</div>
                        <p class="text-gray-600">Faculty</p>
                    </div>
                    <div class="p-6">
                        <div class="text-4xl font-bold text-green-600 mb-2">98%</div>
                        <p class="text-gray-600">Accuracy</p>
                    </div>
                    <div class="p-6">
                        <div class="text-4xl font-bold text-green-600 mb-2">24/7</div>
                        <p class="text-gray-600">Access</p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- How It Works -->
        <section class="py-20 bg-gray-50">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row items-center">
                    <div class="md:w-1/2 pr-0 md:pr-12 mb-10 md:mb-0">
                        <span class="inline-block px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium mb-4">HOW IT WORKS</span>
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Simple and Effective Attendance Process</h2>
                        
                        <div class="space-y-8">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-green-500 rounded-full w-10 h-10 flex items-center justify-center text-white font-semibold mr-4">1</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">Create QR Codes</h3>
                                    <p class="text-gray-600">Teachers generate unique QR codes for each class or event through their portal.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-green-500 rounded-full w-10 h-10 flex items-center justify-center text-white font-semibold mr-4">2</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">Display for Students</h3>
                                    <p class="text-gray-600">QR codes are displayed on screens or printed for students to scan.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-green-500 rounded-full w-10 h-10 flex items-center justify-center text-white font-semibold mr-4">3</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">Scan with Mobile App</h3>
                                    <p class="text-gray-600">Students use their phone camera to scan the QR code and mark attendance.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <div class="flex-shrink-0 bg-green-500 rounded-full w-10 h-10 flex items-center justify-center text-white font-semibold mr-4">4</div>
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800 mb-2">View Reports</h3>
                                    <p class="text-gray-600">Faculty can view attendance data and generate reports from the dashboard.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="md:w-1/2">
                        <div class="rounded-2xl overflow-hidden shadow-2xl">
                            <div class="relative">
                                <img src="https://images.unsplash.com/photo-1516321497487-e288fb19713f?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80" alt="Students in classroom" class="w-full h-auto">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent flex flex-col justify-end p-8">
                                    <h4 class="text-white text-2xl font-bold mb-2">Simplifying Campus Life</h4>
                                    <p class="text-white/80">BCCTAP makes attendance tracking effortless</p>
                                </div>
                            </div>
                            <div class="p-6 bg-white">
                                <div class="flex items-center space-x-6">
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600">60%</div>
                                        <p class="text-sm text-gray-500">Time Saved</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600">95%</div>
                                        <p class="text-sm text-gray-500">Student Approval</p>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-3xl font-bold text-green-600">99%</div>
                                        <p class="text-sm text-gray-500">Accuracy Rate</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- CTA Section -->
        <section class="py-20 bg-gradient-to-r from-green-500 to-green-700 text-white relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute -top-20 -left-20 w-64 h-64 bg-green-200 rounded-full opacity-10"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-green-900 rounded-full opacity-20"></div>
            
            <div class="container mx-auto px-4 relative z-10">
                <div class="max-w-3xl mx-auto text-center">
                    <h2 class="text-3xl md:text-5xl font-bold mb-6">Ready to Transform Your Attendance Tracking?</h2>
                    <p class="text-xl text-green-100 mb-10">Join Bago City College's modern attendance platform and make tracking attendance simple, accurate, and efficient.</p>
                    <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                        <a href="student/login.php" class="flex items-center justify-center gap-2 px-8 py-4 bg-white text-green-800 hover:bg-green-50 font-medium rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 group">
                            <svg class="w-6 h-6 text-green-800 group-hover:text-green-900 transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5.121 17.804A9 9 0 1112 21a9 9 0 01-6.879-3.196z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="font-semibold">Student Portal</span>
                        </a>
                        <a href="staff_login.php" class="flex items-center justify-center gap-2 px-8 py-4 bg-green-800 hover:bg-green-700 text-white font-medium rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 group">
                            <svg class="w-6 h-6 text-green-200 group-hover:text-white transition-colors" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7V6a2 2 0 012-2h8a2 2 0 012 2v1" /><rect width="16" height="10" x="4" y="7" rx="2" /><path stroke-linecap="round" stroke-linejoin="round" d="M8 17v1a2 2 0 002 2h4a2 2 0 002-2v-1" /></svg>
                            <span class="font-semibold">Staff Access</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>
        
        <?php include 'includes/footer.php'; ?>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }
            
            // Add animation effects on scroll
            const animateElements = document.querySelectorAll('.feature-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        // Add a slight delay for each card
                        setTimeout(() => {
                            entry.target.classList.add('animate__animated', 'animate__fadeInUp');
                        }, index * 150);
                    }
                });
            }, { threshold: 0.1 });
            
            animateElements.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>
</html>