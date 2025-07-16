<?php
// FILE: admin/studios.php
require_once '../db_connect.php';
session_start();
if (!isset($_SESSION['user_id'])) header("Location: ../login.php");

if (isset($_POST['add_studio'])) {
    $stmt = $conn->prepare("INSERT INTO studios (name, capacity) VALUES (?, ?)");
    $stmt->execute([$_POST['name'], $_POST['capacity']]);
    header("Location: studios.php");
}
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM studios WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
}
$studios = $conn->query("SELECT * FROM studios")->fetchAll();
?>
<h2>Daftar Studio</h2>
<form method="POST">
  <input name="name" placeholder="Nama Studio" required>
  <input name="capacity" type="number" placeholder="Kapasitas">
  <button name="add_studio">Tambah</button>
</form>
<table>
<tr><th>Nama</th><th>Kapasitas</th><th>Aksi</th></tr>
<?php foreach ($studios as $s): ?>
<tr><td><?= $s['name'] ?></td><td><?= $s['capacity'] ?></td>
<td><a href="?delete=<?= $s['id'] ?>">Hapus</a></td></tr>
<?php endforeach; ?></table>
