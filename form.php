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
    .form-box { max-width: 500px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; font-family: Arial; }
    .form-box input, .form-box select { width: 100%; padding: 10px; margin: 8px 0; }
    .form-box button { width: 100%; padding: 12px; background: black; color: white; border: none; cursor: pointer; }
    .hidden { display: none; }
    .seats-display { font-weight: bold; margin-bottom: 10px; }
    </style>

    <div class="form-box">
        <form method="post">

            <label>Screening</label>
            <select name="screening" id="screening" required>
                <option value="">Select Screening</option>
                <?php foreach ($screenings as $key => $data): ?>
                    <option value="<?php echo $key; ?>"><?php echo $data['name']; ?></option>
                <?php endforeach; ?>
            </select>

            <div id="screening-info"></div>
            <div id="seats-remaining" class="seats-display"></div>

            <label>Ticket Type</label>
            <select name="ticket_type" id="ticketType" required>
                <option value="">Select</option>
                <option value="reserved">Reserved Seat ($5 Deposit)</option>
                <option value="fcfs">First Come First Serve (Free)</option>
            </select>

            <div id="reservedFields" class="hidden">
                <p>Send a $5 e-transfer to: YOUR EMAIL HERE<br>Then enter the reference number below.</p>
                <input type="text" name="reference_number" placeholder="Reference Number">
            </div>

            <label>First Name</label>
            <input type="text" name="first_name" required>

            <label>Last Name</label>
            <input type="text" name="last_name" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label># of Tickets</label>
            <input type="number" name="tickets" min="1" required>

            <button type="submit" name="submit_ticket">Submit</button>
        </form>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const dropdown = document.getElementById("ticketType");
        const reservedFields = document.getElementById("reservedFields");
        const screeningDropdown = document.getElementById("screening");
        const infoDiv = document.getElementById("screening-info");
        const seatsDiv = document.getElementById("seats-remaining");

        const screeningInfo = <?php echo json_encode($screening_info); ?>;
        const remainingSeats = <?php echo json_encode($remaining_seats); ?>;

        dropdown.addEventListener("change", function() {
            reservedFields.classList.toggle("hidden", this.value !== "reserved");
        });

        screeningDropdown.addEventListener("change", function() {
            const val = this.value;
            infoDiv.innerHTML = screeningInfo[val] || "";
            seatsDiv.innerHTML = val ? "Seats Remaining: " + remainingSeats[val] : "";
        });
    });
    </script>

    <?php
    return ob_get_clean();
}