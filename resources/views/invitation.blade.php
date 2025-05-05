<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Wedding Invitation</title>
    
        <!-- Google Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Dancing+Script:wght@400..700&family=Merriweather:ital,opsz,wght@0,18..144,300..900;1,18..144,300..900&family=Oleo+Script:wght@400;700&family=Parisienne&family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Tangerine:wght@400;700&display=swap" rel="stylesheet">
        
        <link rel="stylesheet" href="{{ asset('css/invitation/main-style.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/opener.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/video-teaser.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/countdown.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/location-section.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/bank-section.css') }}">
        <link rel="stylesheet" href="{{ asset('css/invitation/guest-wishes.css') }}">

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

    <!-- Left Border Decoration -->
    <div class="side-border left-border hidden-border">
        <img src="/img/left-border.png" alt="Left Decoration">
    </div>

    <!-- Right Border Decoration -->
    <div class="side-border right-border hidden-border">
        <img src="/img/right-border.png" alt="Right Decoration">
    </div>

    <!-- Top Border -->
    <div class="screen-border border-top">
        <img src="/img/top-border.png" alt="Top Border">
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
            <p class="footer-subtext" style="font-family: Arial, Helvetica, sans-serif">#ALwayshaveVAN</p>
        </div>        
    </div>

    <!-- Main Content -->
    <div class="content">
        <!-- Wedding Opener Section -->
        <section id="wedding-opener" class="wedding-opener">
            <div class="film-scroll-wrapper film-scroll-top">
                <div class="film-scroll-track-reverse">
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                </div>
            </div>
            <div class="wedding-opener-content">
                <blockquote class="wedding-opener-quote">
                    “Two are better than one... If either of them falls down, one can help the other up.”
                </blockquote>
                <span class="wedding-opener-author">— Ecclesiastes 4:9–10</span>
            
                <div class="wedding-opener-divider"></div>
            
                <h2 class="wedding-opener-names">Alvin & Stevani</h2>
                <p class="wedding-opener-date">01 June 2025</p>
            </div>
        
            <!-- Film Scroll directly inside opener -->
            <div class="film-scroll-wrapper">
                <div class="film-scroll-track">
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                    <!-- Duplicate for infinite loop -->
                    <img src="/img/gallery/21.png" alt="Photo 1" class="film-scroll-img">
                    <img src="/img/gallery/22.png" alt="Photo 2" class="film-scroll-img">
                    <img src="/img/gallery/29.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/27.png" alt="Photo 3" class="film-scroll-img">
                    <img src="/img/gallery/25.png" alt="Photo 4" class="film-scroll-img">
                    <img src="/img/gallery/32.png" alt="Photo 5" class="film-scroll-img">
                    <img src="/img/gallery/23.png" alt="Photo 6" class="film-scroll-img">
                    <img src="/img/gallery/15.png" alt="Photo 7" class="film-scroll-img">
                    <img src="/img/gallery/28.png" alt="Photo 8" class="film-scroll-img">
                </div>
            </div>
        </section>
          
        <!-- Groom & Bride Section -->
        <section id="groom-bride" class="section">
            <h1 class="section-title scroll-reveal">The Groom<br/>&<br/>The Bride</h1>
            <div class="bride-groom-container">
                <!-- Groom -->
                <div class="profile">
                    <div class="profile-frame">
                        <img src="/img/photo-frame.png" alt="Frame" class="frame-overlay">
                        <img src="/img/groom2.png" alt="Groom" class="profile-img scroll-reveal">
                    </div>
                    <h3 class="profile-name scroll-reveal">Alvin</h3>
                    <div class="message-frame scroll-reveal">
                        <p class="profile-message">"A journey of love, trust, and forever."</p>
                    </div>
                </div>

                <!-- Bride -->
                <div class="profile">
                    <div class="profile-frame">
                        <img src="/img/photo-frame.png" alt="Frame" class="frame-overlay">
                        <img src="/img/bride2.png" alt="Bride" class="profile-img scroll-reveal">
                    </div>
                    <h3 class="profile-name scroll-reveal">Stevani</h3>
                    <div class="message-frame">
                        <p class="profile-message scroll-reveal">"Every step with you."</p>
                    </div>
                </div>
            </div>
        </section>    
        
        <section id="video-teaser" class="section scroll-reveal reveal-fade teaser-section">
            <h2 class="section-title scroll-reveal">Captured Moments</h2>
            <p class="teaser-subtitle scroll-reveal">A glimpse into the story we are about to begin...</p>
          
            <div class="video-wrapper scroll-reveal">
              <iframe 
                src="https://www.youtube.com/embed/cZ7E3m7F0qU?rel=0"
                frameborder="0" 
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                allowfullscreen
                title="Wedding Teaser"
              ></iframe>
            </div>
        </section>

        <!-- Countdown Timer Section -->
        <section id="countdown" class="section scroll-reveal reveal-fade section-autumn">
            <h1 class="countdown-title scroll-reveal">Countdown to<br/> Our Wedding</h1>
            <div class="countdown-container">
                <div class="countdown-box">
                    <div class="countdown-number scroll-reveal" id="days">00</div>
                    <div class="countdown-label scroll-reveal">Days</div>
                </div>
                <div class="countdown-box scroll-reveal">
                    <div class="countdown-number scroll-reveal" id="hours">00</div>
                    <div class="countdown-label scroll-reveal">Hours</div>
                </div>
                <div class="countdown-box scroll-reveal">
                    <div class="countdown-number scroll-reveal" id="minutes">00</div>
                    <div class="countdown-label scroll-reveal">Minutes</div>
                </div>
                <div class="countdown-box scroll-reveal">
                    <div class="countdown-number scroll-reveal" id="seconds">00</div>
                    <div class="countdown-label scroll-reveal">Seconds</div>
                </div>
            </div>
        </section>        
          
        <!-- Location Section -->
        <section id="location-new" class="section scroll-reveal reveal-up">
            <div class="location-title-wrapper">
                <h1 class="section-title-new scroll-reveal">Our Special Day</h1>
                <div class="section-divider-new scroll-reveal"></div>
                <p class="section-subtitle-new" scroll-reveal>Join us as we celebrate love and new beginnings</p>
            </div>
          
            <div class="location-cards-container">
              <!-- Holy Matrimony -->
              <div class="location-card">
                <div class="event-icon-wrapper">
                    <img src="/img/rings.png" alt="Rings Illustration" class="event-icon-new scroll-reveal">
                </div>
                <h2 class="event-title-new">Holy Matrimony</h2>
                <div class="event-detail-new">
                  <p><i class="fa-solid fa-calendar-days scroll-reveal"></i> Sunday, 01 June 2025</p>
                  <p><i class="fa-solid fa-clock scroll-reveal"></i> 11:00 AM</p>
                  <p><i class="fa-solid fa-location-dot scroll-reveal"></i> Gereja Katolik Santo Matias Rasul</p>
                </div>
                <a href="https://maps.app.goo.gl/2EYvaAZJF7ERRZGW9" target="_blank" class="map-button-new scroll-reveal"><i class="fa-solid fa-map-location-dot"></i>View Map</a>
              </div>
          
              <!-- Reception -->
              <div class="location-card reception-card">
                <div class="event-icon-wrapper">
                    <img src="/img/wedding-cake.png" alt="Wedding Cake Illustration" class="event-icon-new scroll-reveal">
                </div>
                <h2 class="event-title-new scroll-reveal">Reception</h2>
                <div class="event-detail-new">
                  <p><i class="fa-solid fa-calendar-days scroll-reveal"></i> Sunday, 01 June 2025</p>
                  <p><i class="fa-solid fa-clock scroll-reveal"></i> 18:00 PM</p>
                  <p><i class="fa-solid fa-location-dot scroll-reveal"></i> Santika Premiere Hotel Slipi</p>
                </div>
                <a href="https://maps.app.goo.gl/UTq8cdG5VgF9YQJy8" target="_blank" class="map-button-new scroll-reveal"><i class="fa-solid fa-map-location-dot"></i>View Map</a>
              </div>
            </div>
        </section>
          
        <!-- Bank Account Information Section -->
        <section id="gift-new" class="scroll-reveal reveal-left">
            <div class="gift-wrapper-new">
                <div class="gift-header-new">
                    <h2 class="gift-title-new scroll-reveal">Gift & Bank Transfer</h2>
                    <div class="gift-divider scroll-reveal"></div>
                    <p class="gift-description-new scroll-reveal">
                        Your presence at our wedding is the greatest gift.  
                        If you wish to bless us further, you can send your love through the details below.
                    </p>
                </div>
            
                <div class="gift-bank-container-new">
                <!-- Panin Bank -->
                <div class="gift-bank-card-new">
                    <div class="gift-bank-logo-wrapper">
                        <img src="/img/logo-panin.png" alt="Panin Bank Logo" class="gift-bank-logo-new scroll-reveal">
                    </div>
                    <div class="gift-bank-info-new">
                    <h3 class="gift-bank-name-new scroll-reveal">Bank Panin</h3>
                    <div class="gift-account-info-new">
                        <p class="gift-account-number-new scroll-reveal" id="panin-account">1842014911</p>
                        <button class="gift-copy-btn-new scroll-reveal" onclick="copyAccount('panin-account')">
                        <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="gift-account-holder-new scroll-reveal">a/n Alvin Christianto Hadi</p>
                    </div>
                </div>
            
                <!-- BCA Bank -->
                <div class="gift-bank-card-new">
                    <div class="gift-bank-logo-wrapper">
                    <img src="/img/logo-bca.png" alt="BCA Bank Logo" class="gift-bank-logo-new">
                    </div>
                    <div class="gift-bank-info-new">
                    <h3 class="gift-bank-name-new scroll-reveal">Bank BCA</h3>
                    <div class="gift-account-info-new">
                        <p class="gift-account-number-new scroll-reveal" id="bca-account">8870323639</p>
                        <button class="gift-copy-btn-new scroll-reveal" onclick="copyAccount('bca-account')">
                        <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="gift-account-holder-new scroll-reveal">a/n Stevani</p>
                    </div>
                </div>
                </div>
            </div>
        </section>
          
        <!-- Wishes Section -->
        <section id="guest-wishes" class="guest-wishes-section">
            <h2 class="guest-wishes-title scroll-reveal">Guest Wishes</h2>
          
            <!-- Guest Wishes Form -->
            <form id="wishes-form" class="guest-wishes-form" action="{{ route('wishes.submit') }}" method="POST">
                @csrf
                <input type="text" id="guest-name" name="name" placeholder="Your Name" required class="guest-wishes-input scroll-reveal">
                <textarea id="guest-message" name="message" placeholder="Your Wish" required class="guest-wishes-textarea scroll-reveal"></textarea>
                <button type="submit" class="guest-wishes-submit scroll-reveal">Send Wish</button>
            </form>

            <div id="wish-success-message" class="wish-success hidden">🎉 Thank you for your wish!</div>
          
            <!-- Submitted Wishes List -->
            <div class="guest-wishes-list scroll-reveal" id="guest-wishes-list">
              <!-- Example Wish Card -->
              <!--
              <div class="guest-wish-card">
                <p class="guest-wish-name">John Doe</p>
                <p class="guest-wish-message">Wishing you a lifetime of love and happiness!</p>
                <p class="guest-wish-timestamp">24 April 2025, 14:37</p>
              </div>
              -->
            </div>
        </section>

        <!-- RSVP Section -->
        <section id="rsvp" class="section scroll-reveal reveal-right section-autumn">
            <h2 class="autumn-title scroll-reveal">RSVP</h2>

            <p class="rsvp-description scroll-reveal">
                Kindly let us know if you’ll be attending by filling out the RSVP form below.
            </p>

            <form id="rsvp-form" class="rsvp-form scroll-reveal" action="{{ route('rsvp.submit') }}" method="POST">
                @csrf
                <input type="text" name="guest_name" placeholder="Your Name" required>
                <input type="number" name="guest_pax" placeholder="Number of Guests" min="1" required>
                <select name="attendance_status" required>
                    <option value="" disabled selected>Will you attend?</option>
                    <option value="yes">Yes, I will attend</option>
                    <option value="no">Sorry, I can't make it</option>
                </select>
                <button type="submit">Submit</button>
            </form>
        </section>

        <!-- Gallery Section -->
        <section id="gallery" class="section scroll-reveal reveal-up">
            <h2 class="section-title scroll-reveal">Photo Gallery</h2>

            <div class="swiper gallery-swiper">
                <div class="swiper-wrapper scroll-reveal">
                    <div class="swiper-slide"><img src="/img/gallery/1.png" alt="Gallery 1"></div>
                    <div class="swiper-slide"><img src="/img/gallery/2.png" alt="Gallery 2"></div>
                    <div class="swiper-slide"><img src="/img/gallery/3.png" alt="Gallery 3"></div>
                    <div class="swiper-slide"><img src="/img/gallery/4.png" alt="Gallery 4"></div>
                    <div class="swiper-slide"><img src="/img/gallery/5.png" alt="Gallery 5"></div>
                    <div class="swiper-slide"><img src="/img/gallery/6.png" alt="Gallery 6"></div>
                    <div class="swiper-slide"><img src="/img/gallery/7.png" alt="Gallery 7"></div>
                    <div class="swiper-slide"><img src="/img/gallery/8.png" alt="Gallery 8"></div>
                    <div class="swiper-slide"><img src="/img/gallery/9.png" alt="Gallery 9"></div>
                    <div class="swiper-slide"><img src="/img/gallery/10.png" alt="Gallery 10"></div>
                    <div class="swiper-slide"><img src="/img/gallery/11.png" alt="Gallery 11"></div>
                    <div class="swiper-slide"><img src="/img/gallery/12.png" alt="Gallery 12"></div>
                    <div class="swiper-slide"><img src="/img/gallery/13.png" alt="Gallery 13"></div>
                    <div class="swiper-slide"><img src="/img/gallery/14.png" alt="Gallery 14"></div>
                    <div class="swiper-slide"><img src="/img/gallery/15.png" alt="Gallery 15"></div>
                    <div class="swiper-slide"><img src="/img/gallery/16.png" alt="Gallery 16"></div>
                    <div class="swiper-slide"><img src="/img/gallery/17.png" alt="Gallery 17"></div>
                    <div class="swiper-slide"><img src="/img/gallery/18.png" alt="Gallery 18"></div>
                    <div class="swiper-slide"><img src="/img/gallery/19.png" alt="Gallery 19"></div>
                    <div class="swiper-slide"><img src="/img/gallery/20.png" alt="Gallery 20"></div>
                </div>
            </div>

            <div class="thumb-swiper">
                <div class="swiper-wrapper scroll-reveal">
                    <div class="swiper-slide"><img src="/img/gallery/1.png" alt="Gallery 1"></div>
                    <div class="swiper-slide"><img src="/img/gallery/2.png" alt="Gallery 2"></div>
                    <div class="swiper-slide"><img src="/img/gallery/3.png" alt="Gallery 3"></div>
                    <div class="swiper-slide"><img src="/img/gallery/4.png" alt="Gallery 4"></div>
                    <div class="swiper-slide"><img src="/img/gallery/5.png" alt="Gallery 5"></div>
                    <div class="swiper-slide"><img src="/img/gallery/6.png" alt="Gallery 6"></div>
                    <div class="swiper-slide"><img src="/img/gallery/7.png" alt="Gallery 7"></div>
                    <div class="swiper-slide"><img src="/img/gallery/8.png" alt="Gallery 8"></div>
                    <div class="swiper-slide"><img src="/img/gallery/9.png" alt="Gallery 9"></div>
                    <div class="swiper-slide"><img src="/img/gallery/10.png" alt="Gallery 10"></div>
                    <div class="swiper-slide"><img src="/img/gallery/11.png" alt="Gallery 11"></div>
                    <div class="swiper-slide"><img src="/img/gallery/12.png" alt="Gallery 12"></div>
                    <div class="swiper-slide"><img src="/img/gallery/13.png" alt="Gallery 13"></div>
                    <div class="swiper-slide"><img src="/img/gallery/14.png" alt="Gallery 14"></div>
                    <div class="swiper-slide"><img src="/img/gallery/15.png" alt="Gallery 15"></div>
                    <div class="swiper-slide"><img src="/img/gallery/16.png" alt="Gallery 16"></div>
                    <div class="swiper-slide"><img src="/img/gallery/17.png" alt="Gallery 17"></div>
                    <div class="swiper-slide"><img src="/img/gallery/18.png" alt="Gallery 18"></div>
                    <div class="swiper-slide"><img src="/img/gallery/19.png" alt="Gallery 19"></div>
                    <div class="swiper-slide"><img src="/img/gallery/20.png" alt="Gallery 20"></div>
                </div>
            </div>
        </section>

        <!-- Footer Section -->
        <footer class="footer">
            <p class="footer-text scroll-reveal">Thank you for celebrating with us! 💕</p>
            <p class="footer-subtext scroll-reveal">Your presence means the world to us.</p><br/>
            <p class="footer-subtext scroll-reveal">With love</p>
            <p class="footer-name scroll-reveal">Alvin & Stevani</p><br/>
            <p class="footer-subtext scroll-reveal" style="font-family: Arial, Helvetica, sans-serif">#ALwayshaveVAN</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/tsparticles@2/tsparticles.bundle.min.js"></script>
    <script src="{{ asset('js/invitation.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Background Music -->
    <audio id="bg-music" loop>
        <source src="/audio/wedding-music.mp3" type="audio/mpeg">
        Your browser does not support the audio tag.
    </audio>

    <!-- Music Control Button -->
    <button id="music-toggle" class="music-btn"><i class="fas fa-pause"></i></button>

    <!-- Floating Menu -->
    <div id="floating-menu" class="floating-menu">
        <button class="menu-btn" onclick="scrollToSection('groom-bride')">
            <i class="fas fa-user-group"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('location-new')">
            <i class="fas fa-map-location-dot"></i>
        </button>
        <button class="menu-btn" onclick="scrollToSection('gift-new')">
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
<div id="copy-toast" class="copy-toast">Account number copied!</div>
</body>
</html>
