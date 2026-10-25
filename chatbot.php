<?php
// Hungry Man's chatbot widget
?>
<div id="chatbot" aria-label="Hungry Man's chat assistant">

    <button id="chatbot-toggle" type="button" aria-label="Open chat">
        <svg id="chat-icon-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M4 5h16v11H8l-4 4V5z"
                  stroke="currentColor" stroke-width="1.8"
                  stroke-linejoin="round"/>
        </svg>

        <svg id="chat-icon-close" viewBox="0 0 24 24" fill="none"
             aria-hidden="true" style="display:none;">
            <path d="M6 6l12 12M18 6L6 18"
                  stroke="currentColor" stroke-width="2"
                  stroke-linecap="round"/>
        </svg>
    </button>

    <div id="chatbot-panel">
        <div id="chatbot-header">
            <div class="chatbot-title">
                <span class="chatbot-dot"></span>
                Hungry Man's Assistant
            </div>
            <div class="chatbot-sub">
                Ask about our menu, hours, reservations or location.
            </div>
        </div>

        <div id="chatbot-messages" aria-live="polite">
            <div class="msg bot">
                Hi! Welcome to Hungry Man's. How can I help you?
            </div>
        </div>

        <form id="chatbot-form">
            <input
                id="chatbot-input"
                type="text"
                placeholder="Ask something..."
                autocomplete="off"
                aria-label="Chat message"
            >
            <button id="chatbot-send" type="submit" aria-label="Send message">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 4l17 8-17 8 4-8-4-8z"
                          stroke="currentColor" stroke-width="1.8"
                          stroke-linejoin="round"/>
                    <path d="M8 12h13"
                          stroke="currentColor" stroke-width="1.8"
                          stroke-linecap="round"/>
                </svg>
            </button>
        </form>
    </div>
</div>
