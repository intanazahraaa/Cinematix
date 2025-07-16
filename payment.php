<?php
require_once 'config.php';
require_once 'db_connect.php';

// Cek apakah ada booking_id
if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$bookingId = $_GET['booking_id'];

// Ambil detail booking
$stmt = $conn->prepare("
    SELECT b.*, f.title AS film_title, f.price, s.showtime, c.name AS cinema_name, c.location 
    FROM bookings b
    JOIN schedules s ON b.schedule_id = s.id
    JOIN films f ON s.film_id = f.id
    JOIN cinemas c ON s.cinema_id = c.id
    WHERE b.id = ?
");
$stmt->execute([$bookingId]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pembayaran | <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .payment-box {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 40px auto;
        }

        .payment-box h2 {
            margin-bottom: 20px;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-group strong {
            display: inline-block;
            width: 150px;
        }

        .payment-methods {
            margin: 20px 0;
        }

        .payment-methods label {
            display: block;
            margin-bottom: 10px;
        }

        .btn-pay {
            background: #27ae60;
            color: white;
            padding: 12px 20px;
            border: none;
            width: 100%;
            border-radius: 4px;
            font-size: 1em;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php"><?= SITE_NAME ?></a></h1>
        </div>
    </header>

    <main>
        <div class="payment-box">
            <h2>Detail Pembayaran</h2>

            <div class="info-group">
                <strong>Nama:</strong> <?= htmlspecialchars($booking['customer_name']) ?>
            </div>
            <div class="info-group">
                <strong>Email:</strong> <?= htmlspecialchars($booking['customer_email']) ?>
            </div>
            <div class="info-group">
                <strong>Film:</strong> <?= htmlspecialchars($booking['film_title']) ?>
            </div>
            <div class="info-group">
                <strong>Bioskop:</strong> <?= htmlspecialchars($booking['cinema_name']) ?> - <?= htmlspecialchars($booking['location']) ?>
            </div>
            <div class="info-group">
                <strong>Jadwal:</strong> <?= date('l, d F Y - H:i', strtotime($booking['showtime'])) ?>
            </div>
            <div class="info-group">
                <strong>Jumlah Kursi:</strong> <?= $booking['seat_count'] ?>
            </div>
            <div class="info-group">
                <strong>Studio:</strong> <?= $booking['studio_id'] ?>
            </div>
            <div class="info-group">
                <strong>Total Bayar:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.') ?>
            </div>

            <form action="payment_success.php" method="POST">
                <input type="hidden" name="booking_id" value="<?= $booking['id'] ?>">

                <div class="payment-methods">
                    <label><input type="radio" name="payment_method" value="DANA" required> DANA</label>
                    <label><input type="radio" name="payment_method" value="GoPay"> GoPay</label>
                    <label><input type="radio" name="payment_method" value="ShopeePay"> ShopeePay</label>
                    <label><input type="radio" name="payment_method" value="QRIS"> QRIS</label>
                </div>

                <button type="submit" class="btn-pay">Bayar Sekarang</button>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
