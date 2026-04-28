<?php

function sts_render_ticket_form() {

    $screenings = sts_get_screenings();

    // Build display + seats
    $screening_info = [];
    $remaining_seats = [];

    foreach ($screenings as $key => $data) {
    $screening_info[$key] = $data['name'] . "<br>Date: " . $data['date'] . "<br>Location: " . $data['location'];

    $seats = get_option("remaining_seats_$key");

    if ($seats === false) {
        $seats = 200;
    }

    $remaining_seats[$key] = (int) $seats;
}

    ob_start();
    ?>

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Nova&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .form-container {
            max-width: 600px;
            margin: 40px auto;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a0a 100%);
            border: 2px solid #8b0000;
            border-radius: 8px;
            padding: 40px 30px;
            font-family: 'Inter', sans-serif;
            color: #e0e0e0;
            box-shadow: 0 0 40px rgba(139, 0, 0, 0.3), inset 0 0 20px rgba(0, 0, 0, 0.8);
            position: relative;
            overflow: hidden;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="url(%23grit)" width="100" height="100"/><defs><pattern id="grit" x="0" y="0" width="4" height="4" patternUnits="userSpaceOnUse"><rect fill="black" width="4" height="4" opacity="0.03"/></pattern></defs></svg>');
            pointer-events: none;
        }

        .form-inner {
            position: relative;
            z-index: 1;
        }

        /* Progress Indicator */
        .progress-wrapper {
            margin-bottom: 40px;
        }

        .progress-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }

        .progress-step::before {
            content: '';
            position: absolute;
            top: 20px;
            left: -50%;
            width: 100%;
            height: 2px;
            background: #333;
            z-index: 0;
        }

        .progress-step:first-child::before {
            display: none;
        }

        .progress-step.active::before {
            background: linear-gradient(90deg, #8b0000, #ff0000);
        }

        .progress-step.completed::before {
            background: #8b0000;
        }

        .progress-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #2a2a2a;
            border: 2px solid #555;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            position: relative;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .progress-step.active .progress-circle {
            background: linear-gradient(135deg, #8b0000, #ff0000);
            border-color: #ff3333;
            box-shadow: 0 0 15px rgba(139, 0, 0, 0.6);
        }

        .progress-step.completed .progress-circle {
            background: #8b0000;
            border-color: #ff3333;
        }

        .progress-label {
            font-size: 12px;
            margin-top: 8px;
            color: #999;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .progress-step.active .progress-label {
            color: #ff3333;
        }

        /* Form Header */
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h1 {
            font-family: 'Bebas Nova', sans-serif;
            font-size: 32px;
            color: #ff3333;
            text-transform: uppercase;
            letter-spacing: 3px;
            margin-bottom: 5px;
            text-shadow: 0 2px 10px rgba(139, 0, 0, 0.5);
        }

        .form-header .step-title {
            font-size: 14px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Form Steps */
        .form-steps {
            position: relative;
            min-height: 300px;
        }

        .form-step {
            position: absolute;
            width: 100%;
            opacity: 0;
            transform: translateY(20px) translateX(20px);
            pointer-events: none;
            transition: all 0.4s ease;
        }

        .form-step.active {
            opacity: 1;
            transform: translateY(0) translateX(0);
            pointer-events: auto;
        }

        .form-step.prev {
            transform: translateY(20px) translateX(-20px);
        }

        /* Screening Card */
        .screening-card {
            background: rgba(30, 10, 10, 0.8);
            border: 1px solid #8b0000;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
            display: none;
        }

        .screening-card.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .screening-card h3 {
            color: #ff3333;
            font-family: 'Bebas Nova', sans-serif;
            font-size: 18px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .screening-detail {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 13px;
            color: #bbb;
            border-bottom: 1px solid rgba(139, 0, 0, 0.3);
        }

        .screening-detail:last-child {
            border-bottom: none;
        }

        .screening-detail strong {
            color: #e0e0e0;
        }

        /* Reserved Callout */
        .reserved-callout {
            background: rgba(139, 0, 0, 0.2);
            border-left: 4px solid #ff3333;
            padding: 15px;
            margin-top: 15px;
            display: none;
            border-radius: 4px;
        }

        .reserved-callout.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        .reserved-callout strong {
            color: #ff3333;
            display: block;
            margin-bottom: 8px;
            font-family: 'Bebas Nova', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .reserved-callout p {
            font-size: 13px;
            line-height: 1.6;
            color: #ddd;
        }

        /* Form Fields */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #bbb;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 14px;
            background: #1a1a1a;
            border: 2px solid #333;
            border-radius: 4px;
            color: #e0e0e0;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder,
        .form-group select::placeholder {
            color: #666;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            background: #252525;
            border-color: #8b0000;
            box-shadow: 0 0 10px rgba(139, 0, 0, 0.3), inset 0 0 5px rgba(139, 0, 0, 0.1);
        }

        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%238b0000' d='M1 1l5 5 5-5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 35px;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 4px;
            font-family: 'Bebas Nova', sans-serif;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #333;
            color: #bbb;
            border: 1px solid #555;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #444;
            border-color: #666;
        }

        .btn-secondary:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-primary {
            background: linear-gradient(135deg, #8b0000, #cc0000);
            color: #fff;
            border: 1px solid #ff3333;
            width: 100%;
            flex: none;
            box-shadow: 0 0 20px rgba(139, 0, 0, 0.4);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #a50000, #ff0000);
            box-shadow: 0 0 30px rgba(139, 0, 0, 0.6), inset 0 0 10px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Mobile Responsive */
        @media (max-width: 600px) {
            .form-container {
                margin: 20px 15px;
                padding: 25px 20px;
            }

            .form-header h1 {
                font-size: 24px;
                letter-spacing: 2px;
            }

            .progress-bar {
                margin-bottom: 15px;
            }

            .progress-circle {
                width: 35px;
                height: 35px;
                font-size: 14px;
            }

            .progress-label {
                font-size: 10px;
                margin-top: 6px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn-secondary {
                width: 100%;
            }

            .form-step {
                min-height: 250px;
            }
        }

        /* Hidden utility */
        .hidden {
            display: none !important;
        }
    </style>

    <div class="form-container">
        <div class="form-inner">
            <!-- Progress Indicator -->
            <div class="progress-wrapper">
                <div class="progress-bar">
                    <div class="progress-step active" data-step="1">
                        <div class="progress-circle">1</div>
                        <div class="progress-label">Screening</div>
                    </div>
                    <div class="progress-step" data-step="2">
                        <div class="progress-circle">2</div>
                        <div class="progress-label">Tickets</div>
                    </div>
                    <div class="progress-step" data-step="3">
                        <div class="progress-circle">3</div>
                        <div class="progress-label">Details</div>
                    </div>
                </div>
            </div>

            <!-- Form Header -->
            <div class="form-header">
                <h1>🎬 THFF Tickets</h1>
                <div class="step-title" id="currentStepTitle">Select Your Screening</div>
            </div>

            <!-- Form Steps -->
            <form method="post" id="ticketForm">
                <div class="form-steps">
                    <!-- Step 1: Screening Selection -->
                    <div class="form-step active" data-step="1">
                        <div class="form-group">
                            <label for="screening">Screening Selection</label>
                            <select name="screening" id="screening" required>
                                <option value="">Choose a Screening...</option>
                                <?php foreach ($screenings as $key => $data): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $data['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="screening-card" class="screening-card">
                            <h3 id="screening-card-name"></h3>
                            <div class="screening-detail">
                                <span>📅 Date:</span>
                                <strong id="screening-card-date"></strong>
                            </div>
                            <div class="screening-detail">
                                <span>📍 Location:</span>
                                <strong id="screening-card-location"></strong>
                            </div>
                            <div class="screening-detail">
                                <span>🎟️ Seats Remaining:</span>
                                <strong id="screening-card-seats"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Ticket Type Selection -->
                    <div class="form-step" data-step="2">
                        <div class="form-group">
                            <label for="ticketType">Ticket Type</label>
                            <select name="ticket_type" id="ticketType" required>
                                <option value="">Choose Ticket Type...</option>
                                <option value="reserved">Reserved Seat ($5 Deposit)</option>
                                <option value="fcfs">First Come First Serve (Free)</option>
                            </select>
                        </div>

                        <div id="reserved-info" class="reserved-callout">
                            <strong>💳 E-Transfer Required</strong>
                            <p>Send a $5 e-transfer to: <strong>YOUR EMAIL HERE</strong><br><br>
                            Enter the reference number below to complete your reservation.</p>
                        </div>
                    </div>

                    <!-- Step 3: Personal Details -->
                    <div class="form-step" data-step="3">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <input type="text" name="first_name" id="firstName" required>
                        </div>

                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <input type="text" name="last_name" id="lastName" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" required>
                        </div>

                        <div class="form-group">
                            <label for="tickets"># of Tickets</label>
                            <input type="number" name="tickets" id="tickets" min="1" required>
                        </div>

                        <div id="reference-field" class="form-group hidden">
                            <label for="reference_number">E-Transfer Reference Number</label>
                            <input type="text" name="reference_number" id="reference_number" placeholder="e.g., ABC123XYZ">
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" id="prevBtn" disabled>← Previous</button>
                    <button type="button" class="btn btn-primary" id="nextBtn">Next →</button>
                </div>

                <button type="submit" name="submit_ticket" class="btn btn-primary hidden" id="submitBtn" style="margin-top: 12px;">Submit Reservation</button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('ticketForm');
        const screeningDropdown = document.getElementById('screening');
        const ticketTypeDropdown = document.getElementById('ticketType');
        const screeningCard = document.getElementById('screening-card');
        const reservedInfo = document.getElementById('reserved-info');
        const referenceField = document.getElementById('reference-field');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const currentStepTitle = document.getElementById('currentStepTitle');

        const screeningInfo = <?php echo json_encode($screening_info); ?>;
        const remainingSeats = <?php echo json_encode($remaining_seats); ?>;

        let currentStep = 1;
        const totalSteps = 3;

        const stepTitles = {
            1: 'Select Your Screening',
            2: 'Choose Ticket Type',
            3: 'Enter Your Details'
        };

        function updateStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(el => {
                el.classList.remove('active', 'prev');
            });

            // Show current step
            const currentStepEl = document.querySelector(`.form-step[data-step="${step}"]`);
            currentStepEl.classList.add('active');

            // Update progress indicator
            document.querySelectorAll('.progress-step').forEach(el => {
                const stepNum = parseInt(el.dataset.step);
                el.classList.remove('active', 'completed');
                if (stepNum < step) {
                    el.classList.add('completed');
                } else if (stepNum === step) {
                    el.classList.add('active');
                }
            });

            // Update buttons
            prevBtn.disabled = step === 1;
            nextBtn.style.display = step === totalSteps ? 'none' : 'block';
            submitBtn.classList.toggle('hidden', step !== totalSteps);

            // Update title
            currentStepTitle.textContent = stepTitles[step];

            currentStep = step;
        }

        function validateStep(step) {
            const screening = screeningDropdown.value.trim();
            const ticketType = ticketTypeDropdown.value.trim();
            const firstName = document.getElementById('firstName').value.trim();
            const lastName = document.getElementById('lastName').value.trim();
            const email = document.getElementById('email').value.trim();
            const tickets = document.getElementById('tickets').value.trim();

            if (step === 1) return screening !== '';
            if (step === 2) return ticketType !== '';
            if (step === 3) return firstName && lastName && email && tickets;
            return true;
        }

        prevBtn.addEventListener('click', function() {
            if (currentStep > 1) {
                updateStep(currentStep - 1);
            }
        });

        nextBtn.addEventListener('click', function() {
            if (validateStep(currentStep)) {
                if (currentStep < totalSteps) {
                    updateStep(currentStep + 1);
                }
            } else {
                alert('Please complete this step before proceeding.');
            }
        });

        screeningDropdown.addEventListener('change', function() {
            const val = this.value;
            if (val && screeningInfo[val]) {
                const parts = screeningInfo[val].split('<br>');
                const name = parts[0];
                const date = parts[1].replace('Date: ', '');
                const location = parts[2].replace('Location: ', '');
                const seats = remainingSeats[val];

                document.getElementById('screening-card-name').textContent = name;
                document.getElementById('screening-card-date').textContent = date;
                document.getElementById('screening-card-location').textContent = location;
                document.getElementById('screening-card-seats').textContent = seats;

                screeningCard.classList.add('show');
            } else {
                screeningCard.classList.remove('show');
            }
        });

        ticketTypeDropdown.addEventListener('change', function() {
            const isReserved = this.value === 'reserved';
            if (isReserved) {
                reservedInfo.classList.add('show');
                referenceField.classList.remove('hidden');
            } else {
                reservedInfo.classList.remove('show');
                referenceField.classList.add('hidden');
            }
        });
    });
    </script>

    <?php
    return ob_get_clean();
}