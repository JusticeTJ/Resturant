// Hungry Man's Enhanced Chatbot with Conversation History
document.addEventListener('DOMContentLoaded', function () {
    const chatbot = document.getElementById('chatbot');
    const chatToggle = document.getElementById('chatbot-toggle');
    const chatClose = document.getElementById('chatbot-close');
    const messages = document.getElementById('chatbot-messages');
    const form = document.getElementById('chatbot-form');
    const input = document.getElementById('chatbot-input');
    const quickBtns = document.querySelectorAll('.quick-btn');

    // Session ID for conversation tracking
    let sessionId = localStorage.getItem('chatbot_session_id');
    if (!sessionId) {
        sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('chatbot_session_id', sessionId);
    }

    // PHP endpoint - use absolute path from current directory
    const PHP_ENDPOINT = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '/chatbot-reply.php');

    console.log('🤖 Chatbot initialized');
    console.log('📍 Session ID:', sessionId);
    console.log('📍 PHP Endpoint:', PHP_ENDPOINT);

    if (!chatbot || !chatToggle || !form || !input) {
        console.error("❌ Chatbot elements not found!");
        return;
    }

    // Toggle chatbot
    chatToggle.addEventListener('click', function () {
        chatbot.classList.toggle('open');
        if (chatbot.classList.contains('open')) {
            input.focus();
            if (messages.children.length === 0) {
                addMessage("👋 Hello! Welcome to Hungry Man's. How can I help you today?", 'bot');
            }
        }
    });

    // Close chatbot
    if (chatClose) {
        chatClose.addEventListener('click', function () {
            chatbot.classList.remove('open');
        });
    }

    // Quick action buttons
    quickBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const action = this.textContent.trim();
            console.log('📤 Quick button clicked:', action);
            addMessage(action, 'user');
            sendMessage(action);
        });
    });

    // Form submission
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const message = input.value.trim();
        if (!message) return;

        console.log('📤 Message sent:', message);
        addMessage(message, 'user');
        input.value = '';

        sendMessage(message);
    });

    function sendMessage(userMessage) {
        showTyping();

        console.log('🔄 Sending request to:', PHP_ENDPOINT);
        console.log('📝 Message:', userMessage);
        console.log('🆔 Session ID:', sessionId);

        fetch(PHP_ENDPOINT, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: userMessage,
                session_id: sessionId
            })
        })
        .then(response => {
            console.log('📨 Response status:', response.status);

            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Response received:', data);
            removeTyping();

            if (data.reply) {
                addMessage(data.reply, 'bot');
            } else {
                addMessage("I couldn't process that request. The response was empty.", 'bot');
            }
        })
        .catch(error => {
            console.error('❌ FETCH ERROR:', error);
            removeTyping();

            let errorMsg = "❌ Connection Error\n\n";

            if (error.message.includes('HTTP')) {
                errorMsg += "Your PHP file isn't found.\n\n";
                errorMsg += "Tried to fetch from:\n" + PHP_ENDPOINT + "\n\n";
                errorMsg += "Try updating the PHP_ENDPOINT path in the chatbot code.";
            } else if (error.message.includes('Failed to fetch')) {
                errorMsg += "Network error or CORS issue.\n\n";
                errorMsg += "Make sure:\n";
                errorMsg += "1. PHP file exists\n";
                errorMsg += "2. Web server is running\n";
                errorMsg += "3. File path is correct";
            } else {
                errorMsg += error.message;
            }

            addMessage(errorMsg, 'bot');
        });
    }

    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.className = 'msg ' + sender;

        // Handle multiline messages
        if (text.includes('\n')) {
            div.innerHTML = text.replace(/\n/g, '<br>');
        } else {
            div.textContent = text;
        }

        messages.appendChild(div);
        messages.scrollTop = messages.scrollHeight;
    }

    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typing-indicator';
        typingDiv.className = 'typing-indicator';
        typingDiv.innerHTML = '<div class="typing-dot"></div><div class="typing-dot"></div><div class="typing-dot"></div>';
        messages.appendChild(typingDiv);
        messages.scrollTop = messages.scrollHeight;
    }

    function removeTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }
});