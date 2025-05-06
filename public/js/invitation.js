function openInvitation() {
    let cover = document.querySelector('.cover');
    let content = document.querySelector('.content');
    let music = document.getElementById('bg-music');

    // Play music when opening the invitation
    music.play().catch(error => console.log("Auto-play blocked:", error));

    cover.classList.add('hidden');

    setTimeout(() => {
        cover.style.display = 'none';
        content.style.display = 'block';

        setTimeout(() => {
            content.classList.add('visible');
        }, 300);
    }, 1000);
}

function getQueryParam(param) {
    let urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

// Music Toggle Button
document.addEventListener("DOMContentLoaded", function () {
    let guestName = getQueryParam("to"); // Get 'to' parameter from URL
    let guestElement = document.getElementById("guest-name");

    if (guestName) {
        guestElement.textContent = guestName;
    }

    let music = document.getElementById("bg-music");
    let musicBtn = document.getElementById("music-toggle");
    let musicIcon = musicBtn.querySelector("i");

    // Ensure button click is detected
    musicBtn.addEventListener("click", function () {
        if (music.paused) {
            music.play();
            musicIcon.classList.remove("fa-play");
            musicIcon.classList.add("fa-pause"); // Change to pause icon
        } else {
            music.pause();
            musicIcon.classList.remove("fa-pause");
            musicIcon.classList.add("fa-play"); // Change back to play icon
        }
    });

    // Handle floating menu visibility on scroll
    let floatingMenu = document.getElementById("floating-menu");

    window.addEventListener("scroll", function () {
        let scrollTop = window.scrollY || document.documentElement.scrollTop;
        floatingMenu.style.bottom = scrollTop > 100 ? "20px" : "-60px";
    });

    // Smooth Scroll to Sections
    window.scrollToSection = function (sectionId) {
        let section = document.getElementById(sectionId);
        if (section) {
            window.scrollTo({
                top: section.offsetTop - 50,
                behavior: "smooth"
            });
        }
    };

    const animatedElements = document.querySelectorAll(".scroll-reveal");

    // Step 1: Define all animation classes
    const animationClasses = [
        "reveal-fade-in",
        "reveal-fade-up",
        "reveal-fade-down",
        "reveal-fade-left",
        "reveal-fade-right",
        "reveal-zoom-in",
        "reveal-zoom-out",
        "reveal-flip-in",
        "reveal-bounce-in",
        "reveal-blur-in"
    ];

    // Step 2: Randomly assign an animation class to each element
    animatedElements.forEach(el => {
        const randomClass = animationClasses[Math.floor(Math.random() * animationClasses.length)];
        el.classList.add("reveal-animate", randomClass);
    });

    // Step 3: Reveal on scroll
    function revealOnScroll() {
        const windowHeight = window.innerHeight;

        animatedElements.forEach(el => {
            const top = el.getBoundingClientRect().top;
            const bottom = el.getBoundingClientRect().bottom;

            if (top < windowHeight * 0.9 && bottom > 50) {
                el.classList.add("visible");
            } else if (top > windowHeight) {
                el.classList.remove("visible");
            }
        });
    }

    window.addEventListener("scroll", revealOnScroll);
    revealOnScroll(); // initial trigger


    // Countdown Timer
    function updateCountdown() {
        const weddingDate = new Date("2025-06-01T00:00:00").getTime();
        const now = new Date().getTime();
        const timeLeft = weddingDate - now;

        if (timeLeft > 0) {
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById("days").textContent = days < 10 ? "0" + days : days;
            document.getElementById("hours").textContent = hours < 10 ? "0" + hours : hours;
            document.getElementById("minutes").textContent = minutes < 10 ? "0" + minutes : minutes;
            document.getElementById("seconds").textContent = seconds < 10 ? "0" + seconds : seconds;
        } else {
            document.getElementById("countdown").innerHTML = "<h2>The Wedding Day is Here! 🎉</h2>";
        }
    }

    setInterval(updateCountdown, 1000);
    updateCountdown();

    // Detect Mobile OS and Show Calendar Button
    detectMobileOSAndShowCalendarBtn();

    //wishes form
    const wishesForm = document.getElementById("wishes-form");
    const successMessage = document.getElementById("wish-success-message");
    const wishesList = document.getElementById("wishes-list");

    // Submit Wish
    wishesForm.addEventListener("submit", function (event) {
        event.preventDefault();

        let formData = new FormData(wishesForm);

        fetch(wishesForm.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(response => response.json())
            .then(data => {
                successMessage.classList.remove("hidden"); // Show success message
                successMessage.classList.add("show");
                wishesForm.reset(); // Clear the form

                // Hide message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add("hidden");
                    successMessage.classList.remove("show");
                }, 5000);

                // Refresh the wishes list
                loadWishes();
            })
            .catch(error => {
                console.error("Error submitting wish:", error);
            });
    });

    // Load Wishes
    function loadWishes() {
        fetch("/wishes-list")
            .then(response => response.json())
            .then(wishes => {
                let wishesList = document.getElementById("guest-wishes-list");
                wishesList.innerHTML = ""; // Clear existing wishes

                wishes.forEach((wish, index) => {
                    let wishItem = document.createElement("div");
                    wishItem.classList.add("guest-wish-card");

                    let wishHeader = document.createElement("p");
                    wishHeader.classList.add("guest-wish-name");
                    wishHeader.innerText = wish.name;

                    let wishMessage = document.createElement("p");
                    wishMessage.classList.add("guest-wish-message");
                    wishMessage.innerText = wish.message;

                    let wishTimestamp = document.createElement("p");
                    wishTimestamp.classList.add("guest-wish-timestamp");
                    wishTimestamp.innerHTML = `${formatDateTime(wish.created_at)}`;

                    wishItem.appendChild(wishHeader);
                    wishItem.appendChild(wishMessage);
                    wishItem.appendChild(wishTimestamp);

                    // Delay animation for each wish
                    setTimeout(() => {
                        wishItem.classList.add("appear");
                    }, index * 200);

                    wishesList.appendChild(wishItem);
                });
            })
            .catch(error => {
                console.error("Error loading wishes:", error);
            });
    }

    // Format DateTime Function
    function formatDateTime(datetime) {
        let date = new Date(datetime);
        return date.toLocaleString("en-US", {
            day: "numeric",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    // Load wishes on page load
    loadWishes();

    //RSVP
    const rsvpForm = document.getElementById("rsvp-form");

    rsvpForm.addEventListener("submit", function (event) {
        event.preventDefault(); // Prevent page reload

        // Collect form data
        let formData = new FormData(rsvpForm);

        // Send AJAX request
        fetch(rsvpForm.action, {
            method: "POST",
            body: formData,
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
            .then(response => response.json())
            .then(data => {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: "Thank you for your RSVP!",
                });
                rsvpForm.reset(); // Clear the form
            })
            .catch(error => {
                console.error("Error submitting RSVP:", error);
            });
    });

    // Initialize Swiper for the gallery
    const thumbSwiper = new Swiper(".thumb-swiper", {
        slidesPerView: 'auto', // Let your CSS take control of width
        spaceBetween: 10,
        watchSlidesProgress: true,
        freeMode: true,
    });


    const swiper = new Swiper(".gallery-swiper", {
        spaceBetween: 10,
        loop: true,
        autoplay: {
            delay: 0, // Slides every 3 seconds
            disableOnInteraction: false,
        },
        speed: 3000, // Slide speed in ms
        thumbs: {
            swiper: thumbSwiper,
        },
        breakpoints: {
            // when window width is >= 768px (desktop/tablet)
            768: {
                slidesPerView: 2,
            },
            // when window width is < 768px (mobile)
            0: {
                slidesPerView: 1,
            }
        }
    });
});

function openInvitation() {
    let cover = document.querySelector('.cover');
    let content = document.querySelector('.content');

    // Play music when opening the invitation
    let music = document.getElementById('bg-music');
    music.play().catch(error => console.log("Auto-play blocked:", error));

    //Hide cover
    cover.classList.add('hidden');

    setTimeout(() => {
        cover.style.display = 'none';

        // Animate left and right borders
        setTimeout(() => {
            const borders = document.querySelectorAll('.side-border');
            borders.forEach(border => {
                border.classList.add('show-border');
                border.classList.remove('hidden-border');
            });

            document.querySelectorAll('.screen-border').forEach(el => {
                el.classList.add('visible');
            });
        }, 1000);

        setTimeout(() => {
            //Initialize particle background
            tsParticles.load("leaf-bg", {
                particles: {
                    number: { value: 20 },
                    shape: {
                        type: "image",
                        image: [
                            { src: "/img/leaf1.png", width: 32, height: 32 },
                            { src: "/img/leaf2.png", width: 32, height: 32 }
                        ]
                    },
                    size: {
                        value: 24,
                        random: { enable: true, minimumValue: 16 }
                    },
                    opacity: {
                        value: 0.8,
                        random: { enable: true, minimumValue: 0.5 }
                    },
                    move: {
                        enable: true,
                        speed: 1,
                        direction: "bottom",
                        straight: false,
                        outModes: "out",
                        random: true
                    },
                    rotate: {
                        value: { min: 0, max: 180 },
                        animation: { enable: true, speed: 2 }
                    }
                },
                background: {
                    color: "transparent"
                },
                detectRetina: true
            }).then(container => {
                // Force pointer-events: none on the canvas
                const canvas = container.canvas.element;
                if (canvas) canvas.style.pointerEvents = "none";
            });
        }, 1200);

        content.style.display = 'block';

        setTimeout(() => {
            content.classList.add('visible');
        }, 3000);
    }, 1000);
}

function copyAccount(accountId) {
    var copyText = document.getElementById(accountId).innerText;

    navigator.clipboard.writeText(copyText).then(function () {
        // Show toast
        var toast = document.getElementById("copy-toast");
        toast.classList.add("show");

        // Hide after 2 seconds
        setTimeout(function () {
            toast.classList.remove("show");
        }, 2000);
    }).catch(function (error) {
        console.error('Copy failed', error);
    });
}

function detectMobileOSAndShowCalendarBtn() {
    const ua = navigator.userAgent || navigator.vendor || window.opera;
    const isIOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;

    const iosBtn = document.getElementById("btn-ios-calendar");
    const androidBtn = document.getElementById("btn-android-calendar");

    if (isIOS) {
        iosBtn.style.display = "inline-block";
    } else {
        androidBtn.style.display = "inline-block";
    }
}