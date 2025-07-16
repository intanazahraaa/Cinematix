<?php
session_start();
require_once 'db_connect.php';

// Ambil daftar genre yang tersedia dari tabel genres
$genres_result = $conn->query("SELECT * FROM genres");
$genres = [];
if ($genres_result && $genres_result->num_rows > 0) {
    while ($row = $genres_result->fetch_assoc()) {
        $genres[] = $row;
    }
}

$genreSearch = isset($_GET['genre']) ? (int)$_GET['genre'] : '';
$films = [];

if ($genreSearch) {
    // Menampilkan film berdasarkan genre_id
    $stmt = $conn->prepare("SELECT * FROM films WHERE genre_id = ?");
    $stmt->bind_param("i", $genreSearch);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    // Menampilkan semua film
    $result = $conn->query("SELECT * FROM films");
}

if ($result && $result->num_rows > 0) {
    while ($film = $result->fetch_assoc()) {
        $films[] = $film;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cinematix - Film Berdasarkan Genre</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background-color: #ffffff;
            color: #333;
        }
        header {
            background-color: #e0f2f1;
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        header h1 a {
            color: #00796b;
            text-decoration: none;
        }
        nav ul {
            list-style: none;
            padding: 0;
            margin: 15px 0 0;
            display: flex;
            justify-content: center;
            gap: 30px;
        }
        nav ul li a {
            position: relative;
            text-decoration: none;
            color: #00796b;
            font-weight: bold;
            padding-bottom: 5px;
            transition: color 0.3s ease;
        }
        nav ul li a::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            left: 0;
            bottom: 0;
            background-color: #00796b;
            transition: width 0.3s ease;
        }
        nav ul li a:hover::after,
        nav ul li a.active::after {
            width: 100%;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }
        .genre-filter {
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .genre-filter select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1em;
        }
        .genre-filter button {
            padding: 10px 15px;
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .film-slider {
            display: flex;
            overflow-x: scroll;
            gap: 20px;
            margin-bottom: 40px;
        }
        .film-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .film-card:hover .film-poster {
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .film-card {
            flex: 0 0 auto;
            width: 240px;
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            transition: box-shadow 0.3s;
            cursor: pointer;
        }
        .film-poster {
            width: 100%;
            height: 320px;
            object-fit: cover;
            transition: opacity 0.3s ease;
        }
        .film-body {
            padding: 15px;
            background: #e0f2f1;
        }
        .film-title {
            font-size: 1.1em;
            font-weight: bold;
            text-align: center;
            color: #004d40;
        }
        .btn-group {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .detail-btn, .booking-btn {
            text-decoration: none;
            background-color: #00796b;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
        }
        footer {
            background-color: #e0f2f1;
            text-align: center;
            padding: 40px 20px;
            margin-top: 50px;
        }
        footer h3 {
            color: #00796b;
        }
        .social-icons a {
            margin: 0 10px;
            color: #00796b;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }
        .social-icons a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
    <h1><a href="index.php">Cinematix</a></h1>
    <nav>
        <ul>
            <li><a href="index1.php">Home</a></li>
            <li><a href="films.php" class="active">Film</a></li>
            <li><a href="cinemas.php">Bioskop</a></li>
            <li><a href="schedules.php">Jadwal Tayang</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div class="genre-filter">
    <form method="GET" action="films.php">
        <label for="genre">Cari Genre Film: </label>
        <select name="genre" id="genre">
            <option value="">-- Semua Genre --</option>
            <?php foreach ($genres as $genre): ?>
                <option value="<?= $genre['id'] ?>" <?= ($genre['id'] == $genreSearch) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($genre['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Cari</button>
    </form>
</div>

<h2 style="text-align:center;">Semua Film</h2>

<div class="container">
    <div class="film-slider">
        <?php if ($films): ?>
            <?php foreach ($films as $film): ?>
                <div class="film-card">
                    <img src="assets/images/<?= htmlspecialchars($film['poster']) ?>" class="film-poster" alt="<?= htmlspecialchars($film['title']) ?>">
                    <div class="film-body">
                        <div class="film-title"><?= htmlspecialchars($film['title']) ?></div>
                        <div class="btn-group">
                            <a class="detail-btn" href="film_detail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="booking.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">Tidak ada film yang ditemukan untuk genre ini.</p>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <h3>Lokasi</h3>
        <p>Jln.Khatib Sulaiman No.85, Padang</p>
        <h3>Contact Us</h3>
        <p>Email: info@cinematix.com</p>
        <p>Phone: +1 234 567 890</p>
        <h3>Follow Us</h3>
        <p><a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </div>
</footer>
</body>
</html>
