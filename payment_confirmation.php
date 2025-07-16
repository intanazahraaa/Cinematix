<?php
require_once 'db_connect.php';

// Ambil ID pemesanan dari URL
$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

if (!$booking_id) {
    echo "<script>alert('ID pemesanan tidak valid.'); window.location='index.php';</script>";
    exit;
}

// Ambil detail booking berdasarkan booking_id
$stmt = $conn->prepare("SELECT b.*, f.title, f.price, s.date, s.time FROM bookings b
                        JOIN schedules s ON b.schedule_id = s.id
                        JOIN films f ON b.film_id = f.id
                        WHERE b.id = ?");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    echo "<script>alert('Booking tidak ditemukan.'); window.location='index.php';</script>";
    exit;
}

// Proses konfirmasi pembayaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];

    // Update status pembayaran di database
    $stmt = $conn->prepare("UPDATE bookings SET payment_status = 'Paid', payment_method = ? WHERE id = ?");
    $stmt->execute([$payment_method, $booking_id]);

    echo "<script>alert('Pembayaran berhasil!'); window.location='index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pembayaran</title>
    <link rel="stylesheet" href="assets/css/cinematix.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #333;
            font-size: 28px;
        }

        .form-section {
            margin-bottom: 25px;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .form-section h3 {
            margin-top: 0;
            color: #444;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }

        select, input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        button {
            padding: 12px 25px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: background 0.3s;
            width: 100%;
        }

        button:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>

<div class="form-container">
    <div class="form-header">
        <h2>Konfirmasi Pembayaran</h2>
    </div>

    <form method="POST">
        <div class="form-section">
            <h3>Detail Pemesanan</h3>
            <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><strong>Film:</strong> <?= htmlspecialchars($booking['title']) ?></p>
            <p><strong>Tanggal dan Jam:</strong> <?= date('d F Y', strtotime($booking['date'])) ?> - <?= $booking['time'] ?></p>
            <p><strong>Jumlah Tiket:</strong> <?= $booking['seat_count'] ?> tiket</p>
            <p><strong>Harga per Tiket:</strong> Rp <?= number_format($booking['price'], 0, ',', '.') ?></p>
            <p><strong>Total Harga:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></p>
        </div>

        <div class="form-section">
            <h3>Pilih Metode Pembayaran</h3>
            <label>Pilih Metode Pembayaran:</label>
            <select name="payment_method" required>
                <option value="">-- Pilih Metode Pembayaran --</option>
                <option value="ShopeePay">ShopeePay</option>
                <option value="DANA">DANA</option>
                <option value="GoPay">GoPay</option>
                <option value="QRIS">QRIS</option>
            </select>
        </div>

        <button type="submit">Konfirmasi Pembayaran</button>
    </form>
</div>

</body>
</html>
