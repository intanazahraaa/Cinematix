<?php
require_once 'db_connect.php';

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$booking_id) {
    echo "ID booking tidak valid.";
    exit;
}

$stmt = $conn->prepare("SELECT b.*, f.title, s.date, s.time, c.name AS cinema_name 
    FROM bookings b
    JOIN films f ON b.film_id = f.id
    JOIN schedules s ON b.schedule_id = s.id
    JOIN cinemas c ON b.cinema_id = c.id
    WHERE b.id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    echo "Data booking tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking Berhasil!</title>
   <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fa;
            color: #2c3e50;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 750px;
            margin: 50px auto;
            background-color: #ffffff;
            border: 1px solid #dfe6e9;
            border-radius: 10px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 32px;
            color: #34495e;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .emoji {
            font-size: 40px;
            margin-bottom: 20px;
        }

        .info {
            text-align: left;
            background-color: #ecf0f1;
            border-radius: 10px;
            padding: 25px;
            margin-top: 20px;
        }

        .info p {
            font-size: 16px;
            margin: 10px 0;
        }

        .highlight {
            color: #2980b9;
            font-weight: 500;
        }

        a.button {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 25px;
            font-size: 16px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
            transition: background-color 0.3s;
        }

        a.button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="emoji">🎉🎬🍿</div>
        <h1>Booking Berhasil!</h1>
        <p>Terima kasih telah memesan tiket bioskop bersama kami.</p>

        <div class="info">
            <p><strong>ID Booking:</strong> <span class="highlight"><?= htmlspecialchars($booking['id']) ?></span></p>
            <p><strong>Nama:</strong> <?= htmlspecialchars($booking['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($booking['customer_email']) ?></p>
            <p><strong>Film:</strong> <?= htmlspecialchars($booking['title']) ?></p>
            <p><strong>Bioskop:</strong> <?= htmlspecialchars($booking['cinema_name']) ?></p>
            <p><strong>Studio:</strong> <?= htmlspecialchars($booking['studio']) ?></p>
            <p><strong>Jadwal:</strong> <?= date('d F Y', strtotime($booking['date'])) ?> - <?= $booking['time'] ?></p>
            <p><strong>Jumlah Kursi:</strong> <?= $booking['seat_count'] ?> (<?= htmlspecialchars($booking['seat_code']) ?>)</p>
            <p><strong>Total Bayar:</strong> Rp <?= number_format($booking['total_price'], 0, ',', '.') ?></p>
            <p><strong>Metode Pembayaran:</strong> <?= htmlspecialchars($booking['payment_method']) ?></p>
        </div>

        <a href="index1.php" class="button">Kembali ke Beranda</a>
    </div>
</body>
</html>
