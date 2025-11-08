<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$db = new Database();
$conn = $db->connect();

$user = getEmployeeData($conn, $_SESSION['employee_id']);

// Fetch chat history
$stmt = $conn->prepare("
    SELECT message, is_bot, created_at 
    FROM chat_messages 
    WHERE employee_id = :employee_id 
    ORDER BY created_at ASC 
    LIMIT 100
");
$stmt->execute(['employee_id' => $_SESSION['employee_id']]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Bot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .main-content {
            padding: 0;
            min-height: 100vh;
        }

        .chatbot-container {
            padding: 2rem 3rem;
        }

        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
        }

        .page-subtitle {
            font-size: 14px;
            color: #718096;
            margin-top: 0.5rem;
        }

        /* === CHAT LAYOUT === */
        .chat-wrapper {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 2rem;
        }

        /* === CHAT WINDOW === */
        .chat-panel {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            height: 700px;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .chat-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .chat-header-content h3 {
            margin: 0 0 0.5rem 0;
            font-size: 18px;
            font-weight: 700;
        }

        .chat-header-content p {
            margin: 0;
            font-size: 13px;
            opacity: 0.9;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 0.75rem;
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .message.bot .message-avatar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .message.user .message-avatar {
            background: #e2e8f0;
            color: #4a5568;
        }

        .message-content {
            max-width: 70%;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            line-height: 1.5;
            font-size: 14px;
        }

        .message.bot .message-content {
            background: #f0f4ff;
            color: #2d3748;
            border-left: 3px solid #667eea;
        }

        .message.user .message-content {
            background: #667eea;
            color: white;
        }

        .message-time {
            font-size: 12px;
            color: #a0aec0;
            margin-top: 0.25rem;
        }

        .chat-input-wrapper {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 0.75rem;
        }

        .chat-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .chat-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* === SIDEBAR PANEL === */
        .info-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .panel-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .panel-section h4 {
            font-size: 14px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .quick-buttons {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }

        .quick-btn {
            background: white;
            border: 2px solid #e2e8f0;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #404040;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .quick-btn:hover {
            background: #f0f4ff;
            border-color: #667eea;
            color: #667eea;
        }

        .question-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .question-btn {
            background: white;
            border: 1px solid #e2e8f0;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 12px;
            color: #4a5568;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
            line-height: 1.4;
            font-weight: 500;
        }

        .question-btn:hover {
            background: #f0f4ff;
            border-color: #667eea;
            color: #667eea;
        }

        .action-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .action-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }

        .action-btn.secondary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .action-btn.tertiary {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        @media (max-width: 1200px) {
            .chat-wrapper {
                grid-template-columns: 1fr;
            }

            .chat-panel {
                height: auto;
            }

            .info-panel {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .chatbot-container {
                padding: 1.5rem;
            }

            .chat-messages {
                padding: 1rem;
            }

            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <?php include_once 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include_once 'includes/employee_header.php'; ?>

        <div class="chatbot-container">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-chat-dots"></i> Chat with Chatbot
                </h1>
                <p class="page-subtitle">Get instant help with common HR questions and ticket management</p>
            </div>

            <!-- Chat Layout -->
            <div class="chat-wrapper">
                <!-- Chat Panel -->
                <div class="chat-panel">
                    <!-- Header -->
                    <div class="chat-header">
                        <div class="chat-avatar">
                            <i class="bi bi-robot"></i>
                        </div>
                        <div class="chat-header-content">
                            <h3>CHATBOT ASSISTANT</h3>
                            <p>Online</p>
                        </div>
                    </div>

                    <!-- Messages -->
                    <div class="chat-messages" id="chatMessages">
                        <div class="message bot">
                            <div class="message-avatar">
                                <i class="bi bi-robot"></i>
                            </div>
                            <div>
                                <div class="message-content">
                                    Hello! I'm your Assistant. How can I help you today?
                                </div>
                                <div class="message-time">Just now</div>
                            </div>
                        </div>

                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo $msg['is_bot'] ? 'bot' : 'user'; ?>">
                                <div class="message-avatar">
                                    <?php echo $msg['is_bot'] ? '<i class="bi bi-robot"></i>' : '<i class="bi bi-person-fill"></i>'; ?>
                                </div>
                                <div>
                                    <div class="message-content">
                                        <?php echo htmlspecialchars($msg['message']); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Input -->
                    <div class="chat-input-wrapper">
                        <input type="text" id="chatInput" class="chat-input" placeholder="Type your message..." autocomplete="off">
                        <button class="send-btn" id="sendBtn" onclick="sendMessage()">
                            <i class="bi bi-send-fill"></i>
                        </button>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="info-panel">
                    <!-- Quick Actions -->
                    <div class="panel-section">
                        <h4><i class="bi bi-lightning-fill"></i> Quick Actions</h4>
                        <div class="quick-buttons">
                            <button class="quick-btn" onclick="askQuestion('ID Request')">ID Request</button>
                            <button class="quick-btn" onclick="askQuestion('ID Lost or Access Card')">ID Lost or Access Card</button>
                            <button class="quick-btn" onclick="askQuestion('Notary Request')">Notary Request</button>
                            <button class="quick-btn" onclick="askQuestion('Others')">Others</button>
                        </div>
                    </div>

                    <!-- Common Questions -->
                    <div class="panel-section">
                        <h4><i class="bi bi-question-circle"></i> Common Questions</h4>
                        <div class="question-list">
                            <button class="question-btn" onclick="askQuestion('What are the company holidays?')">
                                ❓ What are the company holidays?
                            </button>
                            <button class="question-btn" onclick="askQuestion('How do I update my personal data?')">
                                📝 How do I update my personal data?
                            </button>
                            <button class="question-btn" onclick="askQuestion('What is the sick leave policy?')">
                                🏥 What is the sick leave policy?
                            </button>
                        </div>
                    </div>

                    <!-- Navigation -->
                    <div class="panel-section">
                        <h4><i class="bi bi-link-45deg"></i> Quick Links</h4>
                        <div class="action-grid">
                            <a href="tickets.php" class="action-btn">
                                <i class="bi bi-ticket"></i> View Tickets
                            </a>
                            <a href="status_update.php" class="action-btn secondary">
                                <i class="bi bi-check-all"></i> Check Status
                            </a>
                            <a href="profile.php" class="action-btn tertiary">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');

    // Send on Enter
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Focus on input when page loads
    window.addEventListener('load', () => {
        chatInput.focus();
        chatMessages.scrollTop = chatMessages.scrollHeight;
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        if (message === '') return;

        // Disable send button while processing
        sendBtn.disabled = true;

        // Add user message to UI
        addMessage(message, false);
        chatInput.value = '';
        chatInput.focus();

        // Send to API
        fetch('api/chatbot_api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.response) {
                // Small delay for natural feel
                setTimeout(() => {
                    addMessage(data.response, true);
                    sendBtn.disabled = false;
                    chatInput.focus();
                }, 500);
            } else {
                addMessage('Sorry, I encountered an error. Please try again.', true);
                sendBtn.disabled = false;
                chatInput.focus();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            addMessage('Connection error. Please check your internet connection.', true);
            sendBtn.disabled = false;
            chatInput.focus();
        });
    }

    function addMessage(text, isBot) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isBot ? 'bot' : 'user'}`;
        
        const timestamp = new Date().toLocaleTimeString('en-US', { 
            hour: '2-digit', 
            minute: '2-digit',
            hour12: true
        });

        messageDiv.innerHTML = `
            <div class="message-avatar">
                ${isBot ? '<i class="bi bi-robot"></i>' : '<i class="bi bi-person-fill"></i>'}
            </div>
            <div>
                <div class="message-content">
                    ${escapeHtml(text).replace(/\n/g, '<br>')}
                </div>
                <div class="message-time">${timestamp}</div>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        
        // Auto-scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function askQuestion(question) {
        chatInput.value = question;
        chatInput.focus();
        sendMessage();
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    // Prevent form submission on Enter in certain contexts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && e.target === chatInput && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Enable send button on input
    chatInput.addEventListener('input', function() {
        if (this.value.trim().length > 0) {
            sendBtn.disabled = false;
        }
    });

    // Initial state
    sendBtn.disabled = true;
</script>
</body>
</html>
