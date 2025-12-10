<?php
require_once 'config.php';

// Check if user is logged in and active
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'book_table.php';
    header('Location: login.php?message=Please login to book a table');
    exit();
}

// Check if user account is still active
checkUserActive();

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get available tables
$available_tables = $pdo->query("SELECT * FROM restaurant_tables WHERE is_available = 1 ORDER BY location, capacity")->fetchAll(PDO::FETCH_ASSOC);

$success = '';
$error = '';

if ($_POST) {
    $table_id = intval($_POST['table_id']);
    $booking_date = $_POST['booking_date'];
    $booking_time = $_POST['booking_time'];
    $party_size = intval($_POST['party_size']);
    $phone = sanitize($_POST['phone']);
    $message = sanitize($_POST['message']);
    $booking_type = $_POST['booking_type'] ?? 'self';
    
    // Guest information (if booking for someone else)
    $guest_name = sanitize($_POST['guest_name'] ?? '');
    $guest_email = sanitize($_POST['guest_email'] ?? '');
    $guest_phone = sanitize($_POST['guest_phone'] ?? '');
    
    // Validate inputs
    if (empty($table_id) || empty($booking_date) || empty($booking_time) || empty($party_size)) {
        $error = 'Please fill in all required fields.';
    } elseif ($party_size < 1 || $party_size > 20) {
        $error = 'Party size must be between 1 and 8 people.';
    } elseif (strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = 'Booking date cannot be in the past.';
    } elseif (strtotime($booking_date . ' ' . $booking_time) < strtotime('now')) {
        $error = 'Booking time cannot be in the past. Please select a future date and time.';
    } elseif ($booking_type == 'guest' && (empty($guest_name) || empty($guest_email))) {
        $error = 'Guest name and email are required when booking for someone else.';
    } elseif ($booking_type == 'guest' && !validateEmail($guest_email)) {
        $error = 'Please enter a valid guest email address.';
    } else {
        // Check if table is still available for that date and time
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE table_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'");
        $stmt->execute([$table_id, $booking_date, $booking_time]);
        $existing_booking = $stmt->fetchColumn();
        
        if ($existing_booking > 0) {
            $error = 'This table is already booked for the selected date and time. Please choose a different time or table.';
        } else {
            // Check table capacity
            $stmt = $pdo->prepare("SELECT capacity FROM restaurant_tables WHERE id = ?");
            $stmt->execute([$table_id]);
            $table_capacity = $stmt->fetchColumn();
            
            if ($party_size > $table_capacity) {
                $error = "Selected table can only accommodate up to {$table_capacity} people. Please choose a larger table or reduce party size.";
            } else {
                // Prepare booking data based on type
                if ($booking_type == 'self') {
                    // Booking for self
                    $booking_name = $user['first_name'] . ' ' . $user['last_name'];
                    $booking_email = $user['email'];
                    $booking_phone = $phone ?: $user['phone'];
                    $user_id = $_SESSION['user_id'];
                    $booked_by_user_id = $_SESSION['user_id'];
                } else {
                    // Booking for guest
                    $booking_name = $guest_name;
                    $booking_email = $guest_email;
                    $booking_phone = $guest_phone;
                    $user_id = null; // Guest doesn't have user account
                    $booked_by_user_id = $_SESSION['user_id']; // Track who made the booking
                }
                
                // Insert booking
                $stmt = $pdo->prepare("INSERT INTO bookings (user_id, booked_by_user_id, table_id, name, email, phone, message, booking_date, booking_time, party_size, booking_type, guest_name, guest_email, guest_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                if ($stmt->execute([
                    $user_id,
                    $booked_by_user_id,
                    $table_id,
                    $booking_name,
                    $booking_email,
                    $booking_phone,
                    $message,
                    $booking_date,
                    $booking_time,
                    $party_size,
                    $booking_type,
                    $booking_type == 'guest' ? $guest_name : null,
                    $booking_type == 'guest' ? $guest_email : null,
                    $booking_type == 'guest' ? $guest_phone : null
                ])) {
                    if ($booking_type == 'self') {
                        $success = 'Your table has been successfully booked! We will contact you shortly to confirm the details.';
                    } else {
                        $success = "Table successfully booked for {$guest_name}! We will contact them at {$guest_email} to confirm the details.";
                    }
                } else {
                    $error = 'Failed to book table. Please try again.';
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Table Booking - BrewMaster Coffee</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            padding-top: 80px;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }

        .booking-header {
            background: linear-gradient(135deg, #6B4423, #8B4513);
            color: white;
            padding: 3rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .booking-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .booking-header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .booking-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }

        .booking-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .form-section h3 {
            color: #6B4423;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #6B4423;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #D2691E;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .table-selection {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .table-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .table-card:hover {
            border-color: #D2691E;
            transform: translateY(-2px);
        }

        .table-card.selected {
            border-color: #D2691E;
            background: #fff8f0;
        }

        .table-card input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .table-card .table-icon {
            font-size: 2rem;
            color: #D2691E;
            margin-bottom: 0.5rem;
        }

        .table-card .table-name {
            font-weight: bold;
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .table-card .table-details {
            font-size: 0.9rem;
            color: #666;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #D2691E, #FF8C00);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            margin-top: 2rem;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(210, 105, 30, 0.3);
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #28a745;
            text-align: center;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid #dc3545;
        }

        .user-info {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .user-info h4 {
            color: #6B4423;
            margin-bottom: 0.5rem;
        }

        .time-info {
            background: #e9ecef;
            padding: 0.75rem;
            border-radius: 5px;
            margin-top: 0.5rem;
            font-size: 0.9rem;
            color: #495057;
            display: none;
        }

        .time-info.show {
            display: block;
        }

        .time-info.warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .form-group small {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        @media (max-width: 768px) {
            .booking-content {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .table-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include_once("navigation.php"); ?>

    <div class="container">
        <div class="booking-header">
            <h1><i class="fas fa-utensils"></i> Reserve Your Table</h1>
            <p>Book your perfect spot at BrewMaster Coffee and enjoy our premium coffee experience</p>
        </div>

        <?php if ($success): ?>
            <div class="success">
                <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                <h3><?php echo $success; ?></h3>
                <p style="margin-top: 1rem;">
                    <a href="dashboard.php" class="btn" style="display: inline-block; width: auto; margin: 0.5rem;">View My Bookings</a>
                    <a href="index.php" class="btn" style="display: inline-block; width: auto; margin: 0.5rem;">Return Home</a>
                </p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="booking-content">
            <!-- Booking Form -->
            <div class="booking-form">
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Booking Details</h3>
                    
                    <div class="user-info">
                        <h4>Logged in as:</h4>
                        <p><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></p>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label>Booking Type *</label>
                            <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                                <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                                    <input type="radio" name="booking_type" value="self" checked onchange="toggleBookingType()" style="margin-right: 0.5rem;">
                                    Book for myself
                                </label>
                                <label style="display: flex; align-items: center; font-weight: normal; cursor: pointer;">
                                    <input type="radio" name="booking_type" value="guest" onchange="toggleBookingType()" style="margin-right: 0.5rem;">
                                    Book for someone else
                                </label>
                            </div>
                        </div>

                        <!-- Guest Information Section -->
                        <div id="guestInfo" style="display: none; background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; border-left: 4px solid #D2691E;">
                            <h4 style="color: #6B4423; margin-bottom: 1rem;">Guest Information</h4>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="guest_name">Guest Full Name *</label>
                                    <input type="text" id="guest_name" name="guest_name" placeholder="Enter guest's full name">
                                </div>
                                <div class="form-group">
                                    <label for="guest_email">Guest Email *</label>
                                    <input type="email" id="guest_email" name="guest_email" placeholder="Enter guest's email">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="guest_phone">Guest Phone Number</label>
                                <input type="tel" id="guest_phone" name="guest_phone" placeholder="Enter guest's phone number">
                            </div>
                            
                            <div style="background: #e9ecef; padding: 1rem; border-radius: 5px; font-size: 0.9rem; color: #495057;">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Note:</strong> The guest will receive confirmation details at their email address. You can manage this booking from your dashboard.
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="booking_date">Date *</label>
                                <input type="date" id="booking_date" name="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                                <small style="color: #666; font-size: 0.9rem; margin-top: 0.25rem; display: block;">
                                    <i class="fas fa-calendar"></i> Select today or any future date
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="booking_time">Time *</label>
                                <select id="booking_time" name="booking_time" required>
                                    <option value="">Select Time</option>
                                    <?php
                                    // Generate time slots from 8:00 AM to 10:00 PM (30-minute intervals)
                                    $start_hour = 8;
                                    $end_hour = 22;
                                    
                                    for ($hour = $start_hour; $hour <= $end_hour; $hour++) {
                                        for ($minute = 0; $minute < 60; $minute += 30) {
                                            $time_24 = sprintf('%02d:%02d', $hour, $minute);
                                            $time_12 = date('g:i A', strtotime($time_24));
                                            
                                            // Stop at 10:00 PM
                                            if ($hour == 22 && $minute > 0) break;
                                            
                                            echo "<option value=\"{$time_24}\">{$time_12}</option>";
                                        }
                                    }
                                    ?>
                                </select>
                                <small style="color: #666; font-size: 0.9rem; margin-top: 0.25rem; display: block;">
                                    <i class="fas fa-info-circle"></i> Restaurant hours: 8:00 AM - 10:00 PM
                                </small>
                                <div id="timeInfo" class="time-info">
                                    <i class="fas fa-clock"></i> <span id="timeInfoText"></span>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="party_size">Party Size *</label>
                                <select id="party_size" name="party_size" required>
                                    <option value="">Select Size</option>
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $i == 1 ? 'Person' : 'People'; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group" id="selfPhoneGroup">
                                <label for="phone">Your Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Your contact number">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Special Requests</label>
                            <textarea id="message" name="message" rows="3" placeholder="Any special requests or dietary requirements..."></textarea>
                        </div>

                        <input type="hidden" id="selected_table" name="table_id" value="">
                        
                        <button type="submit" class="btn" id="bookBtn" disabled onclick="return validateBookingForm()">
                            <i class="fas fa-calendar-check"></i> Book Table
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table Selection -->
            <div class="table-selection">
                <h3><i class="fas fa-chair"></i> Select Your Table</h3>
                <p style="color: #666; margin-bottom: 1rem;">Choose from our available tables based on your party size and preference.</p>
                
                <div class="table-grid">
                    <?php foreach ($available_tables as $table): ?>
                        <div class="table-card" onclick="selectTable(<?php echo $table['id']; ?>, <?php echo $table['capacity']; ?>)">
                            <input type="radio" name="table_selection" value="<?php echo $table['id']; ?>" id="table_<?php echo $table['id']; ?>">
                            <div class="table-icon">
                                <?php if ($table['location'] == 'Window Side'): ?>
                                    <i class="fas fa-window-maximize"></i>
                                <?php elseif ($table['location'] == 'Private Area'): ?>
                                    <i class="fas fa-door-closed"></i>
                                <?php elseif ($table['location'] == 'Outdoor Patio'): ?>
                                    <i class="fas fa-tree"></i>
                                <?php elseif ($table['location'] == 'Bar Area'): ?>
                                    <i class="fas fa-wine-glass"></i>
                                <?php else: ?>
                                    <i class="fas fa-chair"></i>
                                <?php endif; ?>
                            </div>
                            <div class="table-name"><?php echo htmlspecialchars($table['table_name']); ?></div>
                            <div class="table-details">
                                <div><strong><?php echo $table['table_number']; ?></strong></div>
                                <div>Seats: <?php echo $table['capacity']; ?> people</div>
                                <div><?php echo htmlspecialchars($table['location']); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include_once("footer.php"); ?>

    <script>
        function toggleBookingType() {
            const bookingType = document.querySelector('input[name="booking_type"]:checked').value;
            const guestInfo = document.getElementById('guestInfo');
            const selfPhoneGroup = document.getElementById('selfPhoneGroup');
            const guestNameField = document.getElementById('guest_name');
            const guestEmailField = document.getElementById('guest_email');
            
            if (bookingType === 'guest') {
                guestInfo.style.display = 'block';
                selfPhoneGroup.style.display = 'none';
                guestNameField.required = true;
                guestEmailField.required = true;
            } else {
                guestInfo.style.display = 'none';
                selfPhoneGroup.style.display = 'block';
                guestNameField.required = false;
                guestEmailField.required = false;
            }
        }

        function selectTable(tableId, capacity) {
            // Remove selected class from all tables
            document.querySelectorAll('.table-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked table
            event.currentTarget.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('selected_table').value = tableId;
            
            // Enable book button
            document.getElementById('bookBtn').disabled = false;
            
            // Check party size compatibility
            const partySize = document.getElementById('party_size').value;
            if (partySize && parseInt(partySize) > capacity) {
                alert(`This table can only accommodate ${capacity} people. Please select a larger table or reduce your party size.`);
                document.getElementById('bookBtn').disabled = true;
            }
        }

        // Check table capacity when party size changes
        document.getElementById('party_size').addEventListener('change', function() {
            const selectedTable = document.querySelector('.table-card.selected');
            if (selectedTable) {
                const tableCapacity = parseInt(selectedTable.querySelector('.table-details div:nth-child(2)').textContent.match(/\d+/)[0]);
                const partySize = parseInt(this.value);
                
                if (partySize > tableCapacity) {
                    alert(`Selected table can only accommodate ${tableCapacity} people. Please select a larger table.`);
                    document.getElementById('bookBtn').disabled = true;
                } else {
                    document.getElementById('bookBtn').disabled = false;
                }
            }
        });

        // Filter time options based on selected date
        function filterTimeOptions() {
            const selectedDate = document.getElementById('booking_date').value;
            const timeSelect = document.getElementById('booking_time');
            const timeInfo = document.getElementById('timeInfo');
            const timeInfoText = document.getElementById('timeInfoText');
            const today = new Date().toISOString().split('T')[0];
            const currentTime = new Date();
            const currentHour = currentTime.getHours();
            const currentMinute = currentTime.getMinutes();
            
            // Reset all options to be visible first
            Array.from(timeSelect.options).forEach(option => {
                if (option.value) {
                    option.style.display = 'block';
                    option.disabled = false;
                }
            });
            
            // Hide time info by default
            timeInfo.classList.remove('show', 'warning');
            
            if (!selectedDate) {
                return;
            }
            
            // If today is selected, disable past times
            if (selectedDate === today) {
                let availableSlots = 0;
                const nextAvailableTime = new Date();
                nextAvailableTime.setMinutes(nextAvailableTime.getMinutes() + 30);
                
                Array.from(timeSelect.options).forEach(option => {
                    if (option.value) {
                        const [optionHour, optionMinute] = option.value.split(':').map(Number);
                        const optionTotalMinutes = optionHour * 60 + optionMinute;
                        const currentTotalMinutes = currentHour * 60 + currentMinute;
                        
                        // Add 30 minutes buffer to current time
                        if (optionTotalMinutes <= currentTotalMinutes + 30) {
                            option.style.display = 'none';
                            option.disabled = true;
                            
                            // Clear selection if currently selected time is now invalid
                            if (option.selected) {
                                timeSelect.value = '';
                            }
                        } else {
                            availableSlots++;
                        }
                    }
                });
                
                // Show info message
                if (availableSlots === 0) {
                    timeInfoText.textContent = 'No time slots available for today. Please select a future date.';
                    timeInfo.classList.add('show', 'warning');
                } else {
                    const nextSlotTime = Math.ceil((currentHour * 60 + currentMinute + 30) / 30) * 30;
                    const nextHour = Math.floor(nextSlotTime / 60);
                    const nextMinute = nextSlotTime % 60;
                    const nextTimeFormatted = new Date(0, 0, 0, nextHour, nextMinute).toLocaleTimeString('en-US', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    timeInfoText.textContent = `Booking available from ${nextTimeFormatted} onwards (30-minute advance booking required).`;
                    timeInfo.classList.add('show');
                }
            } else {
                // Future date selected
                timeInfoText.textContent = 'All time slots available for the selected date.';
                timeInfo.classList.add('show');
            }
        }

        // Add event listener for date change
        document.getElementById('booking_date').addEventListener('change', filterTimeOptions);

        // Filter times on page load if date is already selected
        document.addEventListener('DOMContentLoaded', function() {
            filterTimeOptions();
        });

        // Validate booking form before submission
        function validateBookingForm() {
            const selectedDate = document.getElementById('booking_date').value;
            const selectedTime = document.getElementById('booking_time').value;
            const selectedTable = document.getElementById('selected_table').value;
            const partySize = document.getElementById('party_size').value;
            
            if (!selectedDate) {
                alert('Please select a booking date.');
                return false;
            }
            
            if (!selectedTime) {
                alert('Please select a booking time.');
                return false;
            }
            
            if (!selectedTable) {
                alert('Please select a table.');
                return false;
            }
            
            if (!partySize) {
                alert('Please select party size.');
                return false;
            }
            
            // Check if selected time is in the past
            const today = new Date().toISOString().split('T')[0];
            if (selectedDate === today) {
                const currentTime = new Date();
                const selectedDateTime = new Date(selectedDate + 'T' + selectedTime);
                const currentDateTime = new Date();
                
                if (selectedDateTime <= currentDateTime) {
                    alert('Selected time is in the past. Please choose a future time.');
                    return false;
                }
                
                // Check 30-minute advance booking requirement
                const timeDiff = selectedDateTime - currentDateTime;
                const minutesDiff = timeDiff / (1000 * 60);
                
                if (minutesDiff < 30) {
                    alert('Bookings must be made at least 30 minutes in advance. Please select a later time.');
                    return false;
                }
            }
            
            // Check booking type validation
            const bookingType = document.querySelector('input[name="booking_type"]:checked').value;
            if (bookingType === 'guest') {
                const guestName = document.getElementById('guest_name').value;
                const guestEmail = document.getElementById('guest_email').value;
                
                if (!guestName || !guestEmail) {
                    alert('Please fill in guest name and email for guest bookings.');
                    return false;
                }
            }
            
            return true;
        }

        // Auto-hide success/error messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.success, .error');
            messages.forEach(message => {
                if (!message.querySelector('a')) { // Don't hide if it contains links
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(() => message.remove(), 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>