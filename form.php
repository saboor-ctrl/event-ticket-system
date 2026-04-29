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

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;500;600&display=swap');

    * {
        box-sizing: border-box;
    }

    .sts-form-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 40px;
        background: #0a0a0a;
        border-radius: 12px;
        font-family: 'Inter', sans-serif;
        color: #e0e0e0;
    }

    .sts-form-heading {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        margin-bottom: 30px;
        color: #ffffff;
        text-align: center;
        letter-spacing: 1px;
    }

    .sts-form-group {
        margin-bottom: 28px;
    }

    .sts-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 10px;
        color: #ffffff;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .sts-select {
        width: 100%;
        padding: 12px 14px;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 4px;
        color: #e0e0e0;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .sts-select:hover {
        border-color: #555;
    }

    .sts-select:focus {
        outline: none;
        border-color: #B41E1E;
        box-shadow: 0 0 8px rgba(180, 30, 30, 0.3);
    }

    /* Screening Info Card */
    .sts-screening-card {
        background: #0f0f0f;
        border: 1px solid #333;
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 28px;
        display: none;
    }

    .sts-screening-card.active {
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

    .sts-screening-title {
        font-size: 18px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 10px;
    }

    .sts-screening-detail {
        font-size: 13px;
        color: #b0b0b0;
        margin-bottom: 6px;
    }

    .sts-seats-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        margin-top: 10px;
    }

    .sts-seats-badge.available {
        background: #1a5c2a;
        color: #66dd99;
    }

    .sts-seats-badge.sold-out {
        background: #6b1f1f;
        color: #ff6b6b;
    }

    /* Ticket Type Cards */
    .sts-ticket-cards {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 28px;
    }

    .sts-ticket-card {
        background: #1a1a1a;
        border: 2px solid #333;
        border-radius: 6px;
        padding: 18px;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
    }

    .sts-ticket-card:hover {
        border-color: #555;
    }

    .sts-ticket-card.selected {
        border-color: #B41E1E;
        background: rgba(180, 30, 30, 0.05);
    }

    .sts-ticket-card.selected::before {
        content: '✓';
        position: absolute;
        top: 10px;
        right: 10px;
        width: 24px;
        height: 24px;
        background: #B41E1E;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        font-weight: bold;
    }

    .sts-ticket-title {
        font-size: 15px;
        font-weight: 600;
        color: #ffffff;
        margin-bottom: 8px;
    }

    .sts-ticket-badge {
        display: inline-block;
        background: #333;
        color: #e0e0e0;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .sts-ticket-card.selected .sts-ticket-badge {
        background: #B41E1E;
        color: #ffffff;
    }

    .sts-ticket-description {
        font-size: 12px;
        color: #999;
        line-height: 1.5;
    }

    /* Reserved Instructions Callout */
    .sts-reserved-callout {
        background: rgba(180, 30, 30, 0.08);
        border-left: 4px solid #B41E1E;
        border-radius: 4px;
        padding: 14px 16px;
        margin-bottom: 20px;
        display: none;
        animation: slideIn 0.3s ease;
    }

    .sts-reserved-callout.active {
        display: block;
    }

    .sts-reserved-callout p {
        font-size: 13px;
        color: #e0e0e0;
        margin: 0 0 8px 0;
        line-height: 1.6;
    }

    .sts-reserved-callout p:last-child {
        margin-bottom: 0;
    }

    .sts-reserved-highlight {
        color: #ff9999;
        font-weight: 600;
    }

    /* Form Inputs */
    .sts-input {
        width: 100%;
        padding: 12px 14px;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 4px;
        color: #e0e0e0;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .sts-input::placeholder {
        color: #555;
    }

    .sts-input:focus {
        outline: none;
        border-color: #B41E1E;
        box-shadow: 0 0 8px rgba(180, 30, 30, 0.3);
    }

    /* Submit Button */
    .sts-submit-btn {
        width: 100%;
        padding: 14px;
        background: #B41E1E;
        color: #ffffff;
        border: none;
        border-radius: 4px;
        font-family: 'Playfair Display', serif;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.2s ease;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    .sts-submit-btn:hover {
        background: #8b1515;
        box-shadow: 0 4px 16px rgba(180, 30, 30, 0.4);
    }

    .sts-submit-btn:active {
        transform: translateY(1px);
    }

    /* Hidden */
    .sts-hidden {
        display: none;
    }

    /* Warning Message */
    .sts-warning-message {
        background: rgba(180, 30, 30, 0.15);
        border: 1px solid #B41E1E;
        border-radius: 4px;
        padding: 12px 14px;
        margin-bottom: 16px;
        color: #ff9999;
        font-size: 13px;
        font-weight: 500;
        display: none;
        animation: slideIn 0.3s ease;
    }

    .sts-warning-message.show {
        display: block;
    }

    .sts-ticket-cards.warning-highlight {
        border: 2px solid #B41E1E;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 28px;
    }

    /* Mobile Responsive */
    @media (max-width: 480px) {
        .sts-form-container {
            padding: 16px;
            margin: 20px auto;
        }

        .sts-form-heading {
            font-size: 24px;
            margin-bottom: 24px;
        }

        .sts-ticket-cards {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .sts-ticket-card {
            padding: 16px;
        }

        .sts-label {
            font-size: 13px;
        }

        .sts-input,
        .sts-select {
            font-size: 16px;
            padding: 14px;
        }

        .sts-submit-btn {
            padding: 12px;
            font-size: 14px;
        }
    }
    </style>

    <div class="sts-form-container">
        <div class="sts-form-heading">Get Your Tickets</div>

        <form method="post">

            <!-- Screening Selection -->
            <div class="sts-form-group">
                <label class="sts-label">Select Screening</label>
                <select name="screening" id="sts-screening" class="sts-select" required>
                    <option value="">Choose a screening...</option>
                    <?php foreach ($screenings as $key => $data): ?>
                        <option value="<?php echo $key; ?>"><?php echo $data['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Screening Details Card -->
            <div id="sts-screening-card" class="sts-screening-card">
                <div class="sts-screening-title" id="sts-screening-title"></div>
                <div class="sts-screening-detail"><strong>Date:</strong> <span id="sts-screening-date"></span></div>
                <div class="sts-screening-detail"><strong>Location:</strong> <span id="sts-screening-location"></span></div>
                <div id="sts-seats-badge" class="sts-seats-badge"></div>
            </div>

            <!-- Ticket Type Cards -->
            <div class="sts-form-group">
                <div id="sts-warning-message" class="sts-warning-message">⚠️ Please select a ticket type.</div>
                <label class="sts-label">Select Ticket Type</label>
                <div id="sts-ticket-cards-container" class="sts-ticket-cards">
                    <div class="sts-ticket-card" data-value="reserved">
                        <div class="sts-ticket-title">Reserved Seat</div>
                        <div class="sts-ticket-badge">$5 Deposit</div>
                        <div class="sts-ticket-description">A $5 e-transfer deposit guarantees your seat. Your deposit will be refunded in full when you arrive.</div>
                    </div>
                    <div class="sts-ticket-card" data-value="fcfs">
                        <div class="sts-ticket-title">Same Day Entry</div>
                        <div class="sts-ticket-badge">Free</div>
                        <div class="sts-ticket-description">Walk-in entry with no deposit required. Arrive early to secure your spot.</div>
                    </div>
                </div>
                <input type="hidden" name="ticket_type" id="sts-ticket-type" required>
            </div>

            <!-- Reserved Instructions Callout -->
            <div id="sts-reserved-callout" class="sts-reserved-callout">
                <p>Send a <span class="sts-reserved-highlight">$5 e-transfer</span> to:</p>
                <p><span class="sts-reserved-highlight">YOUR EMAIL HERE</span></p>
                <p>Then enter the reference number below.</p>
            </div>

            <!-- Reference Number (for Reserved only) -->
            <div id="sts-reference-field" class="sts-form-group sts-hidden">
                <label class="sts-label">E-Transfer Reference Number</label>
                <input type="text" name="reference_number" class="sts-input" placeholder="e.g. A1B2C3D4E5">
            </div>

            <!-- Personal Info -->
            <div class="sts-form-group">
                <label class="sts-label">First Name</label>
                <input type="text" name="first_name" class="sts-input" required>
            </div>

            <div class="sts-form-group">
                <label class="sts-label">Last Name</label>
                <input type="text" name="last_name" class="sts-input" required>
            </div>

            <div class="sts-form-group">
                <label class="sts-label">Email</label>
                <input type="email" name="email" class="sts-input" required>
            </div>

            <div class="sts-form-group">
                <label class="sts-label">Number of Tickets</label>
                <input type="number" name="tickets" class="sts-input" min="1" value="1" required>
            </div>

            <button type="submit" name="submit_ticket" class="sts-submit-btn">Book Now</button>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const screeningSelect = document.getElementById("sts-screening");
        const screeningCard = document.getElementById("sts-screening-card");
        const screeningTitle = document.getElementById("sts-screening-title");
        const screeningDate = document.getElementById("sts-screening-date");
        const screeningLocation = document.getElementById("sts-screening-location");
        const seatsBadge = document.getElementById("sts-seats-badge");
        const ticketTypeInput = document.getElementById("sts-ticket-type");
        const ticketCards = document.querySelectorAll(".sts-ticket-card");
        const reservedCallout = document.getElementById("sts-reserved-callout");
        const referenceField = document.getElementById("sts-reference-field");

        const screeningData = <?php echo json_encode($screenings); ?>;
        const remainingSeats = <?php echo json_encode($remaining_seats); ?>;

        // Handle screening selection
        screeningSelect.addEventListener("change", function() {
            if (!this.value) {
                screeningCard.classList.remove("active");
                return;
            }

            const data = screeningData[this.value];
            const seats = remainingSeats[this.value];

            screeningTitle.textContent = data.name;
            screeningDate.textContent = data.date;
            screeningLocation.textContent = data.location;

            seatsBadge.textContent = seats + " Seats Remaining";
            seatsBadge.className = "sts-seats-badge " + (seats > 0 ? "available" : "sold-out");

            screeningCard.classList.add("active");
        });

        // Handle ticket card selection
        ticketCards.forEach(card => {
            card.addEventListener("click", function() {
                ticketCards.forEach(c => c.classList.remove("selected"));
                this.classList.add("selected");
                ticketTypeInput.value = this.dataset.value;

                // Show/hide reserved callout and reference field
                const isReserved = this.dataset.value === "reserved";
                reservedCallout.classList.toggle("active", isReserved);
                referenceField.classList.toggle("sts-hidden", !isReserved);

                // Hide warning message
                document.getElementById("sts-warning-message").classList.remove("show");
                document.getElementById("sts-ticket-cards-container").classList.remove("warning-highlight");
            });
        });

        // Form submission validation
        const form = document.querySelector(".sts-form-container form");
        form.addEventListener("submit", function(e) {
            if (!ticketTypeInput.value) {
                e.preventDefault();
                const warningMessage = document.getElementById("sts-warning-message");
                const ticketCardsContainer = document.getElementById("sts-ticket-cards-container");
                warningMessage.classList.add("show");
                ticketCardsContainer.classList.add("warning-highlight");
                ticketCardsContainer.scrollIntoView({ behavior: "smooth", block: "nearest" });
            }
        });
    });
    </script>

    <?php
    return ob_get_clean();
}