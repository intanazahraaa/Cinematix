<?php
// film_details.php
include 'db_connect.php';
session_start();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $query = mysqli_query($conn, "
        SELECT f.*, g.name AS genre_name_from_genres 
        FROM films f
        LEFT JOIN genres g ON f.genre_id = g.id
        WHERE f.id = $id
    ");
    $film = mysqli_fetch_assoc($query);

    if (!$film) {
        echo "Film tidak ditemukan.";
        exit;
    }
} else {
    echo "ID film tidak ditemukan.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Film - <?= htmlspecialchars($film['title']); ?></title>
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
            font-size: 2em;
        }
        nav ul {
            list-style: none;
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0 0;
            padding: 0;
        }
        nav ul li {
            list-style: none;
        }
        nav ul li a {
            color: #00796b;
            text-decoration: none;
            font-weight: bold;
        }
        nav ul li a:hover {
            color: #004d40;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
        }
        .film-details-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            background-color: #f1f8f6;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
        }
        .film-left {
            flex: 1;
            text-align: center;
        }
        .film-left img {
            width: 250px;
            height: 400px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .film-left h4 {
            font-size: 1.8em;
            color: #00796b;
            margin-top: 0;
        }
        .film-right {
            flex: 2;
            font-size: 1.1em;
            line-height: 1.7;
        }
        .film-right p {
            margin-bottom: 15px;
        }
        .booking-btn {
            display: inline-block;
            padding: 12px 20px;
            margin-top: 15px;
            background-color: #00796b;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            text-decoration: none;
            transition: 0.3s ease;
        }
        .booking-btn:hover {
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
        }
        .social-icons {
            margin-top: 15px;
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
        @media screen and (max-width: 768px) {
            .film-details-wrapper {
                flex-direction: column;
                align-items: center;
            }
            .film-left img {
                width: 200px;
                height: 320px;
            }
            .film-right {
                text-align: center;
            }
            .booking-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header>
    <h1><a href="index.php">Cinematix</a></h1>
    <nav>
        <ul>
            <li><a href="index1.php">Beranda</a></li>
            <li><a href="films.php">Berdasarkan Genre</a></li>
            <li><a href="schedules.php">Jadwal Tayang</a></li>
            <?php if (isset($_SESSION['user_id'])): ?>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<div class="container">
    <div class="film-details-wrapper">
        <div class="film-left">
            <img src="assets/images/<?= htmlspecialchars($film['poster']); ?>" alt="<?= htmlspecialchars($film['title']); ?>">
            <h4><?= htmlspecialchars($film['title']); ?></h4>
        </div>
        <div class="film-right">
            <p><strong>Genre:</strong> <?= htmlspecialchars($film['genre_name_from_genres']); ?></p>
            <p><strong>Durasi:</strong> <?= htmlspecialchars($film['duration']); ?> menit</p>
            <p><strong>Sinopsis:</strong><br><?= nl2br(htmlspecialchars($film['synopsis'])); ?></p>
            <a class="booking-btn" href="booking.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
        </div>
    </div>
</div>

<footer>
    <h3>Cinematix</h3>
    <p>Jln. Khatib Sulaiman No.85, Padang</p>
    <p>Email: support@cinematix.com | Telp: 0800-123-456</p>
    <div class="social-icons">
        <a href="https://facebook.com/cinematix" target="_blank">Facebook</a>
        <a href="https://instagram.com/cinematix" target="_blank">Instagram</a>
        <a href="https://twitter.com/cinematix" target="_blank">Twitter</a>
    </div>
</footer>

</body>
</html>
