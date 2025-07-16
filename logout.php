<?php
session_start();
session_unset(); // Hapus semua session
session_destroy(); // Hancurkan session

// Arahkan ke halaman index setelah logout
header("Location: index.php");
exit();
?>
