<?php
require_once 'db_connect.php';
session_start();

// Ambil data bioskop
$cinemasResult = $conn->query("SELECT * FROM cinemas ORDER BY name ASC");
$cinemas = [];
while ($row = $cinemasResult->fetch_assoc()) {
    $cinemas[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bioskop - Cinematix</title>
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
        h1 {
            text-align: center;
            color: #004d40;
        }
        .cinema-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        .cinema-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        .cinema-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transform: scale(1.05);
        }
        .cinema-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }
        .cinema-body {
            padding: 15px;
            background: #e0f2f1;
            text-align: center;
        }
        .film-title {
            font-size: 1.2em;
            font-weight: bold;
            color: #004d40;
            margin-bottom: 8px;
        }
        .cinema-location {
            font-size: 0.9em;
            color: #555;
            margin-bottom: 12px;
        }
        .detail-btn {
            text-decoration: none;
            background-color: #00796b;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            transition: background-color 0.3s ease;
        }
        .detail-btn:hover {
            background-color: #004d40;
        }
        footer {
            background-color: #e0f2f1;
            text-align: center;
            padding: 40px 20px;
            margin-top: 50px;
        }
        footer h3 {
            color: #00796b;
            margin-bottom: 5px;
        }
        footer p a {
            color: #00796b;
            text-decoration: none;
        }
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
                <li><a class="active" href="cinemas.php">Bioskop</a></li>
                <li><a href="schedules.php">Jadwal Tayang</a></li>
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
    <h1>Daftar Bioskop</h1>

    <div class="cinema-grid">
        <?php foreach ($cinemas as $cinema): ?>
            <div class="cinema-card">
                <img class="cinema-image" src="assets/images/xxi.jpg<?= htmlspecialchars($cinema['image']) ?>" alt="<?= htmlspecialchars($cinema['name']) ?>">
                <div class="cinema-body">
                    <div class="film-title"><?= htmlspecialchars($cinema['name']) ?></div>
                    <div class="cinema-location"><?= htmlspecialchars($cinema['location']) ?></div>
                    <a href="schedules.php?cinema_id=<?= $cinema['id'] ?>" class="detail-btn">Lihat Jadwal</a>
                </div>
            </div>
        <?php endforeach; ?>
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
