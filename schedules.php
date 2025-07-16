<?php
require_once 'db_connect.php';
session_start();

// --- Bagian Otomatisasi Reset Status Kursi (tetap disarankan menggunakan Cron Job) ---
// Jika Anda sebelumnya menaruh kode update_seats_query di sini,
// disarankan untuk memindahkannya ke file terpisah yang dijalankan oleh Cron Job
// agar otomatisasi tidak membebani setiap kunjungan halaman.
// Lihat contoh file 'otomatis_reset_kursi.php' yang saya berikan sebelumnya.
// --- Akhir Bagian Otomatisasi Reset Status Kursi ---

// Pengecekan koneksi database (penting untuk debugging)
if ($conn->connect_error) {
    error_log("ERROR: Koneksi database gagal di schedules.php: " . $conn->connect_error);
    die("<p style='color: red; text-align: center;'>Maaf, terjadi masalah koneksi database.</p>");
}

// Query untuk mengambil data jadwal film TANPA FILTER CURDATE/CURTIME
// Ini akan menampilkan SEMUA jadwal yang ada di database, termasuk yang sudah berlalu
$stmt = $conn->query("
    SELECT s.id AS schedule_id, f.title, f.price, s.date, s.time
    FROM schedules s
    JOIN films f ON s.film_id = f.id
    ORDER BY s.date ASC, s.time ASC
");

$schedules = [];
if ($stmt) {
    while ($row = $stmt->fetch_assoc()) {
        $schedules[] = $row;
    }
} else {
    error_log("ERROR: Gagal menjalankan query SELECT jadwal di schedules.php: " . $conn->error);
    // Tampilkan pesan error di halaman jika perlu
    // echo "<p style='color: red; text-align: center;'>Terjadi kesalahan saat mengambil data jadwal film.</p>";
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Jadwal Film - Cinematix</title>
    <style>
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background-color: #ffffff; color: #333; }
        header { background-color: #e0f2f1; padding: 20px 0; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        header h1 a { color: #00796b; text-decoration: none; }
        nav ul { list-style: none; padding: 0; margin: 15px 0 0; display: flex; justify-content: center; gap: 30px; }
        nav ul li a { position: relative; text-decoration: none; color: #00796b; font-weight: bold; padding-bottom: 5px; transition: color 0.3s ease; }
        nav ul li a::after { content: ''; position: absolute; width: 0; height: 2px; left: 0; bottom: 0; background-color: #00796b; transition: width 0.3s ease; }
        nav ul li a:hover::after, nav ul li a.active::after { width: 100%; }
        .container { max-width: 1200px; margin: auto; padding: 20px; }
        h2 { text-align: center; color: #004d40; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; background-color: #f9f9f9; border-radius: 8px; overflow: hidden; }
        th, td { padding: 14px 16px; text-align: center; border-bottom: 1px solid #ccc; }
        th { background-color: #00796b; color: white; }
        tr:nth-child(even) { background-color: #e0f2f1; }
        tr:hover { background-color: #c8e6c9; }
        .table-action-btn { display: inline-block; background-color: #00796b; color: white; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-weight: bold; transition: background-color 0.3s ease; }
        .table-action-btn:hover { background-color: #004d40; }
        footer { background-color: #e0f2f1; text-align: center; padding: 40px 20px; margin-top: 50px; }
        footer h3 { color: #00796b; margin-bottom: 10px; }
        footer p a { color: #00796b; text-decoration: none; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><a href="index.php">Cinematix</a></h1>
            <nav>
                <ul>
                    <li><a href="index1.php">Home</a></li>
                    <li><a href="films.php">Film</a></li>
                    <li><a href="cinemas.php">Bioskop</a></li>
                    <li><a href="schedules.php" class="active">Jadwal Tayang</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <h2>Jadwal Film</h2>
        <table>
            <thead>
                <tr>
                    <th>Judul Film</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Harga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schedules)): ?>
                    <tr><td colspan="5">Tidak ada jadwal tersedia.</td></tr>
                <?php else: ?>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['title']) ?></td>
                            <td><?= htmlspecialchars($s['date']) ?></td>
                            <td><?= htmlspecialchars($s['time']) ?></td>
                            <td>Rp <?= number_format($s['price'], 0, ',', '.') ?></td>
                            <td>
                                <a href="booking.php?id=<?= $s['schedule_id'] ?>" class="table-action-btn">Pesan Tiket</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <div class="container">
            <h3>Lokasi</h3>
            <p>Jln. Khatib Sulaiman No.85, Padang</p>
            <h3>Contact Us</h3>
            <p>Email: info@cinematix.com</p>
            <p>Phone: +1 234 567 890</p>
            <h3>Follow Us</h3>
            <p><a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
        </div>
    </footer>
</body>
</html>