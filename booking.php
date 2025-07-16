<?php
require_once 'db_connect.php';

// Assuming film_id and cinema_id are passed from a previous page (e.g., film detail page)
$film_id = isset($_GET['film_id']) ? $_GET['film_id'] : null;
$cinema_id = isset($_GET['cinema_id']) ? $_GET['cinema_id'] : null;
$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : null;

// Ambil detail film jika film_id sudah ada
$film_details = null;
if ($film_id) {
    $stmt = $conn->prepare("SELECT title, duration, studio FROM films WHERE id = ?");
    $stmt->bind_param("i", $film_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $film_details = $result->fetch_assoc();
}

// Ambil daftar bioskop
$cinemas_result = $conn->query("SELECT * FROM cinemas");
$cinemas = [];
while ($cinema = $cinemas_result->fetch_assoc()) {
    $cinemas[] = $cinema;
}

// Ambil jadwal berdasarkan film dan bioskop
$schedules = [];
if ($film_id && $cinema_id) {
    $stmt = $conn->prepare("SELECT * FROM schedules WHERE film_id = ? AND cinema_id = ?");
    $stmt->bind_param("ii", $film_id, $cinema_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($sch = $result->fetch_assoc()) {
        $schedules[] = $sch;
    }
}

// Ambil detail jadwal yang dipilih
$selected_schedule = null;
if ($schedule_id) {
    $stmt = $conn->prepare("SELECT s.*, f.title, f.price, c.name AS cinema_name FROM schedules s JOIN films f ON s.film_id = f.id JOIN cinemas c ON s.cinema_id = c.id WHERE s.id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $selected_schedule = $result->fetch_assoc();
}

// Proses booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['customer_name'];
    $email = $_POST['customer_email'];
    $phone = $_POST['customer_phone'];
    $payment_method = $_POST['payment_method'];
    $seat_codes = isset($_POST['seat_codes']) ? explode(',', $_POST['seat_codes']) : [];
    $seat_count = count($seat_codes);
    $studio = $_POST['studio'];
    $base_price = $selected_schedule['price'];
    $total_price = $base_price * $seat_count;
    $seat_codes_str = implode(',', array_filter($seat_codes));

    // Validasi agar tidak null
    if (empty($seat_codes_str)) {
        echo "<script>alert('Kursi belum dipilih atau tidak valid.');</script>";
        // Optionally, you can stop script execution here or redirect back
    } else {
        try {
            // Mulai transaksi
            $conn->begin_transaction();

            // Simpan booking
            $stmt = $conn->prepare("INSERT INTO bookings (film_id, cinema_id, customer_name, customer_email, customer_phone, schedule_id, seat_count, total_price, payment_method, seat_code, studio) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssiiisss", $selected_schedule['film_id'], $selected_schedule['cinema_id'], $name, $email, $phone, $schedule_id, $seat_count, $total_price, $payment_method, $seat_codes_str, $studio);
            $stmt->execute();
            $booking_id = $conn->insert_id;

            // Simpan ke orders
            $stmt = $conn->prepare("INSERT INTO orders (booking_id, customer_name, total_price, payment_method) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isds", $booking_id, $name, $total_price, $payment_method);
            $stmt->execute();

            // Update seats
            foreach ($seat_codes as $code) {
                // Insert into booked_seats
                $stmt_booked_seats = $conn->prepare("INSERT INTO booked_seats (booking_id, seat_code) VALUES (?, ?)");
                $stmt_booked_seats->bind_param("is", $booking_id, $code);
                $stmt_booked_seats->execute();

                // Update is_available in seats table
                $stmt_update_seats = $conn->prepare("UPDATE seats SET is_available = 0 WHERE seat_code = ? AND schedule_id = ?");
                $stmt_update_seats->bind_param("si", $code, $schedule_id);
                $stmt_update_seats->execute();
            }

            // Commit transaksi
            $conn->commit();

            echo "<script>
                alert('Pesanan berhasil! ID Booking: $booking_id');
                window.location.href = 'booking_success.php?id=$booking_id';
            </script>";
            exit;

        } catch (mysqli_sql_exception $e) {
            $conn->rollback();
            echo "<script>alert('Gagal memproses pesanan: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Ambil seats
$seats = [];
if ($schedule_id) {
    $stmt = $conn->prepare("SELECT * FROM seats WHERE schedule_id = ? ORDER BY seat_code ASC");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($seat = $result->fetch_assoc()) {
        $seats[] = $seat;
    }
}

// Studio is now fetched directly from film_details if available
$studio = $film_details['studio'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Tiket</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Reset & Body Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, #f0f2f5, #e0e4eb);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Container */
        .container {
            width: 90%;
            max-width: 900px;
            margin: 30px auto;
            padding: 40px;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 10px 10px 30px rgba(0, 0, 0, 0.1), -10px -10px 30px rgba(255, 255, 255, 0.8);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Headings */
        h2 {
            text-align: center;
            color: #1a2a3a;
            font-size: 32px;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 10px;
        }

        h2::after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: 0;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: #3498db;
            border-radius: 2px;
        }

        h3 {
            color: #2c3e50;
            font-size: 24px;
            margin-top: 30px;
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        /* Form Sections */
        .form-section {
            margin-bottom: 30px;
            padding: 20px;
            background-color: #f9fbfd;
            border-radius: 15px;
            box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.05), inset -2px -2px 5px rgba(255, 255, 255, 0.8);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #555;
            font-size: 15px;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        select {
            padding: 12px 18px;
            border-radius: 10px;
            border: 1px solid #dcdfe6;
            background-color: #ffffff;
            font-size: 16px;
            color: #333;
            transition: all 0.3s ease-in-out;
            box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.03);
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
            background-color: #eaf6ff;
        }

        /* Buttons */
        .button-group {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 40px;
        }

        .btn-primary,
        .btn-secondary {
            padding: 15px 35px;
            font-size: 17px;
            font-weight: 600;
            text-decoration: none;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            text-align: center;
            display: inline-block;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.1), -5px -5px 10px rgba(255, 255, 255, 0.8);
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-3px);
            box-shadow: 8px 8px 15px rgba(0, 0, 0, 0.15), -8px -8px 15px rgba(255, 255, 255, 0.9);
        }

        .btn-secondary {
            background-color: #7f8c8d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #6c7a89;
            transform: translateY(-3px);
            box-shadow: 8px 8px 15px rgba(0, 0, 0, 0.15), -8px -8px 15px rgba(255, 255, 255, 0.9);
        }

        /* Seat Selection */
        .seat-container {
            margin-top: 30px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 20px;
            background-color: #f9fbfd;
            border-radius: 15px;
            box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.05), inset -2px -2px 5px rgba(255, 255, 255, 0.8);
        }

        .screen {
            font-size: 20px;
            font-weight: bold;
            color: #555;
            padding: 15px 40px;
            background-color: #bdc3c7;
            border-radius: 10px;
            box-shadow: inset 0 3px 6px rgba(0, 0, 0, 0.2);
            width: 80%;
            text-align: center;
            margin-bottom: 20px;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(45px, 1fr));
            gap: 15px;
            justify-content: center;
            width: 100%;
            max-width: 600px; /* Limit width for better alignment */
        }

        .seat {
            width: 45px;
            height: 45px;
            background-color: #ecf0f1;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 3px 3px 7px #d1d9e6, -3px -3px 7px #ffffff;
        }

        .seat:hover:not(.unavailable) {
            transform: translateY(-3px);
            box-shadow: 5px 5px 10px rgba(0, 0, 0, 0.1), -5px -5px 10px rgba(255, 255, 255, 0.9);
        }

        .seat.selected {
            background-color: #2ecc71;
            color: white;
            box-shadow: inset 3px 3px 7px rgba(0, 0, 0, 0.2), inset -3px -3px 7px rgba(255, 255, 255, 0.5);
            transform: scale(0.95);
        }

        .seat.unavailable {
            background-color: #e74c3c;
            color: white;
            cursor: not-allowed;
            opacity: 0.7;
            box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        /* Ticket Count Selector */
        .ticket-count-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 25px 0;
            gap: 15px;
            background-color: #f0f2f5;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: inset 2px 2px 5px rgba(0, 0, 0, 0.05);
        }

        .ticket-count-selector label {
            margin-bottom: 0;
            font-size: 16px;
            color: #333;
        }

        .ticket-count-selector button {
            font-size: 22px;
            padding: 8px 18px;
            background-color: #95a5a6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);
        }

        .ticket-count-selector button:hover {
            background-color: #7f8c8d;
        }

        #ticket-count {
            width: 70px;
            text-align: center;
            font-size: 18px;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 8px;
            background-color: #ffffff;
            box-shadow: inset 1px 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* Alert */
        .alert {
            color: #e74c3c;
            font-weight: bold;
            display: none;
            padding: 12px;
            border-left: 5px solid #e74c3c;
            background-color: #fcecec;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        /* Seat Info */
        .seat-info {
            margin-top: 30px;
            display: flex;
            justify-content: center; /* Center the items */
            gap: 30px;
            padding: 15px;
            background-color: #f0f2f5;
            border-radius: 12px;
            box-shadow: inset 1px 1px 4px rgba(0, 0, 0, 0.05);
        }

        .seat-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            color: #555;
        }

        .seat-info-color {
            width: 25px;
            height: 25px;
            border-radius: 6px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 25px;
                margin: 20px auto;
            }

            h2 {
                font-size: 26px;
                margin-bottom: 30px;
            }

            h3 {
                font-size: 20px;
                margin-top: 20px;
                margin-bottom: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .seat-grid {
                grid-template-columns: repeat(auto-fit, minmax(35px, 1fr));
                gap: 10px;
            }

            .seat {
                width: 35px;
                height: 35px;
            }

            .btn-primary, .btn-secondary {
                padding: 12px 25px;
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            h2 {
                font-size: 22px;
            }
            .seat-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h2>Booking Tiket Bioskop</h2>

        <form method="GET">
            <div class="form-section">
                <?php if ($film_details): ?>
                    <p><strong>Film:</strong> <?= htmlspecialchars($film_details['title']) ?> (<?= $film_details['duration'] ?> menit)</p>
                    <input type="hidden" name="film_id" value="<?= htmlspecialchars($film_id) ?>">
                <?php else: ?>
                    <p style="color: #e74c3c; font-weight: bold;">Film belum dipilih. Silakan kembali ke halaman utama untuk memilih film.</p>
                <?php endif; ?>

                <div class="form-group" style="margin-top: 15px;">
                    <label for="cinema_id">Pilih Bioskop:</label>
                    <select name="cinema_id" onchange="this.form.submit()" required>
                        <option value="">-- Pilih Bioskop --</option>
                        <?php foreach ($cinemas as $cinema): ?>
                            <option value="<?= $cinema['id'] ?>" <?= $cinema_id == $cinema['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cinema['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($film_id && $cinema_id && $schedules): ?>
                    <div class="form-group" style="margin-top: 15px;">
                        <label for="schedule_id">Pilih Jadwal:</label>
                        <select name="schedule_id" onchange="this.form.submit()" required>
                            <option value="">-- Pilih Tanggal & Jam Tayang --</option>
                            <?php foreach ($schedules as $sch): ?>
                                <option value="<?= $sch['id'] ?>" <?= $schedule_id == $sch['id'] ? 'selected' : '' ?>>
                                    <?= date('d F Y', strtotime($sch['date'])) ?> - <?= $sch['time'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($film_id && $cinema_id && empty($schedules)): ?>
                    <p style="color: #e74c3c; font-weight: bold; margin-top: 15px;">Tidak ada jadwal tersedia untuk film ini di bioskop yang dipilih.</p>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($selected_schedule): ?>
            <form method="POST" id="booking-form">
                <input type="hidden" name="seat_codes" id="seat-codes">
                <input type="hidden" name="film_id" value="<?= htmlspecialchars($film_id) ?>">
                <input type="hidden" name="cinema_id" value="<?= htmlspecialchars($cinema_id) ?>">
                <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule_id) ?>">

                <div class="form-section">
                    <h3>Detail Pesanan</h3>
                    <p><strong>Film:</strong> <?= htmlspecialchars($selected_schedule['title']) ?></p>
                    <p><strong>Bioskop:</strong> <?= htmlspecialchars($selected_schedule['cinema_name']) ?></p>
                    <p><strong>Tanggal:</strong> <?= date('d F Y', strtotime($selected_schedule['date'])) ?></p>
                    <p><strong>Jam:</strong> <?= $selected_schedule['time'] ?></p>
                    <p><strong>Harga per tiket:</strong> Rp <?= number_format($selected_schedule['price'], 0, ',', '.') ?></p>

                    <div class="ticket-count-selector">
                        <label>Jumlah Tiket:</label>
                        <button type="button" id="decrement-ticket">-</button>
                        <input type="number" id="ticket-count" name="ticket_count" min="1" max="10" value="1" readonly>
                        <button type="button" id="increment-ticket">+</button>
                    </div>

                    <div class="alert error" id="seat-alert">
                        Silakan pilih <span id="remaining-seats">1</span> kursi lagi.
                    </div>
                </div>

                <div class="form-section">
                    <h3>Pilih Tempat Duduk</h3>
                    <div class="seat-container">
                        <div class="screen">LAYAR BIOSKOP</div>
                        <div class="seat-grid">
                            <?php foreach ($seats as $seat): ?>
                                <div class="seat <?= !$seat['is_available'] ? 'unavailable' : '' ?>"
                                     data-code="<?= $seat['seat_code'] ?>">
                                    <?= $seat['seat_code'] ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group" style="margin-top: 20px;">
                            <label>Studio:</label>
                            <input type="text" name="studio" value="<?= htmlspecialchars($studio) ?>" required readonly>
                        </div>
                        <div class="seat-info">
                            <div class="seat-info-item">
                                <div class="seat-info-color" style="background: #ecf0f1;"></div>
                                <span>Tersedia</span>
                            </div>
                            <div class="seat-info-item">
                                <div class="seat-info-color" style="background: #2ecc71;"></div>
                                <span>Dipilih</span>
                            </div>
                            <div class="seat-info-item">
                                <div class="seat-info-color" style="background: #e74c3c;"></div>
                                <span>Tidak Tersedia</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section form-grid">
                    <div class="form-group">
                        <label for="customer_name">Nama Lengkap</label>
                        <input type="text" id="customer_name" name="customer_name" required placeholder="Masukkan nama Anda">
                    </div>
                    <div class="form-group">
                        <label for="customer_phone">Nomor Telepon</label>
                        <input type="text" id="customer_phone" name="customer_phone" required placeholder="Contoh: 081234567890">
                    </div>
                    <div class="form-group">
                        <label for="customer_email">Email</label>
                        <input type="email" id="customer_email" name="customer_email" required placeholder="Contoh: nama@example.com">
                    </div>
                    <div class="form-group">
                        <label for="payment_method">Metode Pembayaran:</label>
                        <select name="payment_method" id="payment_method" required>
                            <option value="ShopeePay">ShopeePay</option>
                            <option value="DANA">DANA</option>
                            <option value="GoPay">GoPay</option>
                            <option value="QRIS">QRIS</option>
                        </select>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn-primary">Pesan Sekarang</button>
                    <a href="index1.php" class="btn-secondary">Kembali ke Beranda</a>
                </div>
            </form>
        <?php elseif ($film_id && $cinema_id && empty($schedules) === false): ?>
             <p style="text-align: center; color: #555; margin-top: 30px; font-size: 18px;">
                 Silakan pilih jadwal tayang di atas untuk melanjutkan pemesanan.
             </p>
        <?php endif; ?>
    </div>

    <script>
        let selectedSeats = new Set();
        const seatAlert = document.getElementById('seat-alert');
        const remainingSeats = document.getElementById('remaining-seats');
        const seatCodesInput = document.getElementById('seat-codes');
        const ticketCountInput = document.getElementById('ticket-count');
        const decrementTicketBtn = document.getElementById('decrement-ticket');
        const incrementTicketBtn = document.getElementById('increment-ticket');
        const bookingForm = document.getElementById('booking-form');

        document.querySelectorAll('.seat').forEach(seat => {
            seat.addEventListener('click', function() {
                if (seat.classList.contains('unavailable')) {
                    return;
                }

                const seatCode = seat.getAttribute('data-code');
                const desiredSeats = parseInt(ticketCountInput.value);

                if (selectedSeats.has(seatCode)) {
                    selectedSeats.delete(seatCode);
                    seat.classList.remove('selected');
                } else {
                    if (selectedSeats.size >= desiredSeats) {
                        alert(`Anda hanya dapat memilih ${desiredSeats} kursi.`);
                        return;
                    }
                    selectedSeats.add(seatCode);
                    seat.classList.add('selected');
                }
                updateBookingSummary();
            });
        });

        decrementTicketBtn.addEventListener('click', function() {
            let currentCount = parseInt(ticketCountInput.value);
            if (currentCount > 1) {
                ticketCountInput.value = currentCount - 1;
                adjustSelectedSeats();
                updateBookingSummary();
            }
        });

        incrementTicketBtn.addEventListener('click', function() {
            let currentCount = parseInt(ticketCountInput.value);
            if (currentCount < 10) { // Assuming max 10 tickets
                ticketCountInput.value = currentCount + 1;
                updateBookingSummary();
            }
        });

        function adjustSelectedSeats() {
            const desiredSeats = parseInt(ticketCountInput.value);
            if (selectedSeats.size > desiredSeats) {
                const seatsToRemove = selectedSeats.size - desiredSeats;
                let removedCount = 0;
                const seatsArray = Array.from(selectedSeats); // Convert Set to Array to iterate and remove safely

                // Iterate and remove from the beginning or end, or based on preference
                for (let i = seatsArray.length - 1; i >= 0 && removedCount < seatsToRemove; i--) {
                    const seatCode = seatsArray[i];
                    selectedSeats.delete(seatCode);
                    document.querySelector(`.seat[data-code="${seatCode}"]`).classList.remove('selected');
                    removedCount++;
                }
            }
        }

        function updateBookingSummary() {
            seatCodesInput.value = Array.from(selectedSeats).join(',');
            const currentSelectedCount = selectedSeats.size;
            const desiredSeats = parseInt(ticketCountInput.value);
            const remaining = Math.max(0, desiredSeats - currentSelectedCount);

            remainingSeats.textContent = remaining;
            seatAlert.style.display = remaining === 0 ? 'none' : 'block';

            // Visually update the #ticket-count input if seats were deselected
            ticketCountInput.value = desiredSeats; // Keep the desired count as set by increment/decrement

        }

        bookingForm.addEventListener('submit', function(e) {
            const desiredSeats = parseInt(ticketCountInput.value);
            if (selectedSeats.size === 0) {
                e.preventDefault();
                alert('Silakan pilih setidaknya satu kursi.');
                return;
            }
            if (selectedSeats.size !== desiredSeats) {
                e.preventDefault();
                alert(`Anda harus memilih ${desiredSeats} kursi. Anda baru memilih ${selectedSeats.size}.`);
            }
        });

        // Initialize alert visibility on page load
        document.addEventListener('DOMContentLoaded', () => {
             updateBookingSummary(); // Call this to set initial state of seat alert
        });

    </script>
</body>
</html>