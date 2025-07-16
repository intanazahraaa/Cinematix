<?php
require_once 'config.php';
require_once 'db_connect.php';

if (!isset($_GET['schedule_id'])) exit;

$scheduleId = $_GET['schedule_id'];
$stmt = $conn->prepare("SELECT seat_code FROM seats WHERE schedule_id = ? AND is_available = 1");
$stmt->execute([$scheduleId]);
$availableSeats = $stmt->fetchAll(PDO::FETCH_COLUMN);

$rows = ['A', 'B', 'C', 'D'];
$cols = range(1, 10);

foreach ($rows as $row) {
    foreach ($cols as $col) {
        $seatCode = $row . $col;
        $disabled = (!in_array($seatCode, $availableSeats)) ? "disabled" : "";
        echo "<label class='seat'>";
        echo "<input type='checkbox' name='seats[]' value='$seatCode' $disabled>";
        echo "<span>$seatCode</span>";
        echo "</label>";
    }
}
?>
