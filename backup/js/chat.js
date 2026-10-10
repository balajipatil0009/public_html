/* AI Chatbot Logic */
/* AI Chatbot Logic */
const initAiChat = () => {
    const chatBubble = document.getElementById('aiChatBubble');
    const chatWindow = document.getElementById('aiChatWindow');
    const chatClose = document.getElementById('aiChatClose');
    const chatForm = document.getElementById('aiChatForm');
    const chatInput = document.getElementById('aiChatInput');
    const chatMessages = document.getElementById('aiChatMessages');
    const chatSend = document.getElementById('aiChatSend');

    let history = []; // Stores recent conversation context
    let config = null; // Store fetched config (key, systemPrompt)

    if (!chatBubble || chatBubble.dataset.initialized) return;
    chatBubble.dataset.initialized = 'true';

    // Toggle Chat Window
    chatBubble.addEventListener('click', () => {
        chatWindow.classList.toggle('active');

        // Remove attention seekers on click
        const badge = chatBubble.querySelector('.ai-chat-badge');
        if (badge) badge.remove();
        const tooltip = document.querySelector('.ai-chat-greeting-tooltip');
        if (tooltip) tooltip.remove();

        if (chatWindow.classList.contains('active')) {
            chatInput.focus();
            // Show welcome message if empty
            if (chatMessages.innerHTML.trim() === '') {
                addMessage('bot', 'Hi there! 👋 I am Aria, your AI counselor. How can I help you discover your path in IT today?');
            }
        }
    });

    // Attention Seeker Logic
    setTimeout(() => {
        if (!chatWindow.classList.contains('active')) {
            // Add Badge
            const badge = document.createElement('div');
            badge.className = 'ai-chat-badge';
            badge.textContent = '1';
            chatBubble.appendChild(badge);

            // Add Greeting Tooltip
            const tooltip = document.createElement('div');
            tooltip.className = 'ai-chat-greeting-tooltip';
            tooltip.innerHTML = 'Hi! Need help choosing a course? 👋';
            chatBubble.parentElement.appendChild(tooltip);
        }
    }, 5000);

    chatClose.addEventListener('click', () => {
        chatWindow.classList.remove('active');
    });

    // Handle Sending Messages
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const message = chatInput.value.trim();
        if (!message || chatSend.disabled) return;

        // Add user message to UI
        addMessage('user', message);
        chatInput.value = '';
        chatInput.focus();

        // Disable input during "cooldown"
        chatSend.disabled = true;
        chatInput.disabled = true;

        // Artificial delay (1.5s) to slow down the pace and prevent instant spam
        // Also makes the bot feel like it's "reading"
        await new Promise(resolve => setTimeout(resolve, 1500));

        // Show typing indicator
        const typingId = showTypingIndicator();

        try {
            // 1. Fetch config/check rate limit from Backend
            const configResponse = await fetch('backend/api/chat.php', { method: 'POST' });

            if (!configResponse.ok) {
                const errorData = await configResponse.json();
                throw new Error(errorData.message || 'Connection failed');
            }

            const configData = await configResponse.json();
            const { apiKey, systemPrompt } = configData;

            // 2. Call Groq API directly from client
            const groqResponse = await fetch('https://api.groq.com/openai/v1/chat/completions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${apiKey}`
                },
                body: JSON.stringify({
                    model: 'llama3-70b-8192',
                    messages: [
                        { role: 'system', content: systemPrompt },
                        ...history,
                        { role: 'user', content: message }
                    ],
                    temperature: 0.7,
                    max_tokens: 150
                })
            });

            if (!groqResponse.ok) {
                const groqError = await groqResponse.json();
                console.error('Groq Error:', groqError);
                throw new Error('Groq API Error');
            }

            const data = await groqResponse.json();
            const aiReply = data.choices[0].message.content;

            removeTypingIndicator(typingId);

            addMessage('bot', aiReply);
            // Update history
            history.push({ role: 'user', content: message });
            history.push({ role: 'assistant', content: aiReply });
            // Keep history manageable
            if (history.length > 10) history = history.slice(-10);

        } catch (error) {
            removeTypingIndicator(typingId);
            addMessage('bot', error.message.includes('busy')
                ? error.message
                : 'Oops! I had trouble connecting to my brain. Could you try again in a moment?');
            console.error('Chat Error:', error);
        } finally {
            // Re-enable after 3 seconds total cooldown
            setTimeout(() => {
                chatSend.disabled = false;
                chatInput.disabled = false;
                chatInput.focus();
            }, 3000);
        }
    });

    function addMessage(sender, text) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `chat-message message-${sender}`;
        messageDiv.textContent = text;
        chatMessages.appendChild(messageDiv);

        // Auto scroll
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingIndicator() {
        const id = 'typing-' + Date.now();
        const indicator = document.createElement('div');
        indicator.id = id;
        indicator.className = 'chat-message message-bot typing-indicator';
        indicator.innerHTML = `
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
        `;
        chatMessages.appendChild(indicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function removeTypingIndicator(id) {
        const indicator = document.getElementById(id);
        if (indicator) indicator.remove();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAiChat);
} else {
    initAiChat();
}
