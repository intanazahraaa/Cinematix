<?php
session_start();
require_once 'db_connect.php'; // Pastikan path ini benar

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
    if (count(array_filter($searchResults)) === 1) { // Perbaikan: Gunakan array_filter untuk memastikan elemen tidak kosong
        header("Location: film_detail.php?id=" . $searchResults[0]['id']);
        exit;
    }
}

// Ambil film yang sedang tayang dan semua film untuk ditampilkan di bawah
$nowPlaying = $conn->query("SELECT films.*, genres.name AS genre_name FROM films LEFT JOIN genres ON films.genre_name = genres.name ORDER BY RAND() LIMIT 3")->fetch_all(MYSQLI_ASSOC);
$allFilms = $conn->query("SELECT films.*, genres.name AS genre_name FROM films LEFT JOIN genres ON films.genre_name = genres.name ORDER BY films.title ASC")->fetch_all(MYSQLI_ASSOC);

// --- START: PHP variables for JavaScript and Profile Data ---
$js_user_id = 'null';
$js_username = 'null';
$js_no_hp = 'null'; // Default value
$hasNewAdminMessage = 'false'; // Default to false

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt_user = $conn->prepare("SELECT username, no_hp FROM users WHERE id = ?");
    $stmt_user->bind_param("i", $user_id);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    $user_data = $result_user->fetch_assoc();

    if ($user_data) {
        $_SESSION['username'] = $user_data['username']; // Update session with fresh data
        $_SESSION['no_hp'] = $user_data['no_hp']; // Update session with fresh data

        $js_user_id = json_encode($user_id);
        $js_username = json_encode($user_data['username']);
        $js_no_hp = json_encode($user_data['no_hp'] ?? 'Tidak tersedia');

        // Check for new unread admin messages for this user
        $stmt_unread = $conn->prepare("SELECT COUNT(*) AS unread_count FROM chat_messages WHERE user_id = ? AND sender = 'admin' AND is_read_by_user = 0");
        if ($stmt_unread) { // Pastikan statement berhasil disiapkan
            $stmt_unread->bind_param("i", $user_id);
            $stmt_unread->execute();
            $unread_result = $stmt_unread->get_result();
            $unread_data = $unread_result->fetch_assoc();
            if ($unread_data && $unread_data['unread_count'] > 0) { // Cek jika $unread_data tidak null
                $hasNewAdminMessage = 'true';
            }
            $stmt_unread->close(); // Tutup statement
        } else {
            error_log("Failed to prepare statement for unread messages: " . $conn->error);
            // Anda bisa menambahkan penanganan error lain di sini, misalnya menampilkan pesan ke user
        }
    }
    $stmt_user->close(); // Tutup statement user
}
// --- END: PHP variables for JavaScript ---
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cinematix - Film</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Original CSS for the main page elements (Films, Header, Footer) */
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
            position: relative; /* Penting untuk penempatan absolut chatbot/profil */
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        header h1 {
            margin: 0;
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
            cursor: pointer;
        }
        .film-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            transform: scale(1.05);
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
            background-color: #004d40;
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

        /* --- PERUBAHAN UTAMA CSS UNTUK CHATBOT & PROFILE --- */
        .header-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex; /* Menggunakan flexbox untuk menata elemen di samping */
            align-items: center;
            gap: 15px; /* Jarak antara chatbot dan profil */
            z-index: 100;
        }

        .chatbot-header-toggle {
            font-size: 2em; /* Ukuran ikon chatbot */
            color: #00796b;
            cursor: pointer;
            position: relative; /* Untuk notifikasi dot */
            text-decoration: none; /* Hapus underline jika a tag */
            display: flex; /* Untuk menengahkan ikon */
            align-items: center;
            justify-content: center;
        }

        .chatbot-header-toggle:hover {
            color: #004d40;
        }

        .chatbot-header-toggle .notification-dot {
            position: absolute;
            top: 0px; /* Sesuaikan posisi dot */
            right: 0px; /* Sesuaikan posisi dot */
            width: 15px;
            height: 15px;
            background-color: #ff5722; /* Red dot for notification */
            border-radius: 50%;
            border: 2px solid white;
            animation: pulse 1s infinite alternate;
        }

        @keyframes pulse {
            from { transform: scale(1); opacity: 1; }
            to { transform: scale(1.2); opacity: 0.8; }
        }

        /* User Profile Dropdown CSS (tetap sama atau sedikit disesuaikan) */
        .user-profile {
            /* Hapus posisi absolut jika sudah diatur di .header-controls */
            /* position: relative; */
            display: flex; /* Agar ikon dan dropdown tetap berfungsi */
            align-items: center;
            gap: 10px;
        }

        .profile-icon {
            font-size: 2em;
            color: #00796b;
            cursor: pointer;
            position: relative;
            user-select: none;
        }

        .profile-icon:hover {
            color: #004d40;
        }

        .profile-dropdown {
            position: absolute;
            top: calc(100% + 5px); /* Position below the icon, with a small gap */
            right: 0;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            min-width: 180px;
            padding: 15px;
            display: none;
            flex-direction: column;
            gap: 8px;
            z-index: 1000;
            transform: translateY(10px);
            opacity: 0;
            transition: transform 0.2s ease-out, opacity 0.2s ease-out;
        }

        .profile-dropdown.active {
            display: flex;
            transform: translateY(0);
            opacity: 1;
        }

        .profile-dropdown p {
            margin: 0;
            color: #333;
            font-size: 0.95em;
        }

        .profile-dropdown p strong {
            color: #00796b;
        }

        .profile-dropdown .logout-btn {
            display: block;
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #d32f2f;
            color: white;
            border: none;
            border-radius: 6px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9em;
        }

        .profile-dropdown .logout-btn:hover {
            background-color: #b71c1c;
        }
    </style>
