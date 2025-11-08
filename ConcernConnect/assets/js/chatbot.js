document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('chatbot-toggle');
    const chatWindow = document.getElementById('chatbot-window');
    const closeBtn = document.getElementById('close-chatbot');
    const sendBtn = document.getElementById('send-message');
    const input = document.getElementById('chatbot-input');
    const messagesDiv = document.getElementById('chatbot-messages');
    
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            chatWindow.classList.toggle('active');
            loadChatHistory();
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            chatWindow.classList.remove('active');
        });
    }
    
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
    
    if (input) {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
    
    document.querySelectorAll('.quick-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const msg = this.dataset.msg;
            input.value = msg;
            sendMessage();
        });
    });
    
    function sendMessage() {
        const message = input.value.trim();
        if (!message) return;
        
        addMessage('User: ' + message, 'user');
        input.value = '';
        
        fetch('api/chatbot.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data.response) {
                addMessage('AI: ' + data.response, 'bot');
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function loadChatHistory() {
        fetch('api/chatbot.php')
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                messagesDiv.innerHTML = '';
                data.messages.forEach(msg => {
                    addMessage(
                        (msg.is_bot ? 'AI: ' : 'User: ') + msg.message,
                        msg.is_bot ? 'bot' : 'user'
                    );
                });
            }
        })
        .catch(error => console.error('Error:', error));
    }
    
    function addMessage(text, type) {
        const msgDiv = document.createElement('div');
        msgDiv.className = 'chat-message ' + type;
        msgDiv.innerHTML = '<strong>' + text.split(':')[0] + ':</strong> ' + text.split(':').slice(1).join(':');
        messagesDiv.appendChild(msgDiv);
        messagesDiv.scrollTop = messagesDiv.scrollHeight;
    }
});
