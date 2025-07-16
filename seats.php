<?php
include '../db_connect.php'; // Pastikan path ini benar

// --- DEBUGGING: Nyalakan pelaporan error untuk melihat masalah PHP ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- Akhir DEBUGGING ---

// --- Otomatiskan Reset Status Kursi Setelah Jadwal Tayang Berlalu ---
// Dapatkan waktu dan tanggal saat ini (gunakan server time)
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
error_log("DEBUG: --- Memulai Otomatisasi Reset Kursi ---");
error_log("DEBUG: Waktu server saat ini: $current_date $current_time"); 

// Query untuk mengidentifikasi schedule_id yang sudah berlalu
// Jadwal yang tanggalnya sudah lewat ATAU tanggalnya hari ini tapi jamnya sudah lewat
$stmt_expired_schedules = $conn->prepare("
    SELECT id FROM schedules 
    WHERE date < CURDATE() 
       OR (date = CURDATE() AND time < CURTIME())
");

if (!$stmt_expired_schedules) {
    error_log("ERROR: Gagal mempersiapkan query expired schedules: " . $conn->error);
} else {
    $stmt_expired_schedules->execute();
    $result_expired_schedules = $stmt_expired_schedules->get_result();

    $expired_schedule_ids = [];
    while ($row = $result_expired_schedules->fetch_assoc()) {
        $expired_schedule_ids[] = $row['id'];
    }
    $stmt_expired_schedules->close();

    error_log("DEBUG: Ditemukan " . count($expired_schedule_ids) . " ID jadwal yang sudah berlalu.");

    // Jika ada jadwal yang sudah berlalu, perbarui status kursi terkait
    if (!empty($expired_schedule_ids)) {
        // Ubah status kursi dari 'tidak tersedia' (0) menjadi 'tersedia' (1)
        // untuk jadwal yang sudah berlalu.
        $placeholders = implode(',', array_fill(0, count($expired_schedule_ids), '?'));
        $types = str_repeat('i', count($expired_schedule_ids)); // Semua ID adalah integer

        $stmt_update_seats = $conn->prepare("UPDATE seats SET is_available = 1 WHERE schedule_id IN ($placeholders) AND is_available = 0");
        
        if (!$stmt_update_seats) {
            error_log("ERROR: Gagal mempersiapkan query update seats: " . $conn->error);
        } else {
            $stmt_update_seats->bind_param($types, ...$expired_schedule_ids);
            if ($stmt_update_seats->execute()) {
                error_log("DEBUG: Berhasil memperbarui " . $stmt_update_seats->affected_rows . " kursi menjadi tersedia.");
            } else {
                error_log("ERROR: Gagal mengeksekusi update seats query: " . $stmt_update_seats->error);
            }
            $stmt_update_seats->close();
        }
    } else {
        error_log("DEBUG: Tidak ada jadwal yang berlalu, tidak ada kursi yang diupdate secara otomatis.");
    }
}
error_log("DEBUG: --- Akhir Otomatisasi Reset Kursi ---");

// --- Fetch daftar film dan jadwal untuk dropdown (Penting: Lakukan di sini agar tersedia untuk semua form) ---
// Pastikan pointer di-reset dengan data_seek(0) jika digunakan di lebih dari satu loop
$films_list = $conn->query("SELECT id, title FROM films ORDER BY title ASC");

// Hanya ambil jadwal yang belum berlalu untuk dropdown form create/edit
$schedules_list = $conn->query("SELECT s.id, f.title as film_title, s.date, s.time, s.studio 
                                 FROM schedules s 
                                 JOIN films f ON s.film_id = f.id
                                 WHERE s.date >= CURDATE() OR (s.date = CURDATE() AND s.time >= CURTIME()) 
                                 ORDER BY s.date ASC, s.time ASC");


// --- Handle Delete (Menggunakan Prepared Statement) ---
if (isset($_GET['delete'])) {
    $seat_id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM seats WHERE id = ?");
    $stmt->bind_param("i", $seat_id);
    if ($stmt->execute()) {
        echo "<script>alert('Seat deleted successfully'); window.location = 'seats.php';</script>";
    } else {
        echo "<script>alert('Failed to delete seat: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// --- Handle Edit (Menggunakan Prepared Statement) ---
$seat_data = null; // Inisialisasi null
if (isset($_GET['edit'])) {
    $seat_id = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM seats WHERE id = ?");
    $stmt->bind_param("i", $seat_id);
    $stmt->execute();
    $seat_result = $stmt->get_result();
    $seat_data = $seat_result->fetch_assoc();
    $stmt->close();
    
    // Process form submission for editing seat
    if (isset($_POST['update_seat'])) {
        $film_id = $_POST['film_id'];
        $schedule_id = $_POST['schedule_id'];
        $seat_code = $_POST['seat_code'];
        $is_available = isset($_POST['is_available']) ? 1 : 0; // Ambil status dari form jika ada
        
        // Update di tabel seats
        $stmt_update_seat = $conn->prepare("UPDATE seats SET film_id = ?, schedule_id = ?, seat_code = ?, is_available = ? WHERE id = ?");
        $stmt_update_seat->bind_param("iisii", $film_id, $schedule_id, $seat_code, $is_available, $seat_id);
        
        if ($stmt_update_seat->execute()) {
            echo "<script>alert('Seat updated successfully'); window.location = 'seats.php';</script>";
        } else {
            echo "<script>alert('Failed to update seat: " . $stmt_update_seat->error . "');</script>";
        }
        $stmt_update_seat->close();
    }
}

// --- Handle Create (for adding new seats) (Menggunakan Prepared Statement) ---
if (isset($_POST['create_seat'])) {
    $film_id = $_POST['film_id'];
    $schedule_id = $_POST['schedule_id'];
    $seat_code = $_POST['seat_code'];
    // Default is_available to 1 (tersedia) saat membuat kursi baru
    $is_available = 1; 

    $stmt = $conn->prepare("INSERT INTO seats (film_id, schedule_id, seat_code, is_available) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iisi", $film_id, $schedule_id, $seat_code, $is_available);
    if ($stmt->execute()) {
        echo "<script>alert('Seat created successfully'); window.location = 'seats.php';</script>";
    } else {
        echo "<script>alert('Failed to create seat: " . $stmt->error . "');</script>";
    }
    $stmt->close();
}

// --- Fetch all seats (termasuk yang tidak tersedia) ---
// Kita akan tampilkan semua kursi di halaman admin ini,
// dan gunakan kolom 'is_available' untuk menunjukkan statusnya.
$seats_query = "SELECT 
                    s.id, 
                    s.seat_code, 
                    s.is_available, 
                    f.title as film_title, 
                    sch.studio, 
                    sch.date, 
                    sch.time 
                FROM seats s
                JOIN films f ON s.film_id = f.id
                JOIN schedules sch ON s.schedule_id = sch.id
                ORDER BY s.id DESC"; 
$seats_result = mysqli_query($conn, $seats_query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kursi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f7f6;
            color: #333;
        }

        h1, h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 25px;
        }

        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            background-color: #fff;
            border-radius: 8px;
            overflow: hidden; 
        }

        table th, table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        table th {
            background-color: #00796b; 
            color: white;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        table tbody tr:nth-child(even) {
            background-color: #f8f8f8;
        }

        table tbody tr:hover {
            background-color: #e0f2f1; 
        }

        .btn {
            padding: 8px 12px;
            margin: 4px;
            text-decoration: none;
            color: white;
            border-radius: 5px;
            transition: background-color 0.3s ease, transform 0.2s ease;
            display: inline-block;
            font-size: 0.85em;
        }

        .btn-success {
            background-color: #28a745; 
        }

        .btn-warning {
            background-color: #ffc107; 
            color: #333; 
        }

        .btn-danger {
            background-color: #dc3545; 
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #4a4a4a;
        }

        .form-group input[type="text"],
        .form-group select {
            width: calc(100% - 20px); 
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group input[type="checkbox"] {
            margin-right: 8px;
        }

        form {
            width: 50%;
            max-width: 600px;
            margin: 30px auto;
            padding: 25px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .form-actions {
            text-align: center;
            margin-top: 25px;
        }
        .form-actions button {
            padding: 12px 25px;
            background-color: #007bff; 
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-actions button:hover {
            background-color: #0056b3;
        }

        .status-available {
            color: #28a745; 
            font-weight: bold;
        }

        .status-unavailable {
            color: #dc3545; 
            font-weight: bold;
        }
    </style>
</head>
<body>

<h1>Manajemen Kursi Bioskop</h1>

<?php if (isset($_GET['edit']) && $seat_data): ?>
    <h2>Edit Kursi (ID: <?= $seat_data['id']; ?>)</h2>
    <form method="POST">
        <div class="form-group">
            <label for="film_id">Film:</label>
            <select name="film_id" required>
                <option value="">-- Pilih Film --</option>
                <?php $films_list->data_seek(0); // Reset pointer ?>
                <?php while($film = $films_list->fetch_assoc()): ?>
                    <option value="<?= $film['id'] ?>" <?= $seat_data['film_id'] == $film['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($film['title']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="schedule_id">Jadwal (Film - Tanggal - Waktu - Studio):</label>
            <select name="schedule_id" required>
                <option value="">-- Pilih Jadwal --</option>
                <?php $schedules_list->data_seek(0); // Reset pointer ?>
                <?php while($schedule = $schedules_list->fetch_assoc()): ?>
                    <option value="<?= $schedule['id'] ?>" <?= $seat_data['schedule_id'] == $schedule['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($schedule['film_title']) ?> - <?= date('d M Y', strtotime($schedule['date'])) ?> - <?= $schedule['time'] ?> (<?= htmlspecialchars($schedule['studio']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="seat_code">Kode Kursi:</label>
            <input type="text" name="seat_code" value="<?= htmlspecialchars($seat_data['seat_code']); ?>" required>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_available" <?= $seat_data['is_available'] ? 'checked' : '' ?>>
                Tersedia
            </label>
        </div>
        <div class="form-actions">
            <button type="submit" name="update_seat">Update Kursi</button>
        </div>
    </form>
<?php endif; ?>

<h2>Tambah Kursi Baru</h2>
<form method="POST">
    <div class="form-group">
        <label for="film_id_create">Film:</label>
        <select name="film_id" id="film_id_create" required>
            <option value="">-- Pilih Film --</option>
            <?php $films_list->data_seek(0); // Reset pointer ?>
            <?php while($film = $films_list->fetch_assoc()): ?>
                <option value="<?= $film['id'] ?>"><?= htmlspecialchars($film['title']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="schedule_id_create">Jadwal (Film - Tanggal - Waktu - Studio):</label>
        <select name="schedule_id" id="schedule_id_create" required>
            <option value="">-- Pilih Jadwal --</option>
            <?php $schedules_list->data_seek(0); // Reset pointer ?>
            <?php while($schedule = $schedules_list->fetch_assoc()): ?>
                <option value="<?= $schedule['id'] ?>">
                    <?= htmlspecialchars($schedule['film_title']) ?> - <?= date('d M Y', strtotime($schedule['date'])) ?> - <?= $schedule['time'] ?> (<?= htmlspecialchars($schedule['studio']) ?>)
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="seat_code_create">Kode Kursi (Contoh: A1, B10):</label>
        <input type="text" name="seat_code" id="seat_code_create" required>
    </div>
    <div class="form-actions">
        <button type="submit" name="create_seat">Buat Kursi</button>
    </div>
</form>

<h2>Daftar Kursi</h2>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Film</th>
            <th>Studio</th>
            <th>Tanggal & Waktu</th>
            <th>Kode Kursi</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($seats_result->num_rows > 0): ?>
            <?php while($seat = mysqli_fetch_assoc($seats_result)): ?>
            <tr>
                <td><?= $seat['id']; ?></td>
                <td><?= htmlspecialchars($seat['film_title']); ?></td>
                <td><?= htmlspecialchars($seat['studio']); ?></td>
                <td><?= date('d M Y', strtotime($seat['date'])) . ' ' . $seat['time']; ?></td>
                <td><?= htmlspecialchars($seat['seat_code']); ?></td>
                <td>
                    <?php if ($seat['is_available']): ?>
                        <span class="status-available">Tersedia</span>
                    <?php else: ?>
                        <span class="status-unavailable">Tidak Tersedia</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="seats.php?edit=<?= $seat['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="seats.php?delete=<?= $seat['id']; ?>" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kursi ini?')">Hapus</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="text-align: center;">Tidak ada data kursi ditemukan.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>