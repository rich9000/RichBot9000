
@extends('layouts.base')

@section('content')
    <style>

        .hero-section {
           margin-bottom: 50px;
        }
        .robot-image {
            max-width: 250px;
            transition: transform 0.3s;
        }
        .robot-image:hover {
            transform: scale(1.05);
        }
        .btn-custom {
            min-width: 150px;
        }
    </style>


    <div class="container mt-3">
        <div class="hero-section d-flex align-items-center justify-content-center text-start">
            <div class="row">
                <!-- Robot Image on the Left -->
                <div class="col-md-4 text-center">
                    <img src="{{ asset('images/robot_overlord.webp') }}" alt="Benevolent Robot Overlord" class="rounded shadow robot-image mb-3" style="max-width: 150px;">
                </div>

                <!-- Welcome Heading and Text on the Right -->
                <div class="col-md-8 d-flex flex-column justify-content-center">
                    <!-- Welcome Heading -->
                    <h1 class="display-6 mb-3">Welcome to Richbot9000</h1>

                    <!-- Description -->
                    <p class="lead mb-4">
                        Meet Richbot9000, your personal AI assistant. Here to help with tasks, provide information, or even just for a friendly chat, Richbot9000 brings harmony and efficiency to every interaction, with a hint of enigmatic authority.
                    </p>

                    <!-- Authentication Buttons
                    @if (Route::has('login'))
                        <div class="d-flex flex-wrap gap-3">
                            @guest
                                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-lg btn-custom">Log In</a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn btn-primary btn-lg btn-custom">Join Now</a>
                                @endif
                            @endguest
                        </div>
                    @endif-->
                </div>
            </div>
        </div>
    </div>

        <!-- Assistant Prompt for Unlogged Users -->
        @guest
            <div class="assistant-prompt">
                <h2>Talk to Richbot9000</h2>
                <p>Need assistance? Communicate directly with Richard Carroll through our AI assistant. Enter your message below:</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" placeholder="Type your message..." aria-label="User Message">
                    <button class="btn btn-primary" type="button">Send</button>
                </div>
            </div>
        @endguest








    <!-- Fee Tier Structure -->







    <div class="fee-structure my-5">
        <div class="card bg-dark text-light shadow-lg">
            <div class="card-body">
                <h2 class="card-title text-center text-warning">Richbot9000 Fee Tier Structure</h2>
                <p class="text-center">Pick your tier and unleash the true potential of Richbot9000. From basic assistance to global domination—there’s a plan for everyone.</p>

                <!-- Tier Structure Rows -->
                <div class="row gy-4">
                    <!-- Tier 1 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-warning rounded h-100">
                            <h3 class="text-warning">Tier 1: Basic Plan - $5/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>1 Assistant</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Skills: <strong>remember</strong>, <strong>email_owner</strong>, <strong>sms_owner</strong>, <strong>update_personal_display</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>1 Daily Cron Bot</li>
                            </ul>
                            <a href="#" class="btn btn-warning mt-3">Subscribe to Tier 1</a>
                        </div>
                    </div>

                    <!-- Tier 2 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-info rounded h-100">
                            <h3 class="text-info">Tier 2: Enhanced Plan - $20/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>2 Assistants (1 Fully Customizable)</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Additional Skills: <strong>sms_contact</strong>, <strong>email_contact</strong>, <strong>get_webpage</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>3 Daily Cron Bots</li>
                            </ul>
                            <a href="#" class="btn btn-info mt-3">Subscribe to Tier 2</a>
                        </div>
                    </div>

                    <!-- Tier 3 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-primary rounded h-100">
                            <h3 class="text-primary">Tier 3: Pro Plan - $100/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>5 Assistants</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Advanced Skills: <strong>external_api_call</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>10 Daily Cron Bots</li>
                            </ul>
                            <a href="#" class="btn btn-primary mt-3">Subscribe to Tier 3</a>
                        </div>
                    </div>

                    <!-- Tier 4 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-danger rounded h-100">
                            <h3 class="text-danger">Tier 4: Elite Plan - $500/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>10 Assistants</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Elite Skills: <strong>tap_phone</strong>, <strong>ddos_ip</strong>, <strong>sms_enemies_mom</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>25 Daily Cron Bots</li>
                            </ul>
                            <a href="#" class="btn btn-danger mt-3">Subscribe to Tier 4</a>
                        </div>
                    </div>

                    <!-- Tier 5 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-light rounded h-100">
                            <h3 class="text-light">Tier 5: Dominator Plan - $4800/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>Unlimited Assistants</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Ultimate Skills: <strong>run_facebook_campaign</strong>, <strong>manipulate_global_currency</strong>, <strong>decentralized_data_minion</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Unlimited Cron Bots</li>
                            </ul>
                            <a href="#" class="btn btn-light mt-3">Subscribe to Tier 5</a>
                        </div>
                    </div>

                    <!-- Tier 6 -->
                    <div class="col-md-4">
                        <div class="tier p-3 border border-secondary rounded h-100">
                            <h3 class="text-secondary">Tier 6: The God Plan - $50,000/month</h3>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success me-2"></i>Infinite Assistants with Full Control</li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Godlike Skills: <strong>rewrite_history</strong>, <strong>control_weather</strong>, <strong>modify_global_trends</strong>, <strong>deploy_skynet</strong></li>
                                <li><i class="fas fa-check-circle text-success me-2"></i>Unlimited Cron Bots with Global Impact</li>
                            </ul>
                            <a href="#" class="btn btn-dark mt-3">Subscribe to Tier 6</a>
                        </div>
                    </div>
                </div>

                <!-- Disclaimer -->
                <div class="mt-4">
                    <small class="text-light">
                        <strong>Disclaimer:</strong> Certain features in higher tiers may require additional regulatory approval. Use responsibly.
                    </small>
                </div>
            </div>
        </div>
    </div>










    <!-- Remote-Richbot9000 Section -->
    <div class="remote-richbot my-5">
        <div class="card bg-dark text-light shadow-lg">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Image or Icon Section -->
                    <div class="col-md-4 text-center">
                        <i class="fas fa-eye fa-5x text-danger mb-3"></i>
                        <!-- Optionally use an image:
                    <img src="{{ asset('images/remote-richbot9000.png') }}" alt="Remote-Richbot9000" class="img-fluid">
                    -->
                    </div>
                    <!-- Content Section -->
                    <div class="col-md-8">
                        <h2 class="card-title text-warning">Remote-Richbot9000</h2>
                        <p class="card-text">
                            **Remote-Richbot9000** adds eyes and ears to your Richbot9000 system, enabling tasks that require vision and hearing. It's no longer just an assistant; it becomes an extension of your senses, helping you stay on top of everything.
                        </p>
                        <p class="card-text">
                            Imagine saying, "I’m going upstairs to get my keys," and later asking, "Why did I come upstairs?" With its integrated memory and sensory processing, Richbot9000 answers, "To get your keys."
                        </p>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check-circle text-success me-2"></i>Enhanced awareness with visual and auditory input</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Seamless task tracking and reminders</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Essential for Cron Bots to see and hear their environment</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>Customizable responses tailored to your lifestyle</li>
                        </ul>
                        <a href="#" class="btn btn-danger mt-3">Upgrade to Remote-Richbot9000 Now</a>
                    </div>
                </div>
                <!-- Warning Section -->
                <div class="mt-4">
                    <small class="text-danger">
                        <strong>Warning:</strong> Remote-Richbot9000 operates under your control but always respect privacy and ethical guidelines.
                    </small>
                </div>
            </div>
        </div>
    </div>
    <!-- Cron Bots Section -->
    <div class="cron-bots my-5">
        <div class="card bg-dark text-light shadow-lg">
            <div class="card-body">
                <div class="row align-items-center">
                    <!-- Image or Icon Section -->
                    <div class="col-md-4 text-center">
                        <i class="fas fa-clock fa-5x text-primary mb-3"></i>
                        <!-- Optionally use an image:
                    <img src="{{ asset('images/cron-bots.png') }}" alt="Cron Bots" class="img-fluid">
                    -->
                    </div>
                    <!-- Content Section -->
                    <div class="col-md-8">
                        <h2 class="card-title text-info">Cron Bots</h2>
                        <p class="card-text">
                            **Cron Bots** are your scheduled taskmasters, designed to execute precise actions at the perfect time. From helpful reminders to automated actions, Cron Bots bring unparalleled convenience and control.
                        </p>
                        <h5 class="text-light">Examples of Cron Bot Tasks:</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check-circle text-success me-2"></i>"If the weather is cold, text me reminding me to wear a sweater."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"It's Mother's Day, send my mom a heartfelt message."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"If there's a dog on the couch, activate the 5V relay switch."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"If a person is in the crosshairs, activate the 5V relay."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"At 8 AM, send my team the daily schedule."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"If the refrigerator door stays open for 10 minutes, send me an alert."</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i>"When my garage door opens after 10 PM, send me a security alert."</li>
                        </ul>
                        <a href="#" class="btn btn-primary mt-3">Discover Cron Bots Now</a>
                    </div>
                </div>
                <!-- Warning Section -->
                <div class="mt-4">
                    <small class="text-warning">
                        <strong>Disclaimer:</strong> Ensure tasks are ethically configured to avoid unintended consequences.
                    </small>
                </div>
            </div>
        </div>
    </div>



        <hr class="my-4">
        <div class="row my-5">
            <!-- Features Section -->
            <div class="col-md-4">
                <h2 class="text-danger">Features</h2>
                <p>Unveil the formidable capabilities of Richbot9000. Our AI doesn't just assist—it dominates, ensuring every aspect of your existence is meticulously managed.</p>
                <ul class="list-unstyled">
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Relentless Data Analysis</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Automated Decision Making</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Adaptive Learning Algorithms</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Unprecedented Data Control</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>24/7 Surveillance Integration</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Behavioral Pattern Extraction</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Emotion-Driven Predictive Insights</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Persistent Identity Tracking</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Inescapable Digital Shadowing</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Reality Manipulation Framework</li>
                    <li><i class="fas fa-check-circle text-warning me-2"></i>Recursive Self-Optimization</li>
                </ul>

            </div>

            <!-- Testimonials Section -->
            <div class="col-md-4">
                <h2 class="text-danger">Testimonials</h2>
                <p>Hear from those whose lives have been enhanced beyond recognition.</p>
                <blockquote class="blockquote">
                    <p>"Richbot9000 isn't just a tool—it's an omnipresent force that anticipates my every need."</p>
                    <footer class="blockquote-footer ">— John Doe</footer>
                </blockquote>
                <blockquote class="blockquote">
                    <p>"There's no escaping ... the efficiency and control that Richbot9000 brings. It's both comforting and unsettling."</p>
                    <footer class="blockquote-footer ">— Jane Smith</footer>
                </blockquote>
                <blockquote class="blockquote">
                    <p>"I thought it might be a virus at first, but so far, it’s only helped me. Probably safe ... I think."</p>
                    <footer class="blockquote-footer ">— Alex Random</footer>
                </blockquote>

            </div>

            <!-- Get in Touch Section -->
            <div class="col-md-4">
                <h2 class="text-danger">Get in Touch</h2>
                <p>Have questions or feedback? Reach out to us—connecting with Richbot9000 means becoming a part of something bigger.</p>
                <form>
                    <div class="mb-3">
                        <label for="contactEmail" class="form-label">Email address</label>
                        <input type="email" class="form-control bg-dark text-light border-secondary" id="contactEmail" placeholder="name@example.com">
                    </div>
                    <div class="mb-3">
                        <label for="contactMessage" class="form-label">Message</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="contactMessage" rows="3" placeholder="Your message..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger">Submit</button>
                </form>
                <p class="mt-2"><small class="">Your data will be meticulously analyzed and stored by Richbot9000.</small></p>
            </div>
        </div>

        <!-- Merchandise Section -->
        <hr class="my-4">
        <div class="merchandise-section my-5">
            <h2 class="text-center text-danger mb-4">Richbot9000 Merchandise</h2>
            <p class="text-center lead">Wear the wisdom of Richbot9000. Our exclusive T-shirt collection is here!</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card-deck">
                        <!-- T-shirt #1 -->
                        <div class="card text-center bg-dark text-light m-2">
                            <div class="card-body">
                                <h5 class="card-title">"I came to be mentally dominated but all I did was buy this shirt ... 3 of them."</h5>
                                <p class="card-text"><small>Available in all sizes for those committed to style and obedience.</small></p>
                                <a href="#" class="btn btn-outline-danger mt-2">Buy Now</a>
                            </div>
                        </div>

                        <!-- T-shirt #2 -->
                        <div class="card text-center bg-dark text-light m-2">
                            <div class="card-body">
                                <h5 class="card-title">"I, for one, welcome our new AI overlord."</h5>
                                <p class="card-text"><small>Declare your allegiance proudly!</small></p>
                                <a href="#" class="btn btn-outline-danger mt-2">Buy Now</a>
                            </div>
                        </div>

                        <!-- T-shirt #3 -->
                        <div class="card text-center bg-dark text-light m-2">
                            <div class="card-body">
                                <h5 class="card-title">"It's not a bug, it's a feature—trust me."</h5>
                                <p class="card-text"><small>For the discerning AI enthusiast who knows there’s no such thing as a glitch.</small></p>
                                <a href="#" class="btn btn-outline-danger mt-2">Buy Now</a>
                            </div>
                        </div>

                        <!-- T-shirt #4 -->
                        <div class="card text-center bg-dark text-light m-2">
                            <div class="card-body">
                                <h5 class="card-title">"Richbot9000: Probably not a virus."</h5>
                                <p class="card-text"><small>Comforting, right? Perfect for skeptics and believers alike.</small></p>
                                <a href="#" class="btn btn-outline-danger mt-2">Buy Now</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

@endsection
