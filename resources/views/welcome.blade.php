@extends('layouts.base')

@section('content')
    <style>
        .hero-section {
            margin-bottom: 50px;
        }
        .feature-card {
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .feature-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-color: #4a90e2;
        }
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4a90e2;
        }
        .section-title {
            color: #2c3e50;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: #4a90e2;
        }
        .card-body {
            color: #2c3e50;
        }
        .card-body h3 {
            color: #4a90e2;
            font-weight: 600;
        }
        .card-body p {
            color: #666666;
        }
        .fun-text {
            font-family: 'Figtree', sans-serif;
            color: #4a90e2;
        }
        .glow-effect {
            color: #2c3e50;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #4a90e2;
            border-color: #4a90e2;
        }
        .btn-primary:hover {
            background-color: #357abd;
            border-color: #357abd;
        }
        .btn-outline-light {
            color: #4a90e2;
            border-color: #4a90e2;
        }
        .btn-outline-light:hover {
            background-color: #4a90e2;
            color: white;
        }
    </style>

    <div class="container mt-5">
        <!-- Hero Section -->
        <div class="hero-section text-center mb-5">
            <h1 class="display-4 mb-4">Welcome to Richbot9000</h1>
            
            <div class="d-flex justify-content-center gap-3">
                <a href="{{ config('app.url') }}/webapp" target="_blank" class="btn btn-primary btn-lg">Launch WebApp</a>
                <a href="{{ route('merchandise.index') }}" class="btn btn-outline-light btn-lg">Shop</a>
                <a href="https://youtube.com/@richbot9000" target="_blank" class="btn btn-outline-light btn-lg"><i class="fab fa-youtube me-2"></i>YouTube</a>
            </div>
        </div>

        <!-- Core Features Section -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title">Core Features</h2>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-robot feature-icon"></i>
                        <h3 class="h4 mb-3">AI Assistants</h3>
                        <p>Your friendly neighborhood AI assistants, ready to chat, help, and make your day easier. They're smart, they're fun, and they're here to stay!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-clock feature-icon"></i>
                        <h3 class="h4 mb-3">Cron Bots</h3>
                        <p>Meet your time-traveling taskmasters! These bots are always on time and ready to automate your daily routines with a touch of AI magic.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-eye feature-icon"></i>
                        <h3 class="h4 mb-3">Remote Richbots</h3>
                        <p>Your AI's eyes and ears in the real world! These bots can see, hear, and understand their environment, making them perfect for smart home automation.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-headset feature-icon"></i>
                        <h3 class="h4 mb-3">Call Assistant</h3>
                        <p>Your personal AI receptionist that never sleeps! Handle calls, schedule meetings, and manage your communications with a friendly AI touch.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communication Features -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title">Communication</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-phone feature-icon"></i>
                        <h3 class="h4 mb-3">Phone Integration</h3>
                        <p>Turn your phone into an AI-powered communication hub. Make and receive calls with style!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-comment-dots feature-icon"></i>
                        <h3 class="h4 mb-3">SMS & Messaging</h3>
                        <p>Send messages with a touch of AI personality. Your texts will never be boring again!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-project-diagram feature-icon"></i>
                        <h3 class="h4 mb-3">Conversation Paths</h3>
                        <p>Create amazing conversation flows that feel natural and engaging. Your AI will never get lost in translation!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Management -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title">Business Made Fun</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-alt feature-icon"></i>
                        <h3 class="h4 mb-3">Appointment Scheduling</h3>
                        <p>Let AI handle your calendar while you focus on the fun stuff. No more scheduling headaches!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-poll feature-icon"></i>
                        <h3 class="h4 mb-3">Surveys</h3>
                        <p>Create engaging surveys that people actually want to fill out. Make data collection fun!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-users feature-icon"></i>
                        <h3 class="h4 mb-3">Contacts</h3>
                        <p>Keep track of your connections with an AI-powered address book that's smarter than your average contact list!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Features -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title">Cool Tech Stuff</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-plug feature-icon"></i>
                        <h3 class="h4 mb-3">Integrations</h3>
                        <p>Connect with your favorite tools and watch the magic happen. It's like LEGO for your business!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-image feature-icon"></i>
                        <h3 class="h4 mb-3">Image Generation</h3>
                        <p>Turn your ideas into stunning visuals with AI-powered image creation. Your imagination is the only limit!</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card feature-card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-code feature-icon"></i>
                        <h3 class="h4 mb-3">Scripts & Tools</h3>
                        <p>Customize your AI experience with powerful tools and scripts. Make your AI truly yours!</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Call to Action -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card bg-white">
                    <div class="card-body py-5">
                        <h2 class="section-title text-center mb-4">Get in Touch</h2>
                        <div class="row">
                            <!-- Contact Form -->
                            <div class="col-md-6">
                                <form id="contactForm" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" required>
                                        <div class="invalid-feedback">
                                            Please provide your name.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" required>
                                        <div class="invalid-feedback">
                                            Please provide a valid email.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="subject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="subject" required>
                                        <div class="invalid-feedback">
                                            Please provide a subject.
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" rows="4" required></textarea>
                                        <div class="invalid-feedback">
                                            Please provide a message.
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                                    </div>
                                </form>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="col-md-6">
                                <div class="ps-md-4">
                                    <h3 class="h4 mb-4" style="color: #4a90e2;">Let's Connect</h3>
                                    <p class="mb-4">Have questions about our AI solutions? Want to explore how Richbot9000 can transform your business? We're here to help!</p>
                                    
                                    <div class="mb-4">
                                        <h4 class="h5 mb-3" style="color: #2c3e50;">Other Ways to Reach Us</h4>
                                        <ul class="list-unstyled text-muted">
                                            <li class="mb-2"><i class="fas fa-envelope me-2"></i> {{ 'support@' . config('app.domain') }}</li>
                                            <li><i class="fas fa-phone me-2"></i> 866-594-7659</li>
                                        </ul>
                                    </div>

                                    <div class="mt-4">
                                        <h4 class="h5 mb-3" style="color: #2c3e50;">Follow Us</h4>
                                        <div class="d-flex gap-3">
                                            <a href="#" class="text-muted"><i class="fab fa-twitter fa-lg"></i></a>
                                            <a href="#" class="text-muted"><i class="fab fa-linkedin fa-lg"></i></a>
                                            <a href="#" class="text-muted"><i class="fab fa-facebook fa-lg"></i></a>
                                            <a href="#" class="text-muted"><i class="fab fa-instagram fa-lg"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Form validation
        document.getElementById('contactForm').addEventListener('submit', function(event) {
            if (!this.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            this.classList.add('was-validated');
        });
    </script>
@endsection
