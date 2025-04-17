(function() { // IIFE Start
    // Backend API URL - Determine environment dynamically
    const determineApiUrl = () => {
        // WordPress integration - check for API URL configuration
        if (window.bookGptConfig && window.bookGptConfig.apiUrl) {
            console.log(`Using WordPress configured API URL: ${window.bookGptConfig.apiUrl}`);
            return window.bookGptConfig.apiUrl;
        }
        
        // Check if we're running on localhost
        const isLocalhost = window.location.hostname === 'localhost' || 
                           window.location.hostname === '127.0.0.1';
        
        if (isLocalhost) {
            return 'http://localhost:8005/api/chat';
        }
        
        // Default to relative path for Vercel deployment or other scenarios
        // This works when the frontend and backend are deployed together
        return '/api/chat';
    };

    const API_URL = determineApiUrl();
    console.log(`Using API URL: ${API_URL} on host: ${window.location.hostname}`);

    // Generate a unique session ID for this conversation
    const sessionId = `session_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
    console.log(`Session ID: ${sessionId}`);
    
    // Initialize Web Worker immediately at script evaluation time
    let worker = null;
    
    // Create and initialize the worker synchronously during initial script execution
    function initializeWorker() {
        try {
            worker = new Worker('./worker.js');
            
            // IMPORTANT: Add message listener immediately during initialization
            // This prevents the "Event handler of 'message' event must be added on initial evaluation" warning
            worker.addEventListener('message', handleWorkerMessage);
            
            console.log('Web Worker initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Web Worker:', error);
            worker = null;
        }
    }
    
    // Initialize worker immediately during script evaluation
    initializeWorker();
    
    /**
     * Handle messages received from the Web Worker
     * @param {MessageEvent} e - The message event from the worker
     */
    function handleWorkerMessage(e) {
        const data = e.data;
        
        switch (data.type) {
            case 'message_processed':
                console.log('Worker processed message:', data.result);
                break;
                
            case 'recommendations_result':
                console.log('Received book recommendations from worker:', data.books);
                // You could use these recommendations in the UI
                break;
                
            case 'analytics_tracked':
                console.log('Analytics tracking confirmed:', data.success);
                break;
                
            case 'error':
                console.error('Worker error:', data.error);
                break;
                
            default:
                console.warn('Unknown message from worker:', data);
        }
    }
    
    /**
     * Send a message to the worker for processing
     * @param {string} message - The message to process
     */
    function processMessageInWorker(message) {
        if (worker) {
            worker.postMessage({
                type: 'process_message',
                message: message
            });
        }
    }
    
    /**
     * Send an analytics event to the worker for tracking
     * @param {Object} event - The event data to track
     */
    function trackAnalyticsEvent(event) {
        if (worker) {
            worker.postMessage({
                type: 'track_analytics',
                event: event
            });
        } else {
            // Fallback if worker isn't available
            console.log('Analytics event (fallback):', event);
        }
    }

    // CSS Styles (from style.css) - Updated selectors for widget IDs
    const cssStyles = `
        /* Reset & Global Styles (applied to host page if needed, consider scoping) */
        /* ... (Keep reset if necessary, but be mindful of host page conflicts) ... */

        /* Widget Container */
        #book-chat-widget-container {
            position: fixed; /* Changed from relative/absolute */
            bottom: 20px;    /* Position bottom right */
            right: 20px;
            width: 380px;    /* Widget width */
            height: 550px;   /* Widget height */
            max-height: calc(100vh - 40px); /* Prevent overlap with viewport edges */
            display: flex;
            flex-direction: column;
            background-color: #fff;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15); /* Enhanced shadow */
            border: 1px solid #eaeaef;
            border-radius: 12px; /* Rounded corners */
            overflow: hidden;    /* Important for border-radius */
            z-index: 9999;     /* Ensure it's above other content */
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 15px; /* Slightly smaller base font for widget */
            line-height: 1.5;
            color: #202123;
            transition: width 0.3s ease, height 0.3s ease; /* Smooth transitions for responsive changes */
        }

        /* Widget Header */
        #widget-header {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #eaeaef;
            background-color: #f9f9f9;
            flex-shrink: 0; /* Prevent header from shrinking */
        }

        #widget-header h1 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #202123;
            text-align: center;
            margin: 0;
        }

        /* Chat Area */
        #widget-chat-area {
            flex-grow: 1; /* Takes remaining space */
            overflow-y: auto; /* Enables scrolling */
            padding: 1rem;
            display: flex;
            flex-direction: column;
            scroll-behavior: smooth;
            background-color: #fafafa;
            /* Optional: Subtle scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: #ccc #f0f0f0;
        }
        #widget-chat-area::-webkit-scrollbar {
            width: 6px;
        }
        #widget-chat-area::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 3px;
        }
        #widget-chat-area::-webkit-scrollbar-thumb {
            background-color: #ccc;
            border-radius: 3px;
        }
        #widget-chat-area::-webkit-scrollbar-thumb:hover {
            background-color: #aaa;
        }

        #widget-message-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding-bottom: 0.5rem;
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        /* Messages */
        .widget-message {
            padding: 0.7rem 1rem;
            border-radius: 16px;
            max-width: 85%; /* Adjusted max-width for widget */
            line-height: 1.4;
            animation: fadeIn 0.3s ease-in-out;
            position: relative;
            word-wrap: break-word;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        }

        /* ... existing keyframes fadeIn ... */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .widget-user-message {
            align-self: flex-end;
            background-color: #3b82f6;
            color: #ffffff;
            margin-left: auto;
            font-weight: 500;
        }

        .widget-bot-message {
            align-self: flex-start;
            background-color: #f0f0f0;
            color: #202123;
            border: 1px solid #e0e0e0;
            margin-right: auto;
        }

        /* ... existing message content, links, recommendations container styles ... */
        .widget-message-content { width: 100%; }
        .widget-bot-message a { color: #2563eb; text-decoration: underline; }
        .widget-bot-message a:hover { text-decoration: none; }
        .widget-recommendations-container { display: flex; flex-direction: column; width: 100%; gap: 0.8rem; margin: 0.5rem 0; }
        .widget-recommendations-message { max-width: 100% !important; padding: 0.5rem !important; background-color: transparent !important; border: none !important; box-shadow: none !important; }

        /* Recommendation Cards */
        .widget-recommendation-card {
            display: flex;
            flex-direction: column;
            border: 1px solid #eaeaef;
            border-radius: 10px;
            overflow: hidden;
            background-color: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .widget-recommendation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .widget-recommendation-content {
            display: flex;
            padding: 0.7rem;
            gap: 0.8rem;
            align-items: flex-start;
        }

        .widget-book-cover {
            width: 60px;
            height: 90px;
            object-fit: cover;
            border: 1px solid #eaeaef;
            flex-shrink: 0;
            border-radius: 4px;
        }

        .widget-book-cover-placeholder {
            width: 60px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f9f9f9;
            color: #888;
            font-size: 0.7em;
            text-align: center;
            border: 1px solid #eaeaef;
            flex-shrink: 0;
            border-radius: 4px;
        }

        .widget-book-info {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            overflow: hidden;
        }

        .widget-book-title { font-size: 0.95em; font-weight: 600; margin: 0; color: #202123; }
        .widget-book-author { font-size: 0.8em; color: #555; margin: 0; }
        .widget-book-description { font-size: 0.8em; color: #444; line-height: 1.4; max-height: 4.2em; overflow: hidden; }
        .widget-book-link { display: inline-block; margin-top: 0.5rem; padding: 0.4rem 0.8rem; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 14px; font-size: 0.75em; font-weight: 500; align-self: flex-start; transition: background-color 0.2s ease; }
        .widget-book-link:hover { background-color: #2563eb; }
        .widget-book-reasoning { margin: 0.4rem 0; padding: 0.5rem; border-radius: 6px; background-color: #f0f7ff; border-left: 2px solid #3b82f6; }
        .widget-book-reasoning h4 { margin: 0 0 0.2rem 0; font-size: 0.75em; font-weight: 600; color: #3b82f6; }
        .widget-book-reasoning p { margin: 0; font-size: 0.75em; line-height: 1.3; font-style: italic; color: #444; }

        /* Suggestion Buttons */
        .widget-suggestions-container {
            margin-top: 0.6rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: flex-start;
            width: 100%;
        }

        .widget-suggestion-button {
            display: inline-block;
            background-color: #fff;
            border: 1px solid #dce3f9;
            padding: 0.4rem 0.8rem;
            border-radius: 16px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s ease;
            color: #3b82f6;
            font-weight: 500;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        .widget-suggestion-button:hover { background-color: #eef2ff; border-color: #c7d5f8; transform: translateY(-1px); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.06); }
        .widget-suggestion-button:active { transform: translateY(0px); background-color: #dce3f9; }

        /* Input Section */
        #widget-input-section {
            padding: 0.8rem;
            border-top: 1px solid #eaeaef;
            background-color: #fff;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.03);
            flex-shrink: 0; /* Prevent input section from shrinking */
        }

        #widget-input-wrapper {
            display: flex;
            align-items: flex-end; /* Align items to bottom for multi-line text */
            gap: 0.5rem;
            padding: 0.5rem 0.8rem;
            border: 1px solid #eaeaef;
            border-radius: 20px;
            background-color: #fff;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
            width: 100%;
            margin: 0 auto;
        }
        #widget-input-wrapper:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1); }

        #widget-user-input {
            flex-grow: 1;
            border: none;
            outline: none;
            padding: 0.4rem;
            font-size: 0.9rem;
            font-family: inherit;
            resize: none;
            background: transparent;
            max-height: 80px;
            line-height: 1.4;
            /* Removed align-self: stretch; align-items: flex-end handles it */
        }

        #widget-send-button {
            background-color: #3b82f6;
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.2s ease;
            flex-shrink: 0;
            /* Removed align-self: flex-end; align-items: flex-end on wrapper handles it */
            margin-bottom: 2px; /* Fine-tune vertical alignment with textarea */
        }
        #widget-send-button:hover { background-color: #2563eb; }
        #widget-send-button:disabled { background-color: #93b4f8; cursor: not-allowed; }

        /* Loading Indicator */
        #widget-loading-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem;
            height: 30px;
            position: absolute; /* Position relative to chat area */
            bottom: 5px; /* Position above input section */
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .widget-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(59, 130, 246, 0.2);
            border-radius: 50%;
            border-left-color: #3b82f6;
            animation: spin 1s linear infinite;
        }

        /* ... existing keyframes spin ... */
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .widget-hidden { display: none !important; }

        /* Disclaimer */
        #widget-disclaimer {
            font-size: 0.7rem;
            color: #aaa;
            text-align: center;
            padding: 0.4rem 0.8rem;
            background-color: #f9f9f9;
            border-top: 1px solid #eee;
            flex-shrink: 0; /* Prevent disclaimer from shrinking */
        }

        /* Responsive Adjustments for Widget */
        @media (max-width: 480px) {
             #book-chat-widget-container {
                width: calc(100% - 20px); /* Near full width */
                height: calc(100% - 20px); /* Near full height */
                max-height: 90vh;         /* Limit height */
                bottom: 10px;             /* Adjust position */
                right: 10px;
                left: 10px;               /* Center horizontally by setting left */
             }
             /* Adjust font sizes slightly for smaller widget */
             .widget-message { font-size: 14px; }
             #widget-user-input { font-size: 0.85rem; }
             .widget-book-title { font-size: 0.9em; }
             .widget-book-author { font-size: 0.75em; }
             .widget-book-description { font-size: 0.75em; }
             .widget-suggestion-button { font-size: 0.75rem; padding: 0.3rem 0.7rem; }
        }
    `;

    // --- DOM Element References (will be assigned in initializeBookChatWidget) ---
    let widgetContainer, messageList, userInput, sendButton, loadingIndicator, inputWrapper;

    /**
     * Scrolls an element into view within the chat area
     * @param {HTMLElement} element - The element to scroll into view
     */
    function scrollElementIntoView(element) {
        if (element && messageList) {
             // Get the chat area container
            const chatArea = widgetContainer.querySelector('#widget-chat-area');
            if (chatArea) {
                // Calculate scroll position
                const elementRect = element.getBoundingClientRect();
                const chatAreaRect = chatArea.getBoundingClientRect();
                const offset = elementRect.top - chatAreaRect.top + chatArea.scrollTop;

                // Scroll smoothly
                chatArea.scrollTo({
                    top: offset - 10, // Add a small offset from the top
                    behavior: 'smooth'
                });
            }
        } else if (messageList) { // Fallback for safety
            const chatArea = widgetContainer.querySelector('#widget-chat-area');
            if (chatArea) {
                chatArea.scrollTop = chatArea.scrollHeight;
            }
        } // <<< Added missing closing brace
    }


    /**
     * Displays a message in the chat interface
     * @param {string} text - The message text
     * @param {string} sender - 'user' or 'bot'
     * @param {Array} suggestions - Optional array of suggestion buttons to show
     * @returns {HTMLElement} The created message element
     */
    function displayMessage(text, sender, suggestions = []) {
        if (!messageList) return null; // Guard if not initialized

        const messageDiv = document.createElement('div');
        // Use renamed widget-specific classes
        messageDiv.classList.add('widget-message', sender === 'user' ? 'widget-user-message' : 'widget-bot-message');

        const contentDiv = document.createElement('div');
        contentDiv.classList.add('widget-message-content'); // Use renamed class

        if (sender === 'bot') {
            contentDiv.innerHTML = text;
        } else {
            contentDiv.innerText = text;
        }
        messageDiv.appendChild(contentDiv);

        if (sender === 'bot' && suggestions && suggestions.length > 0) {
            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.classList.add('widget-suggestions-container'); // Use renamed class

            suggestions.forEach(suggestionText => {
                const button = document.createElement('button');
                button.classList.add('widget-suggestion-button'); // Use renamed class
                button.textContent = suggestionText;
                button.addEventListener('click', () => {
                    handleSuggestionClick(suggestionText);
                });
                suggestionsContainer.appendChild(button);
            });
            messageDiv.appendChild(suggestionsContainer);
        }

        messageList.appendChild(messageDiv);
        return messageDiv;
    }

    /**
     * Handles click events on suggestion buttons
     * @param {string} suggestionText - The text of the clicked suggestion
     */
    function handleSuggestionClick(suggestionText) {
        const userMessageElement = displayMessage(suggestionText, 'user');
        if (userMessageElement) {
            scrollElementIntoView(userMessageElement);
        }

        if (userInput) {
            userInput.value = ''; // Clear input if exists
        }

        sendMessageToServer(suggestionText);
    }

    /**
     * Sends a message to the backend
     * @param {string} messageText - The message text to send
     */
    async function sendMessageToServer(messageText) {
        if (!messageText && messageText !== "") return; // Allow initial empty message

        console.log('Sending message to backend:', { sessionId, message: messageText });
        showLoading(true);
        
        // Track this interaction in the worker for analytics
        trackAnalyticsEvent({
            type: 'user_message',
            sessionId: sessionId,
            message: messageText,
            timestamp: new Date().toISOString()
        });

        try {
            // Also process message in worker (for any background tasks)
            processMessageInWorker(messageText);
            
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: sessionId, message: messageText })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ detail: "Unknown server error" }));
                console.error('API Error Response:', errorData);
                throw new Error(`Server error: ${response.status} ${response.statusText} - ${errorData.detail || 'No details'}`);
            }

            const data = await response.json();
            let lastAddedElement;

            if (data.bot_message) {
                lastAddedElement = displayMessage(data.bot_message, 'bot', data.suggestions || []);
            }

            if (data.books && data.books.length > 0) {
                lastAddedElement = displayBooks(data.books); // This will append after the message if both exist
            }

            if (!lastAddedElement && messageText !== "") { // Only show fallback if it wasn't the initial greeting fetch
                 lastAddedElement = displayMessage("I'm looking into that. Anything else?", 'bot',
                     ["Suggest Fantasy Books", "Recommend Sci-Fi", "Mystery Novels"]);
            }

            if (lastAddedElement) {
                scrollElementIntoView(lastAddedElement);
            }

        } catch (error) {
            console.error('Fetch API Error:', error);
            const errorElement = displayMessage(`Sorry, an error occurred. Please try again.`, 'bot',
                ["Suggest Fantasy Books", "Recommend Sci-Fi", "Mystery Novels"]);
             if (errorElement) {
                scrollElementIntoView(errorElement);
            }
        } finally {
            showLoading(false);
        }
    }

    /**
     * Displays book recommendations in card format
     * @param {Array} books - Array of book objects from the API
     * @returns {HTMLElement} The container element with all recommendations
     */
    function displayBooks(books) {
        if (!messageList) return null; // Guard

        const amazonTag = 'bookgpt-20'; // Your Amazon Associate Tag

        // Create a container for all book cards within a single bot message structure
        const recommendationsMessage = document.createElement('div');
        // Use renamed widget-specific classes
        recommendationsMessage.classList.add('widget-message', 'widget-bot-message', 'widget-recommendations-message');

        const booksContainer = document.createElement('div');
        booksContainer.classList.add('widget-recommendations-container'); // Use renamed class
        recommendationsMessage.appendChild(booksContainer);

        books.forEach((book, index) => {
            const card = document.createElement('div');
            card.classList.add('widget-recommendation-card'); // Use renamed class

            let processedDescription = 'No description available.';
            if (book.description) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = book.description;
                const textContent = tempDiv.textContent || tempDiv.innerText || "";
                // Adjusted length for smaller widget card
                processedDescription = textContent.length > 80 ? textContent.substring(0, 80) + '...' : textContent;
            }

            let amazonLink = null;
            if (book.amazon_link && book.amazon_link.includes('amazon.com')) {
                amazonLink = book.amazon_link;
            } else if (book.isbn13) {
                amazonLink = `https://www.amazon.com/s?k=${book.isbn13}&tag=${amazonTag}`;
            } else if (book.title) {
                let searchQuery = encodeURIComponent(book.title);
                if (book.authors && book.authors.length > 0) {
                    searchQuery += `+${encodeURIComponent(book.authors[0])}`;
                }
                amazonLink = `https://www.amazon.com/s?k=${searchQuery}&tag=${amazonTag}`;
            }

            // Use renamed widget-specific classes in the template literal
            const cardHTML = `
                <div class="widget-recommendation-content">
                    ${book.thumbnail ?
                        `<img src="${book.thumbnail}" alt="Cover of ${book.title}" class="widget-book-cover">` :
                        '<div class="widget-book-cover-placeholder">No Cover</div>'}
                    <div class="widget-book-info">
                        <h3 class="widget-book-title">${book.title || 'No Title'}</h3>
                        <p class="widget-book-author">by ${book.authors ? book.authors.join(', ') : 'Unknown Author'}</p>
                        ${book.reasoning ?
                            `<div class="widget-book-reasoning">
                                <h4>Why This Book?</h4>
                                <p>${book.reasoning}</p>
                            </div>` :
                            ''}
                        <div class="widget-book-description">${processedDescription}</div>
                        ${amazonLink ?
                            `<a href="${amazonLink}" target="_blank" rel="noopener noreferrer" class="widget-book-link">View on Amazon</a>` :
                            ''}
                    </div>
                </div>
            `;

            card.innerHTML = cardHTML;
            booksContainer.appendChild(card);
            console.log(`Book #${index + 1} - Title: ${book.title}, Amazon Link: ${amazonLink || 'None'}`);
        });

        messageList.appendChild(recommendationsMessage);
        return recommendationsMessage; // Return the container message element
    }


    /**
     * Shows or hides the loading indicator and disables/enables input
     * @param {boolean} isLoading - Whether the app is in a loading state
     */
    function showLoading(isLoading) {
        if (!loadingIndicator || !sendButton || !userInput) return; // Guard

        if (isLoading) {
            loadingIndicator.classList.remove('widget-hidden'); // Use renamed class
            sendButton.disabled = true;
            userInput.disabled = true;
        } else {
            loadingIndicator.classList.add('widget-hidden'); // Use renamed class
            sendButton.disabled = false;
            userInput.disabled = false;
            userInput.focus();
        }
    }

    /**
     * Handles sending a message typed by the user
     */
    async function sendMessage() {
        if (!userInput) return; // Guard
        const messageText = userInput.value.trim();
        if (!messageText) return;

        const userMessageElement = displayMessage(messageText, 'user');
         if (userMessageElement) {
            scrollElementIntoView(userMessageElement);
        }

        const currentUserInput = userInput.value; // Store before clearing
        userInput.value = '';
        adjustTextareaHeight();

        sendMessageToServer(currentUserInput);
    }

    /**
     * Adjusts the textarea height based on content
     */
    function adjustTextareaHeight() {
        if (!userInput) return; // Guard
        userInput.style.height = 'auto'; // Reset height
        // Calculate scroll height and set, respecting max-height from CSS
        const scrollHeight = userInput.scrollHeight;
        const maxHeight = parseInt(window.getComputedStyle(userInput).maxHeight, 10);
        userInput.style.height = Math.min(scrollHeight, maxHeight) + 'px';

        // Adjust input wrapper padding slightly if textarea grows
        if (inputWrapper) {
             inputWrapper.style.paddingTop = scrollHeight > 30 ? '0.7rem' : '0.5rem';
             inputWrapper.style.paddingBottom = scrollHeight > 30 ? '0.7rem' : '0.5rem';
        }
    }

    /**
     * Fetches the initial greeting message from the backend
     */
    async function fetchInitialMessage() {
        console.log("Fetching initial message...");
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: sessionId, message: "" }) // Empty message triggers greeting
            });
            if (!response.ok) throw new Error(`Initial fetch failed: ${response.status}`);

            const data = await response.json();
            let initialElement;
            if (data.bot_message) {
                initialElement = displayMessage(data.bot_message, 'bot', data.suggestions);
                 if (initialElement) {
                    scrollElementIntoView(initialElement);
                }
            }
        } catch (error) {
            console.error('Error getting initial greeting:', error);
            const fallbackElement = displayMessage("Hi! How can I help you find a book today?", 'bot',
                ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]);
             if (fallbackElement) {
                scrollElementIntoView(fallbackElement);
            }
        } finally {
             if (userInput) userInput.focus(); // Focus input after attempting fetch
        }
    }


    /**
     * Main initialization function for the chat widget
     */
    function initializeBookChatWidget() {
        // --- 1. Inject CSS ---
        const styleTag = document.createElement('style');
        styleTag.id = 'book-chat-widget-styles';
        styleTag.innerHTML = cssStyles;
        document.head.appendChild(styleTag);

        // --- 2. Create Widget Container ---
        widgetContainer = document.createElement('div');
        widgetContainer.id = 'book-chat-widget-container';

        // --- 3. Create Widget Structure (using innerHTML for simplicity) ---
        // Note: Using createElement for complex structures is often better,
        // but innerHTML is acceptable here for faster setup.
        widgetContainer.innerHTML = `
            <header id="widget-header">
                <h1>Book Buddy</h1>
            </header>
            <main id="widget-chat-area">
                <div id="widget-message-list"></div>
                 <div id="widget-loading-indicator" class="widget-hidden">
                     <div class="widget-spinner"></div>
                 </div>
            </main>
            <section id="widget-input-section">
                <div id="widget-input-wrapper">
                    <textarea id="widget-user-input" placeholder="Ask about books..." rows="1"></textarea>
                    <button id="widget-send-button" title="Send Message">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                          <path d="M15.854.146a.5.5 0 0 1 .11.54l-5.819 14.547a.75.75 0 0 1-1.329.124l-3.178-4.995L.643 7.184a.75.75 0 0 1 .124-1.33L15.314.037a.5.5 0 0 1 .54.11ZM6.636 10.07l2.761 4.338L14.13 2.576zm6.787-8.201L1.591 6.602l4.339 2.76z"/>
                        </svg>
                    </button>
                </div>
            </section>
            <div id="widget-disclaimer">AI-powered recommendations. Links may be affiliated.</div>
        `;

        // --- 4. Append to Body ---
        document.body.appendChild(widgetContainer);

        // --- 5. Get DOM References (within the widget container) ---
        messageList = widgetContainer.querySelector('#widget-message-list');
        userInput = widgetContainer.querySelector('#widget-user-input');
        sendButton = widgetContainer.querySelector('#widget-send-button');
        loadingIndicator = widgetContainer.querySelector('#widget-loading-indicator');
        inputWrapper = widgetContainer.querySelector('#widget-input-wrapper'); // Get reference to wrapper

        // --- 6. Setup Event Listeners ---
        if (sendButton) {
            sendButton.addEventListener('click', sendMessage);
        }
        if (userInput) {
            userInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    sendMessage();
                }
            });
            userInput.addEventListener('input', adjustTextareaHeight);
        }

        // --- 7. Initial Setup ---
        adjustTextareaHeight(); // Initial adjustment
        fetchInitialMessage(); // Fetch the initial greeting

        console.log("Book Chat Widget Initialized");
    }

    // --- Call Initialization ---
    // Check if the DOM is already loaded
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        // DOM is already loaded, initialize directly
        initializeBookChatWidget();
    } else {
        // Wait for the DOM to load
        document.addEventListener('DOMContentLoaded', initializeBookChatWidget);
    }

})(); // IIFE End