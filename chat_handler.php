<?php
// cinematix/chat_handler.php (VERSI CERDAS UNTUK ADMIN DAN USER)
session_start();
header('Content-Type: application/json');

// AKTIFKAN INI UNTUK DEBUGGING! JANGAN LUPA NONAKTIFKAN DI PRODUKSI.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Pastikan path db_connect.php benar relatif dari lokasi chat_handler.php
require_once 'db_connect.php'; 

$response = ['status' => 'error', 'message' => 'Invalid request'];

// --- AUTENTIKASI DAN OTORISASI UMUM ---
// Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Unauthorized access. Please log in.';
    echo json_encode($response);
    exit;
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Dapatkan action dari GET atau POST
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- LOGIKA UNTUK ROLE ADMIN ---
if ($current_user_role === 'admin') {
    if ($action === 'get_chat_list_for_admin') {
        // Mengambil daftar chat user untuk Admin
        $stmt = $conn->prepare("
            SELECT 
                u.id AS user_id, 
                u.username,
                COUNT(CASE WHEN cm.sender = 'user' AND cm.is_read_by_admin = FALSE THEN 1 END) AS unread_messages_count,
                MAX(cm.timestamp) AS last_message_timestamp,
                (SELECT message FROM chat_messages WHERE user_id = u.id ORDER BY timestamp DESC LIMIT 1) AS last_message
            FROM 
                users u
            LEFT JOIN 
                chat_messages cm ON u.id = cm.user_id
            WHERE
                u.role = 'user' -- Hanya tampilkan chat dengan role 'user'
            GROUP BY 
                u.id, u.username
            ORDER BY 
                last_message_timestamp DESC
        ");

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $response = ['status' => 'success', 'chat_list' => $users]; // Mengganti 'users' menjadi 'chat_list'
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for admin chat list: ' . $conn->error;
        }

    } elseif ($action === 'get_messages' && isset($_GET['user_id'])) {
        // Admin melihat pesan dari user tertentu
        $target_user_id = (int)$_GET['user_id'];

        // Pastikan admin hanya bisa melihat chat user
        $stmt_check_user = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'user'");
        if ($stmt_check_user) {
            $stmt_check_user->bind_param("i", $target_user_id);
            $stmt_check_user->execute();
            $result_check = $stmt_check_user->get_result();
            if ($result_check->num_rows === 0) {
                $response['message'] = 'User chat not found or unauthorized.';
                echo json_encode($response);
                exit;
            }
            $stmt_check_user->close();
        } else {
             $response['message'] = 'Failed to prepare user check statement: ' . $conn->error;
             echo json_encode($response);
             exit;
        }

        $stmt = $conn->prepare("
            SELECT
                cm.message,
                cm.timestamp,
                cm.sender,
                CASE
                    WHEN cm.sender = 'user' THEN (SELECT username FROM users WHERE id = cm.user_id)
                    WHEN cm.sender = 'admin' THEN (SELECT username FROM users WHERE id = ?) -- Ambil nama admin yang sedang login
                    ELSE 'N/A'
                END AS sender_name
            FROM
                chat_messages cm
            WHERE
                cm.user_id = ?
            ORDER BY
                cm.timestamp ASC
        ");

        if ($stmt) {
            $stmt->bind_param("ii", $current_user_id, $target_user_id); // Bind ID admin dan user target
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
                $response = ['status' => 'success', 'messages' => $messages];
            } else {
                $response['message'] = 'Execution failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for admin messages: ' . $conn->error;
        }

    } elseif ($action === 'send_message' && isset($_POST['user_id'], $_POST['message'])) {
        $target_user_id = (int)$_POST['user_id'];
        $message_content = trim($_POST['message']);

        if (empty($message_content)) {
            $response['message'] = 'Message cannot be empty.';
            echo json_encode($response);
            exit;
        }

        // Admin mengirim pesan, is_read_by_user = FALSE, is_read_by_admin = TRUE
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, message, is_read_by_user, is_read_by_admin) VALUES (?, 'admin', ?, FALSE, TRUE)");
        if ($stmt) {
            $stmt->bind_param("is", $target_user_id, $message_content);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Pesan terkirim.'];
            } else {
                $response['message'] = 'Gagal mengirim pesan: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for admin sending message: ' . $conn->error;
        }

    } elseif ($action === 'mark_user_messages_as_read_by_admin' && isset($_POST['user_id'])) {
        $target_user_id = (int)$_POST['user_id'];

        $stmt = $conn->prepare("UPDATE chat_messages SET is_read_by_admin = TRUE WHERE user_id = ? AND sender = 'user' AND is_read_by_admin = FALSE");
        if ($stmt) {
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Pesan pengguna ditandai sudah dibaca oleh admin.'];
            } else {
                $response['message'] = 'Gagal menandai pesan pengguna sudah dibaca: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for marking user messages as read: ' . $conn->error;
        }
    }

}
// --- LOGIKA UNTUK ROLE USER ---
elseif ($current_user_role === 'user') {
    if ($action === 'get_messages') {
        $target_user_id = $current_user_id; // Ambil dari sesi, bukan GET/POST

        $stmt = $conn->prepare("
            SELECT
                cm.message,
                cm.timestamp,
                cm.sender,
                CASE
                    WHEN cm.sender = 'user' THEN (SELECT username FROM users WHERE id = ?) 
                    WHEN cm.sender = 'admin' THEN (SELECT username FROM users WHERE role = 'admin' LIMIT 1) 
                    ELSE 'N/A'
                END AS sender_name
            FROM
                chat_messages cm
            WHERE
                cm.user_id = ?
            ORDER BY
                cm.timestamp ASC
        ");

        if ($stmt) {
            $stmt->bind_param("ii", $target_user_id, $target_user_id); // Bind user_id dua kali
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $messages = [];
                while ($row = $result->fetch_assoc()) {
                    $messages[] = $row;
                }
                $response = ['status' => 'success', 'messages' => $messages];
            } else {
                $response['message'] = 'Execution failed: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for user messages: ' . $conn->error;
        }

    } elseif ($action === 'send_message' && isset($_POST['message'])) {
        $message_content = trim($_POST['message']);

        if (empty($message_content)) {
            $response['message'] = 'Message cannot be empty.';
            echo json_encode($response);
            exit;
        }

        // User mengirim pesan, is_read_by_user = TRUE, is_read_by_admin = FALSE
        $stmt = $conn->prepare("INSERT INTO chat_messages (user_id, sender, message, is_read_by_user, is_read_by_admin) VALUES (?, 'user', ?, TRUE, FALSE)");
        if ($stmt) {
            $stmt->bind_param("is", $current_user_id, $message_content);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Pesan terkirim.'];
            } else {
                $response['message'] = 'Gagal mengirim pesan: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for user sending message: ' . $conn->error;
        }

    } elseif ($action === 'mark_admin_messages_as_read_by_user') {
        $target_user_id = $current_user_id;

        $stmt = $conn->prepare("UPDATE chat_messages SET is_read_by_user = TRUE WHERE user_id = ? AND sender = 'admin' AND is_read_by_user = FALSE");
        if ($stmt) {
            $stmt->bind_param("i", $target_user_id);
            if ($stmt->execute()) {
                $response = ['status' => 'success', 'message' => 'Pesan admin ditandai sudah dibaca oleh user.'];
            } else {
                $response['message'] = 'Gagal menandai pesan admin sudah dibaca: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to prepare statement for marking admin messages as read: ' . $conn->error;
        }
    }
}
// Jika role tidak dikenal atau action tidak valid untuk role tersebut
else {
    $response['message'] = 'Access denied for this role or invalid action.';
}

echo json_encode($response);
$conn->close();
?>