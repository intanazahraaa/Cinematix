<?php
session_start(); // Pastikan session_start() ada di bagian paling atas file

require_once 'db_connect.php'; // Pastikan path ini benar

$error = ''; // Inisialisasi variabel error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']); // Gunakan trim untuk menghapus spasi di awal/akhir
    $no_hp = trim($_POST['no_hp']); // Gunakan trim
    $password_input = $_POST['password']; // Password mentah dari input

    // Validasi panjang no_hp max 13 digit
    if (strlen($no_hp) > 13) {
        $error = "Nomor HP maksimal 13 digit.";
    } elseif (empty($username) || empty($no_hp) || empty($password_input)) {
        $error = "Semua kolom wajib diisi.";
    } else {
        // Hashing password
        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

        try {
            // Cek apakah username sudah dipakai
            $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);

            if ($check->fetch()) {
                $error = "Username sudah terdaftar. Silakan pilih username lain.";
            } else {
                // Insert data user baru ke database
                // Perhatikan urutan kolom: username, no_hp, password, role
                $stmt = $conn->prepare("INSERT INTO users (username, no_hp, password, role) VALUES (?, ?, ?, 'user')");
                // 'sss' berarti 3 string: username, no_hp, password
                $stmt->bind_param("sss", $username, $no_hp, $hashed_password);

                if ($stmt->execute()) {
                    // Pendaftaran berhasil.
                    // SEKARANG, kita ambil ID pengguna yang baru terdaftar
                    // dan langsung simpan data pengguna (ID, username, no_hp) ke sesi.
                    $new_user_id = $stmt->insert_id;

                    // Query untuk mendapatkan data pengguna yang baru terdaftar
                    // (ini memastikan kita punya data yang konsisten dari database)
                    $getUserData = $conn->prepare("SELECT id, username, no_hp FROM users WHERE id = ?");
                    $getUserData->bind_param("i", $new_user_id);
                    $getUserData->execute();
                    $result = $getUserData->get_result();
                    $user = $result->fetch_assoc();

                    if ($user) {
                        // Simpan data pengguna ke dalam sesi
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['no_hp'] = $user['no_hp']; // <-- no_hp disimpan di sini!

                        // Arahkan pengguna ke halaman utama (index1.php)
                        header("Location: index1.php");
                        exit();
                    } else {
                        // Seharusnya tidak terjadi jika INSERT berhasil
                        $error = "Terjadi kesalahan saat mengambil data pengguna setelah pendaftaran.";
                    }
                } else {
                    $error = "Pendaftaran gagal. Error: " . $stmt->error;
                }
                $stmt->close(); // Tutup statement setelah digunakan
            }
            $check->close(); // Tutup statement setelah digunakan
        } catch (mysqli_sql_exception $e) {
            // Tangani error database lainnya, seperti koneksi
            $error = "Terjadi kesalahan database: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Cinematix</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-family: 'Poppins', sans-serif; /* Menggunakan Poppins untuk konsistensi */
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .login-container {
            max-width: 400px;
            margin: 80px auto;
            background-color: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s ease;
            flex-grow: 1; /* Agar container bisa mengisi ruang */
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #00796b; /* Warna yang lebih konsisten dengan Cinematix */
            font-size: 28px; /* Ukuran font lebih besar */
            font-weight: 700; /* Lebih tebal */
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600; /* Lebih tebal dari 500 */
            margin-bottom: 8px; /* Sedikit lebih banyak ruang */
            color: #333;
        }

        .form-group input {
            width: calc(100% - 20px); /* Sesuaikan padding */
            padding: 12px 10px; /* Padding lebih besar */
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #00796b; /* Warna fokus */
            outline: none;
        }

        .btn {
            width: 100%;
            background-color: #00796b; /* Warna utama Cinematix */
            color: white;
            padding: 12px;
            border-radius: 5px;
            font-size: 1.1em; /* Ukuran font tombol lebih besar */
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
            font-weight: 600;
        }

        .btn:hover {
            background-color: #004d40; /* Warna hover lebih gelap */
            transform: translateY(-2px); /* Efek sedikit terangkat */
        }

        .error-message {
            color: #d32f2f; /* Warna merah yang lebih standar */
            background-color: #ffebee; /* Latar belakang merah muda */
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
            border: 1px solid #d32f2f; /* Border untuk kejelasan */
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9em;
            color: #555;
        }

        .login-link a {
            color: #00796b;
            text-decoration: none;
            font-weight: 600;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        footer {
            text-align: center;
            padding: 20px 0;
            color: #777;
            margin-top: auto; /* Mendorong footer ke bawah */
            background-color: #e0f2f1; /* Sesuaikan dengan warna header/footer utama */
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <h2>Daftar Akun Baru</h2>
    <?php if (isset($error) && !empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="no_hp">Nomor HP</label>
            <input type="text" name="no_hp" id="no_hp" maxlength="13" value="<?= htmlspecialchars($_POST['no_hp'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
        </div>

        <button type="submit" class="btn">Daftar</button>
    </form>
    <div class="login-link">
        Sudah punya akun? <a href="login.php">Login di sini</a>
    </div>
</div>

<footer>
    &copy; <?= date('Y') ?> Cinematix. All rights reserved.
</footer>

</body>
</html>