</head>
<body>
<header>
    <h1><a href="index1.php">Cinematix</a></h1>
    <nav>
        <ul>
            <li><a href="index1.php">Home</a></li>
            <li><a href="films.php">Film</a></li>
            <li><a href="cinemas.php">Bioskop</a></li>
            <li><a href="schedules.php">Jadwal Tayang</a></li>
        </ul>
    </nav>
    
    <div class="header-controls">
        <a href="chat_user.php" class="chatbot-header-toggle">
            <i class="fas fa-comment-dots"></i>
            <?php if ($hasNewAdminMessage === 'true'): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-profile">
                <div class="profile-icon" id="profileIcon"><i class="fas fa-user-circle"></i></div>
                <div class="profile-dropdown" id="profileDropdown">
                    <p>Halo, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>!</p>
                    <p>No. HP: <strong><?php echo htmlspecialchars($_SESSION['no_hp'] ?? 'Tidak tersedia'); ?></strong></p>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="user-profile">
                <a href="login.php" class="profile-icon" style="font-size: 1.2em; text-decoration: none; padding-bottom: 0;"><i class="fas fa-sign-in-alt"></i> Login</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<div class="container">
    <div class="search-box">
        <form method="GET" action="films.php">
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
                            <a class="detail-btn" href="film_detail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="booking.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
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
                            <a class="detail-btn" href="film_detail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="booking.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
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
                            <a class="detail-btn" href="film_detail.php?id=<?= $film['id'] ?>">Lihat Detail</a>
                            <a class="booking-btn" href="booking.php?film_id=<?= $film['id'] ?>">Pesan Tiket</a>
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
        <h3>Hubungi Kami</h3>
        <p>Email: info@cinematix.com</p>
        <p>Telepon: +1 234 567 890</p>
        <h3>Ikuti Kami</h3>
        <p><a href="#">Facebook</a> | <a href="#">Twitter</a> | <a href="#">Instagram</a></p>
    </div>
</footer>

<script>
    // Profile Dropdown Elements
    const profileIcon = document.getElementById('profileIcon');
    const profileDropdown = document.getElementById('profileDropdown');

    // Function to toggle profile dropdown
    function toggleProfileDropdown() {
        if (profileDropdown) { // Check if the element exists (only for logged-in users)
            profileDropdown.classList.toggle('active');
        }
    }

    // Event listener for profile icon
    if (profileIcon) { // Ensure profileIcon exists before adding listener
        profileIcon.addEventListener('click', toggleProfileDropdown);
    }

    // Close profile dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (profileDropdown && profileDropdown.classList.contains('active')) {
            const isClickInsideProfile = profileDropdown.contains(event.target) || (profileIcon && profileIcon.contains(event.target));
            // Tambahkan pengecekan untuk tombol chatbot agar tidak menutup dropdown ketika diklik
            const chatbotToggle = document.querySelector('.chatbot-header-toggle');
            const isClickInsideChatbot = chatbotToggle && chatbotToggle.contains(event.target);

            if (!isClickInsideProfile && !isClickInsideChatbot) {
                profileDropdown.classList.remove('active');
            }
        }
    });

    // Tidak ada JavaScript tambahan untuk chatbot di sini karena tombolnya langsung mengarah ke chat_user.php.
    // Indikator notifikasi ditangani sepenuhnya oleh PHP dan CSS.
</script>
</body>
</html>