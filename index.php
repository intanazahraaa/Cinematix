<?php
session_start();
require_once 'db_connect.php';

// Ambil pencarian dari parameter GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchResults = [];

// Jika ada pencarian, cari film berdasarkan judul
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM films WHERE title LIKE ?");
    $likeSearch = "%$search%";
    $stmt->bind_param("s", $likeSearch);
    $stmt->execute();
    $result = $stmt->get_result();
    $searchResults = $result->fetch_all(MYSQLI_ASSOC);

    // Jika hanya ada satu hasil pencarian, alihkan ke halaman detail film
    if (count($searchResults) === 1) {
        header("Location: filmdetail.php?id=" . $searchResults[0]['id']);
        exit;
    }
}

// Ambil film yang sedang tayang dan semua film untuk ditampilkan di bawah
$nowPlaying = $conn->query("SELECT films.*, genres.name AS genre_name FROM films LEFT JOIN genres ON films.genre_name = genres.name ORDER BY RAND() LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$allFilms = $conn->query("SELECT films.*, genres.name AS genre_name FROM films LEFT JOIN genres ON films.genre_name = genres.name ORDER BY films.title ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cinematix - Film</title>
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
        .search-box {
            text-align: center;
            margin-bottom: 30px;
        }
        .search-box input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .search-box button {
            padding: 10px 15px;
            background-color: #00796b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .film-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .film-card {
            border: 1px solid #ccc;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;  /* Agar kursor berubah menjadi pointer */
        }
        .film-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);  /* Menambahkan shadow lebih tebal */
            transform: scale(1.05);  /* Membesarkan card sedikit */
        }
        .film-poster {
            width: 100%;
            height: 320px;
            object-fit: cover;
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
            transition: background-color 0.3s ease;
        }
        .detail-btn:hover, .booking-btn:hover {
            background-color: #004d40;  /* Ganti warna saat hover */
        }
        .no-films {
            text-align: center;
            font-style: italic;
            color: #888;
            margin: 20px 0;
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
    </style>
</head>
<body>
<header>
    <h1><a href="index.php">Cinematix</a></h1>
    <nav>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="login.php">Login</a></li>
        </ul>
    </nav>
</header>

<div class="container">
    <div class="search-box">
        <form method="GET" action="index.php">
            <input type="text" name="search" placeholder="Cari film..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit">Cari</button>
        </form>
    </div>

    <?php if (!empty($search) && count($searchResults) > 1): ?>
        <h2>Hasil Pencarian untuk "<?= htmlspecialchars($search) ?>"</h2>
        <div class="film-grid">
            <?php foreach ($searchResults as $film): ?>
                <div class="film-card">
                    <img src="assets/images/<?= htmlspecialchars($film['poster']) ?>" class="film-poster" alt="<?= htmlspecialchars($film['title']) ?>">
                    <div class="film-body">
                        <div class="film-title"><?= htmlspecialchars($film['title']) ?></div>
                        <div class="btn-group">
                            <a class="detail-btn" href="filmdetail.php<?= htmlspecialchars($film['genre_name']) ?>                         <a class="booking-btn" href="login.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($search) && count($searchResults) === 0): ?>
        <p class="no-films">Film dengan judul "<?= htmlspecialchars($search) ?>" tidak ditemukan.</p>
    <?php endif; ?>

    <?php if (empty($search)): ?>
        <h2>Film yang Sedang Tayang</h2>
        <div class="film-grid">
            <?php foreach ($nowPlaying as $film): ?>
                <div class="film-card">
                    <img src="assets/images/<?= htmlspecialchars($film['poster']) ?>" class="film-poster" alt="<?= htmlspecialchars($film['title']) ?>">
                    <div class="film-body">
                        <div class="film-title"><?= htmlspecialchars($film['title']) ?></div>
                        <div class="btn-group">
                            <a class="detail-btn" href="filmdetail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="login.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <h2 style="margin-top:40px;">Semua Film</h2>
        <div class="film-grid">
            <?php foreach ($allFilms as $film): ?>
                <div class="film-card">
                    <img src="assets/images/<?= htmlspecialchars($film['poster']) ?>" class="film-poster" alt="<?= htmlspecialchars($film['title']) ?>">
                    <div class="film-body">
                        <div class="film-title"><?= htmlspecialchars($film['title']) ?></div>
                        <div class="btn-group">
                            <a class="detail-btn" href="filmdetail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="login.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
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
