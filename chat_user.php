<?php
// cinematix/chat_user.php
session_start();

// Periksa apakah pengguna sudah login dan memiliki role 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    // Redirect ke halaman login jika belum login atau bukan role 'user'
    header('Location: login.php'); // Sesuaikan dengan halaman login Anda
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_username = $_SESSION['username'] ?? 'Pengguna'; // Ambil username jika ada di sesi
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Admin Cinematix</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS Umum */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .chat-container {
            width: 100%;
            max-width: 450px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 70vh;
            min-height: 500px;
        }

        .chat-header {
            background-color: #28a745;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.1em;
            font-weight: bold;
            border-bottom: 1px solid #228b22;
        }

        .chat-header .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5em;
            cursor: pointer;
            padding: 0 5px;
        }

        .chat-messages {
            flex-grow: 1;
            padding: 15px;
            overflow-y: auto;
            background-color: #eaf1f1;
            display: flex;
            flex-direction: column;
            gap: 10px;
            scroll-behavior: smooth;
        }

        .message {
            max-width: 80%;
            padding: 10px 15px;
            border-radius: 18px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message.user {
            align-self: flex-end;
            background-color: #d1e7dd;
            color: #333;
            border-bottom-right-radius: 4px;
        }

        .message.admin {
            align-self: flex-start;
            background-color: #fff;
            color: #333;
            border: 1px solid #ddd;
            border-bottom-left-radius: 4px;
        }

        .message-sender {
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 3px;
            color: #555;
        }
        .message.user .message-sender {
            text-align: right;
        }

        .message-timestamp {
            font-size: 0.7em;
            color: #888;
            margin-top: 5px;
            text-align: right;
        }
        .message.admin .message-timestamp {
            text-align: left;
        }

        .chat-input {
            padding: 15px;
            border-top: 1px solid #eee;
            display: flex;
            align-items: center;
            background-color: #f9f9f9;
        }

        .chat-input input[type="text"] {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 1em;
            margin-right: 10px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chat-input input[type="text"]:focus {
            border-color: #28a745;
        }

        .chat-input button {
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 25px;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: background-color 0.3s ease, transform 0.1s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .chat-input button:hover {
            background-color: #228b22;
            transform: translateY(-1px);
        }

        .chat-input button:active {
            transform: translateY(0);
        }

        /* Pesan Sistem */
        .system-message, .loading-message, .error-message {
            text-align: center;
            font-style: italic;
            color: #777;
            padding: 10px;
            border-radius: 8px;
            margin: 10px auto;
            max-width: 90%;
            background-color: #f0f0f0;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>

    <div class="chat-container" id="chatContainer">
        <div class="chat-header">
            <span>Chat Admin Cinematix</span>
            <button class="close-btn" onclick="window.history.back()">×</button>
        </div>
        <div class="chat-messages" id="chatMessages">
            <p class="loading-message">Memuat pesan...</p>
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Ketik pesan..." onkeypress="handleKeyPress(event)">
            <button id="sendMessageBtn">
                Kirim <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <script>
        // Mendapatkan ID pengguna dari PHP ke JavaScript
        const currentUserId = <?php echo json_encode($current_user_id); ?>;
        // Mendapatkan username dari PHP ke JavaScript
        const currentUsername = <?php echo json_encode($current_username); ?>;

        const chatMessagesDiv = document.getElementById('chatMessages');
        const messageInput = document.getElementById('messageInput');
        const sendMessageBtn = document.getElementById('sendMessageBtn');

        // Debugging awal: Pastikan ID user dan username terbaca
        console.log("DEBUG JS: currentUserId:", currentUserId);
        console.log("DEBUG JS: currentUsername:", currentUsername);

        // Fungsi untuk memuat pesan chat
        async function loadChat() {
            // Tampilkan pesan loading hanya jika belum ada pesan yang dimuat
            if (!chatMessagesDiv.querySelector('.message')) {
                chatMessagesDiv.innerHTML = '<p class="loading-message">Memuat pesan...</p>';
            }

            try {
                // PATH RELATIF: 'chat_handler.php' KARENA KEDUANYA ADA DI FOLDER 'cinematix/'
                const response = await fetch(`chat_handler.php?action=get_messages`); 
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error saat memuat pesan:', response.status, response.statusText, errorText);
                    chatMessagesDiv.innerHTML = `<p class="error-message">Gagal memuat pesan. Server merespons dengan status ${response.status}.</p>`;
                    return;
                }

                const data = await response.json();
                console.log("DEBUG JS: Data respon dari get_messages:", data);

                if (data.status === 'error') {
                    if (data.message === 'Unauthorized access. Please log in.') {
                         chatMessagesDiv.innerHTML = `<p class="error-message">Akses ditolak. Anda perlu login atau sesi Anda telah berakhir.</p>`;
                         setTimeout(() => { window.location.href = 'login.php'; }, 3000); 
                    } else {
                        chatMessagesDiv.innerHTML = `<p class="error-message">Cinematix Bot:<br> ${data.message}</p>`;
                    }
                    return;
                }
                
                chatMessagesDiv.innerHTML = ''; // Kosongkan pesan loading

                if (data.messages.length === 0) { // data.messages adalah array pesan
                    chatMessagesDiv.innerHTML = '<p class="system-message">Selamat datang! Belum ada pesan dalam obrolan ini. Mulailah dengan mengirim pesan pertama Anda.</p>';
                } else {
                    data.messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.classList.add('message', msg.sender);
                        
                        let senderName = msg.sender_name; 

                        messageDiv.innerHTML = `
                            <div class="message-sender">${senderName}</div>
                            <div class="message-text">${msg.message}</div>
                            <div class="message-timestamp">${new Date(msg.timestamp).toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' })}</div>
                        `;
                        chatMessagesDiv.appendChild(messageDiv);
                    });
                    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
                }
                
                // Setelah pesan dimuat, tandai pesan admin sebagai sudah dibaca oleh user
                markAdminMessagesAsReadByUser();

            } catch (error) {
                console.error('DEBUG JS: Error saat memuat chat:', error);
                chatMessagesDiv.innerHTML = `<p class="error-message">Cinematix Bot:<br> Gagal memuat pesan. Silakan coba lagi nanti.</p>`;
            }
        }

        // Fungsi untuk mengirim pesan
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (message === '') return;

            // Optimistic UI update: langsung tambahkan pesan ke tampilan
            appendMessage(currentUsername, 'user', message, new Date());
            messageInput.value = '';

            try {
                const formData = new FormData();
                formData.append('action', 'send_message');
                // user_id tidak perlu dikirim dari frontend, handler akan mengambilnya dari sesi
                formData.append('message', message);

                // PATH RELATIF: 'chat_handler.php' KARENA KEDUANYA ADA DI FOLDER 'cinematix/'
                const response = await fetch('chat_handler.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('HTTP Error saat mengirim pesan:', response.status, response.statusText, errorText);
                    return;
                }

                const data = await response.json();
                console.log("DEBUG JS: Respon dari send_message:", data);

                if (data.status === 'error') {
                    console.error('Gagal mengirim pesan:', data.message);
                } else {
                    console.log('Pesan berhasil dikirim.');
                    // Memuat ulang chat untuk melihat balasan admin jika ada, atau untuk memastikan sinkronisasi
                    loadChat(); 
                }

            } catch (error) {
                console.error('DEBUG JS: Error saat mengirim pesan:', error);
            }
        }

        // Fungsi bantuan untuk menambahkan pesan ke UI
        function appendMessage(senderName, senderType, messageText, timestamp) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', senderType);

            messageDiv.innerHTML = `
                <div class="message-sender">${senderName}</div>
                <div class="message-text">${messageText}</div>
                <div class="message-timestamp">${timestamp.toLocaleString('id-ID', { hour: '2-digit', minute: '2-digit', day: '2-digit', month: '2-digit', year: 'numeric' })}</div>
            `;
            chatMessagesDiv.appendChild(messageDiv);
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        // Fungsi untuk menangani tombol Enter pada input pesan
        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Fungsi untuk menandai pesan admin sebagai sudah dibaca oleh user
        async function markAdminMessagesAsReadByUser() {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_admin_messages_as_read_by_user');
                
                // PATH RELATIF: 'chat_handler.php' KARENA KEDUANYA ADA DI FOLDER 'cinematix/'
                const response = await fetch('chat_handler.php', {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.status === 'success') {
                        console.log('Pesan admin ditandai sudah dibaca oleh user.');
                    } else {
                        console.error('Gagal menandai pesan admin sudah dibaca:', data.message);
                    }
                } else {
                    console.error('HTTP Error saat menandai pesan admin sudah dibaca:', response.status, response.statusText);
                }
            } catch (error) {
                console.error('Error saat menandai pesan admin sudah dibaca:', error);
            }
        }

        // Event Listeners
        sendMessageBtn.addEventListener('click', sendMessage);

        // Inisialisasi: Panggil loadChat saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            if (currentUserId !== null) {
                loadChat();
                setInterval(loadChat, 5000); // Polling setiap 5 detik untuk cek pesan baru
            } else {
                chatMessagesDiv.innerHTML = '<p class="error-message">Anda harus login untuk memulai chat.</p>';
                console.error("User ID tidak tersedia. Chat tidak dapat dimuat.");
            }
        });
    </script>
</body>
</html>