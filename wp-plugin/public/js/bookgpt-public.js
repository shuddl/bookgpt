/**
 * BookGPT Public JavaScript
 * 
 * This file loads and initializes the BookGPT chat widget within WordPress
 * It uses the configuration passed from the server via wp_localize_script
 */
(function($) {
    'use strict';

    // Initialize BookGPT widget when DOM is fully loaded
    $(document).ready(function() {
        initializeBookGPT();
    });

    /**
     * Main initialization function for BookGPT widget
     */
    function initializeBookGPT() {
        // Check if configuration is available
        if (!window.bookGptConfig || !window.bookGptConfig.apiUrl) {
            console.error('BookGPT: Missing configuration. Widget not initialized.');
            return;
        }

        // Find all widget containers
        const containers = document.querySelectorAll('.bookgpt-container');
        
        // If no explicit containers found and not already initialized, create fixed container
        if (containers.length === 0 && !document.getElementById('book-chat-widget-container')) {
            createFixedWidget();
        }
        
        // Load the main script
        loadMainScript();
    }

    /**
     * Create a fixed position widget container
     */
    function createFixedWidget() {
        const container = document.createElement('div');
        container.classList.add('bookgpt-container', 'bookgpt-fixed');
        container.setAttribute('data-position', 'fixed');
        container.setAttribute('data-theme', 'light');
        document.body.appendChild(container);
    }

    /**
     * Load the main BookGPT script
     */
    function loadMainScript() {
        // Create script element to load the widget script
        const scriptUrl = window.bookGptConfig.scriptUrl || 'https://bookgpt.vercel.app/script.js';
        
        const script = document.createElement('script');
        script.src = scriptUrl;
        script.async = true;
        script.defer = true;
        
        // Handle script load error
        script.onerror = function() {
            console.error('BookGPT: Failed to load script from ' + scriptUrl);
            
            // Try to load from Vercel as fallback if using custom URL
            if (scriptUrl !== 'https://bookgpt.vercel.app/script.js') {
                console.log('BookGPT: Attempting to load from Vercel fallback URL');
                const fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://bookgpt.vercel.app/script.js';
                fallbackScript.async = true;
                fallbackScript.defer = true;
                document.head.appendChild(fallbackScript);
            }
        };
        
        // Add the script to the document
        document.head.appendChild(script);
    }

    /**
     * Apply styles to containers based on attributes
     * This runs after the main script initializes the widget
     */
    function applyContainerStyles() {
        document.querySelectorAll('.bookgpt-container').forEach(function(container) {
            const position = container.getAttribute('data-position') || 'default';
            const theme = container.getAttribute('data-theme') || 'light';
            
            // Handle different positions
            if (position === 'inline') {
                // Inline styles are handled by inline style attribute
            } else if (position === 'fixed') {
                // For fixed position, the main script handles this
            }
            
            // Handle theme
            if (theme === 'dark') {
                // Apply dark theme overrides if needed
            }
        });
    }

    /**
     * Initialize the widget form of the AI chat
     */
    function initializeWidgetForm() {
        // Check if the widget container already exists
        if (document.getElementById('book-chat-widget-container')) {
            console.log('Widget form already initialized.');
            return;
        }

        // Create the widget container
        const widgetContainer = document.createElement('div');
        widgetContainer.id = 'book-chat-widget-container';
        document.body.appendChild(widgetContainer);

        // Initialize the chat widget
        initializeBookChatWidget();
    }

    /**
     * Initialize the Book Chat Widget
     */
    function initializeBookChatWidget() {
        const widgetContainer = document.getElementById('book-chat-widget-container');
        if (!widgetContainer) {
            console.error('BookGPT: Widget container not found.');
            return;
        }

        // Create the widget header
        const header = document.createElement('div');
        header.id = 'widget-header';
        header.innerHTML = `<h1>${window.bookGptConfig.widgetTitle || 'Book Recommendations'}</h1>`;
        widgetContainer.appendChild(header);

        // Create the chat area
        const chatArea = document.createElement('div');
        chatArea.id = 'widget-chat-area';
        chatArea.innerHTML = `<div id="widget-message-list"></div>`;
        widgetContainer.appendChild(chatArea);

        // Create the input section
        const inputSection = document.createElement('div');
        inputSection.id = 'widget-input-section';
        inputSection.innerHTML = `
            <div id="widget-input-wrapper">
                <textarea id="widget-user-input" placeholder="Ask for book recommendations..." rows="1"></textarea>
                <button id="widget-send-button" aria-label="Send Message">&rarr;</button>
            </div>
            <div id="widget-disclaimer">
                <p>AI recommendations powered by ChatGPT & Google Books. Links may be affiliated. This is a demo.</p>
            </div>
        `;
        widgetContainer.appendChild(inputSection);

        // Add event listener for send button
        document.getElementById('widget-send-button').addEventListener('click', sendMessage);

        // Add event listener for Enter key in textarea
        document.getElementById('widget-user-input').addEventListener('keypress', function(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        });
    }

    /**
     * Send user message to the chat bot
     */
    function sendMessage() {
        const userInput = document.getElementById('widget-user-input');
        const message = userInput.value.trim();
        if (message === '') return;

        // Display user message
        displayMessage(message, 'user');

        // Clear input
        userInput.value = '';

        // Send message to the chat bot
        fetch(window.bookGptConfig.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ message: message })
        })
        .then(response => response.json())
        .then(data => {
            if (data && data.response) {
                displayMessage(data.response, 'bot');
            } else {
                displayMessage('Sorry, I could not process your request.', 'bot');
            }
        })
        .catch(error => {
            console.error('BookGPT: Error sending message', error);
            displayMessage('Sorry, there was an error processing your request.', 'bot');
        });
    }

    /**
     * Display a message in the chat area
     * @param {string} message - The message text
     * @param {string} sender - The sender ('user' or 'bot')
     */
    function displayMessage(message, sender) {
        const messageList = document.getElementById('widget-message-list');
        const messageElement = document.createElement('div');
        messageElement.classList.add('widget-message', `widget-${sender}-message`);
        messageElement.innerHTML = `<div class="widget-message-content">${message}</div>`;
        messageList.appendChild(messageElement);

        // Scroll to the bottom of the chat area
        messageList.scrollTop = messageList.scrollHeight;
    }

    // Call the new widget initialization function
    initializeWidgetForm();

})(jQuery);
