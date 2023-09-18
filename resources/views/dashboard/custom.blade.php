@extends('dashboard.layouts.main') {{-- Replace 'app' with your actual layout name if needed --}}

@section('head')
    <!-- Additional head content specific to your birthday view, if any -->
    <style>
        /* Custom CSS for the birthday content */
        .birthday-container {
            background-color: #ffc0cb;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: bounce 3s infinite;
        }

        .birthday-heading {
            font-size: 36px;
            color: #ffc0cb;
            margin-bottom: 10px;
        }

        .birthday-message {
            font-size: 24px;
            color: #333;
            margin: 0;
        }

        .birthday-heart {
            color: red;
            font-size: 36px;
            margin-top: 20px;
            animation: heartBeat 0.5s infinite;
        }

        /* Animation Keyframes */
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        @keyframes heartBeat {
            0%, 100% {
                transform: scale(1);
            }
            10%, 30%, 50%, 70%, 90% {
                transform: scale(1.3);
            }
        }
        
        /* Hide the gift content by default */
        .gift-content {
            display: none;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        /* Cute button styles */
        #open-gift {
            background-color: #ff6f61;
            color: white;
            padding: 10px 20px;
            font-size: 20px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #open-gift:hover {
            background-color: #ff5349;
        }

        .from-me {
            position: absolute;
            bottom: 10px; /* Adjust the distance from the bottom as needed */
            right: 10px; /* Adjust the distance from the right as needed */
            font-size: 14px; /* Make it smaller */
            color: white; /* Adjust the color as needed */
        }

        /* Surprise popup styles */
        .surprise-popup {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 20px;
            background-color: #333; /* Dark background color */
            color: white; /* White text color */
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            z-index: 9999;
        }
    </style>
@endsection

@section('container')
    {{-- Your birthday content goes here --}}
    <div class="container birthday-container mt-5" style="display: flex; justify-content: center; align-items: center; min-height: 75vh;">
        <div class="gift-box" style="text-align: center;">
            <button id="open-gift">Open Gift</button>
            <div class="gift-content">
                <h1 class="birthday-heading">Happy Birthday, Stevani!</h1>
                <p class="birthday-message">Wishing you a day filled with love and joy.</p>
                <p class="birthday-message">May all your dreams and wishes come true.</p>
                <p class="birthday-heart" id="surprise-heart">&#10084;&#65039;</p>
                <p class="from-me">From: Alvin &#128420;</p> <!-- Updated position and size of "From: Me" message -->
            </div>
            <div class="surprise-popup" id="popup">
                <p>Surprise! 🎉</p>
                <p>Lop u &#10084;&#65039;</p>
            </div>
        </div>
    </div>


    <script>
        function openGift() {
            $('.gift-content').fadeIn(3000); // Show the birthday content
            const audio = new Audio('img/happy-birthday-music-box.mp3'); // Replace with your music file path
            audio.loop = true; // Loop the music
            audio.volume = 1.0; // Adjust the volume (0.0 to 1.0)
            audio.play(); // Start playing the music
            $('#open-gift').hide(); // Hide the "Open Gift" button
        }
        
        $(document).ready(function() {
            // Attach a click event listener to the "Open Gift" button
            $('#open-gift').click(function() {
                openGift();
            });

            // Attach a click event listener to the birthday heart for the surprise animation
            $('#surprise-heart').click(function() {
                $('#popup').fadeIn(); // Show the surprise popup

                // Automatically hide the popup after 3 seconds
                setTimeout(function() {
                    $('#popup').fadeOut(); // Hide the popup
                }, 1000);
            });
        });
    </script>
@endsection
