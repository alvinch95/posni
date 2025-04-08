<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wedding Invitation</title>
    
        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Parisienne&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
    
        <link rel="stylesheet" href="{{ asset('css/invitation.css') }}">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

        <!-- Swiper CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
        <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    </head>    
<body>
<div class="mobile-container">
    {{-- Autumn leaf with particle js --}}
    <div id="leaf-bg"></div>

    <!-- Full-Screen Frame -->
    <div class="screen-frame">
        <img src="/img/section-frame.png" alt="Decorative Frame">
    </div>

    <!-- Cover Page -->
    <div class="cover">
        <div class="cover-card">
            <p class="guest-name">To: <span id="guest-name">Dear Guest</span></p>
            <h1 class="cover-title">Join Us To Celebrate Our Wedding</h1>
            <h2 class="cover-names">Alvin </h2>
            <h2 class="cover-names">&</h2>
            <h2 class="cover-names">Stevani</h2>
            <h3 class="cover-date">Sunday, 1 June 2025</h3>
            <button class="open-invitation" onclick="openInvitation()">Open Invitation</button>
            <br/>
            <p class="footer-subtext" style="font-family: Arial, Helvetica, sans-serif">#VINallywithVAN</p>
        </div>        
    </div>

    <!-- Main Content -->
    <div class="content">

        <!-- Bible Verse Section -->
        <section id="quote-section" class="section">
            <div class="quote-card">
                <blockquote class="quote-text animate-verse">
                    “Two are better than one... If either of them falls down, one can help the other up.”
                </blockquote>
                <span class="quote-author">— Ecclesiastes 4:9–10</span>
            </div>
        </section>

        <!-- Groom & Bride Section -->
        <section id="groom-bride" class="section">
            <h1 class="section-title">The Groom & The Bride</h1>
            <div class="bride-groom-container">
                <!-- Groom -->
                <div class="profile">
                    <div class="profile-frame">
                        <img src="/img/photo-frame.png" alt="Frame" class="frame-overlay">
                        <img src="/img/groom.png" alt="Groom" class="profile-img">
                    </div>
                    <h3 class="profile-name">Alvin</h3>
                    <div class="message-frame">
                        <p class="profile-message">"A journey of love, trust, and forever."</p>
                    </div>
                </div>

                <!-- Bride -->
                <div class="profile">
                    <div class="profile-frame">
                        <img src="/img/photo-frame.png" alt="Frame" class="frame-overlay">
                        <img src="/img/bride.png" alt="Bride" class="profile-img">
                    </div>
                    <h3 class="profile-name">Stevani</h3>
                    <div class="message-frame">
                        <p class="profile-message">"Every step with you is a step toward happiness."</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Countdown Timer Section -->
        <section id="countdown" class="section scroll-reveal reveal-fade">
            <h1 class="section-title">Countdown to<br/> the Wedding</h1>
            <div class="countdown-container">
                <div class="countdown-box">
                    <div class="countdown-number" id="days">00</div>
                    <div class="countdown-label">Days</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="hours">00</div>
                    <div class="countdown-label">Hours</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="minutes">00</div>
                    <div class="countdown-label">Minutes</div>
                </div>
                <div class="countdown-box">
                    <div class="countdown-number" id="seconds">00</div>
                    <div class="countdown-label">Seconds</div>
                </div>
            </div>
        </section>
          

        <!-- Location Section -->
        <section id="location" class="section scroll-reveal reveal-left">
            <h2 class="section-title">Wedding Venue</h2>
            <div class="event-wrapper">
                <img src="/img/wedding-gate.png" alt="Wedding Gate" class="wedding-gate">
                <div class="event-container">
                    <!-- Holy Matrimony -->
                   <div class="event-location">
                       <h3 class="event-title">Holy Matrimony</h3>
                       <p class="event-date">Sunday, 1 June 2025</p>
                       <p class="event-date">13:00 WIB</p>
                       <p class="event-venue">Gereja Katolik Santo Matias Rasul</p>
                       <a href="https://maps.app.goo.gl/2EYvaAZJF7ERRZGW9" target="_blank" class="map-link">
                           <i class="fas fa-map-marker-alt"></i> View Map
                       </a>
                   </div>
                   <!-- Reception -->
                   <div class="event-location">
                       <h3 class="event-title">Reception</h3>
                       <p class="event-date">Sunday, 1 June 2025</p>
                       <p class="event-date">18:00 WIB</p>
                       <p class="event-venue">Hotel Santika Premiere Slipi</p>
                       <a href="https://maps.app.goo.gl/UTq8cdG5VgF9YQJy8" target="_blank" class="map-link">
                           <i class="fas fa-map-marker-alt"></i> View Map
                       </a>
                   </div>
                </div>
            </div>

            
            
                
        </section>

        <!-- Bank Account Information Section -->
        <section id="bank-info" class="section scroll-reveal reveal-right">
            <h2 class="section-title">Gift & Bank Transfer</h2>
            
            <!-- Gift Message -->
            <p class="bank-description">
                Your presence at our wedding is the greatest gift of all. 
                However, if you would like to honor us with a token of love, 
                we would be truly grateful. You can send your blessings through the details below.
            </p>

            <div class="bank-card">
                <img src="/img/logo-panin.png" alt="Bank Logo" class="bank-logo">
                <div class="bank-details">
                    <h3 class="bank-name">Bank Panin</h3>
                    <div class="account-info">
                        <p class="account-number" id="panin-account">1842014911</p>
                        <button class="copy-btn" onclick="copyAccount('panin-account')">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="account-holder">Alvin Christianto Hadi</p>
                </div>
            </div>

            <div class="bank-card">
                <img src="/img/logo-bca.png" alt="Bank Logo" class="bank-logo">
                <div class="bank-details">
                    <h3 class="bank-name">Bank BCA</h3>
                    <div class="account-info">
                        <p class="account-number" id="bca-account">8870323639</p>
                        <button class="copy-btn" onclick="copyAccount('bca-account')">
                            <i class="fas fa-copy"></i>
                        </button>    
                    </div>
                    <p class="account-holder">Stevani</p>
                </div>
            </div>
        </section>

        <!-- Wishes Section -->
        <section id="wishes" class="section scroll-reveal reveal-up">
            <h2 class="section-title">Guest Wishes</h2>

            <!-- Wishes Form -->
            <form id="wishes-form" action="{{ route('wishes.submit') }}" method="POST">
                @csrf
                <input type="text" id="wish-name" name="name" placeholder="Your Name" required>
                <textarea id="wish-message" name="message" placeholder="Write your wish..." required></textarea>
                <button type="submit" class="wishes-button">Send Wish</button>
            </form>

            <!-- Success Message -->
            <div id="wish-success-message" class="wish-success hidden">🎉 Thank you for your wish!</div>

            <!-- Display Wishes -->
            <div id="wishes-list"></div>
        </section>


        <!-- RSVP Section -->
        <section id="rsvp" class="section scroll-reveal reveal-right">
            <h2 class="section-title">RSVP</h2>

            <p class="rsvp-description">
                Kindly let us know if you’ll be attending by filling out the RSVP form below.
            </p>

            <form id="rsvp-form" class="rsvp-form" action="{{ route('rsvp.submit') }}" method="POST">
                @csrf
                <input type="text" name="guest_name" placeholder="Your Name" required>
                <input type="number" name="guest_pax" placeholder="Number of Guests" min="1" required>
                <select name="attendance_status" required>
                    <option value="" disabled selected>Will you attend?</option>
                    <option value="yes">Yes, I will attend</option>
                    <option value="no">Sorry, I can't make it</option>
                </select>
                <button type="submit">Submit RSVP</button>
            </form>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="section scroll-reveal reveal-up">
            <h2 class="section-title">Photo Gallery</h2>

            <div class="swiper gallery-swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide"><img src="/img/gallery/1.jpg" alt="Gallery 1"></div>
                    <div class="swiper-slide"><img src="/img/gallery/2.jpg" alt="Gallery 2"></div>
                    <div class="swiper-slide"><img src="/img/gallery/3.jpg" alt="Gallery 3"></div>
                    <div class="swiper-slide"><img src="/img/gallery/4.jpg" alt="Gallery 4"></div>
                    <div class="swiper-slide"><img src="/img/gallery/5.jpg" alt="Gallery 5"></div>
                </div>
            </div>
        </section>

        <!-- Footer Section -->
        <footer class="footer">
            <p class="footer-text">Thank you for celebrating with us! 💕</p>
            <p class="footer-subtext">Your presence means the world to us.</p>
            <p class="footer-subtext">With love, Alvin & Stevani</p><br/>
            <p class="footer-subtext" style="font-family: Arial, Helvetica, sans-serif">#VINallywithVAN</p>
        </footer>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    <script src="{{ asset('js/invitation.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Background Music -->
    <audio id="bg-music" loop>
        <source src="/audio/wedding-music2.mp3" type="audio/mpeg">
        Your browser does not support the audio tag.
    </audio>

    <!-- Music Control Button -->
    <button id="music-toggle" class="music-btn"><i class="fas fa-pause"></i></button>

    <!-- Floating Menu -->
    <div id="floating-menu" class="floating-menu">
        <button class="menu-btn" onclick="scrollToSection('groom-bride')">
            <i class="fas fa-user-group"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('location')">
            <i class="fas fa-map-location-dot"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('bank-info')">
            <i class="fas fa-gift"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('rsvp')">
            <i class="fas fa-clipboard-check"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('gallery')">
            <i class="fas fa-camera-retro"></i>
        </button>
    </div>
</div>

</body>
</html>
