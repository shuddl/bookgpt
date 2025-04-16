/**
 * Book Recommendation Chatbot - Core JavaScript
 * Handles user interactions, message display, and prepares for API integration
 */

document.addEventListener('DOMContentLoaded', () => {
    // Backend API URL - adjust as needed
    const API_URL = 'http://localhost:8000/api/chat'; // Adjust if backend runs elsewhere
    // const API_URL = '/api/chat'; // Use relative path if deploying backend/frontend together on same domain

    // DOM references
    const messageList = document.getElementById('message-list');
    const userInput = document.getElementById('userInput');
    const sendButton = document.getElementById('sendButton');
    const loadingIndicator = document.getElementById('loading-indicator');
    const inputWrapper = document.getElementById('input-wrapper');

    // Generate a unique session ID for this conversation
    const sessionId = `session_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
    console.log(`Session ID: ${sessionId}`); // For debugging

    /**
     * Displays a message in the chat interface
     * @param {string} text - The message text
     * @param {string} sender - 'user' or 'bot'
     * @param {Array} suggestions - Optional array of suggestion buttons to show
     */
    function displayMessage(text, sender, suggestions = []) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');

        // Basic text rendering (will be enhanced for recommendations later)
        // Handle potential newlines in bot messages
        messageDiv.innerText = text; // Use innerText to prevent basic HTML injection from text

        // Check if there are suggestions for bot messages
        if (sender === 'bot' && suggestions && suggestions.length > 0) {
            const suggestionsContainer = document.createElement('div');
            suggestionsContainer.classList.add('suggestions-container');

            suggestions.forEach(suggestionText => {
                const button = document.createElement('button');
                button.classList.add('suggestion-button');
                button.textContent = suggestionText;
                button.addEventListener('click', () => {
                    handleSuggestionClick(suggestionText);
                });
                suggestionsContainer.appendChild(button);
            });
            messageDiv.appendChild(suggestionsContainer); // Append buttons below the text
        }

        messageList.appendChild(messageDiv);
        scrollToBottom();
    }

    /**
     * Handles click events on suggestion buttons
     * @param {string} suggestionText - The text of the clicked suggestion
     */
    function handleSuggestionClick(suggestionText) {
        // Display the suggestion as if the user typed it
        displayMessage(suggestionText, 'user');

        // Send the suggestion to the backend
        sendSuggestionToBackend(suggestionText);
    }

    /**
     * Sends a suggestion text to the backend
     * @param {string} messageText - The suggestion text to send
     */
    async function sendSuggestionToBackend(messageText) {
        if (!messageText) return; // Should not happen with buttons

        console.log('Sending suggestion to backend:', { sessionId, message: messageText });
        showLoading(true); // Show loading indicator

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: sessionId,
                    message: messageText // Send the suggestion text
                })
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({ detail: "Unknown server error" }));
                console.error('API Error Response:', errorData);
                throw new Error(`Server error: ${response.status} ${response.statusText} - ${errorData.detail || 'No details'}`);
            }

            const data = await response.json();

            if (data.bot_message) {
                displayMessage(data.bot_message, 'bot', data.suggestions); // Pass suggestions
            }
            if (data.books && data.books.length > 0) {
                displayBooks(data.books);
            }
            scrollToBottom();

        } catch (error) {
            console.error('Fetch API Error:', error);
            displayMessage(`Sorry, I encountered an error. Please try again. (${error.message})`, 'bot');
        } finally {
            showLoading(false);
        }
    }

    /**
     * Displays book recommendations in card format
     * @param {Array} books - Array of book objects from the API
     */
    function displayBooks(books) {
        const booksContainer = document.createElement('div');
        booksContainer.classList.add('recommendations-container');

        books.forEach(book => {
            const card = document.createElement('div');
            card.classList.add('recommendation-card');

            // Basic sanitization for description
            const safeDescription = book.description ? book.description.replace(/</g, "&lt;").replace(/>/g, "&gt;") : 'No description available.';
            const truncatedDescription = safeDescription.length > 150 ? 
                                        safeDescription.substring(0, 150) + '...' : 
                                        safeDescription;

            card.innerHTML = `
                <div class="recommendation-content">
                    ${book.thumbnail ? 
                        `<img src="${book.thumbnail}" alt="Cover of ${book.title}" class="book-cover">` : 
                        '<div class="book-cover-placeholder">No Cover</div>'}
                    <div class="book-info">
                        <h3 class="book-title">${book.title || 'No Title'}</h3>
                        <p class="book-author">by ${book.authors ? book.authors.join(', ') : 'Unknown Author'}</p>
                        <p class="book-description">${truncatedDescription}</p>
                        ${book.amazon_link ? 
                            `<a href="${book.amazon_link}" target="_blank" rel="noopener noreferrer" class="book-link">View on Amazon</a>` : 
                            ''}
                    </div>
                </div>
            `;
            
            booksContainer.appendChild(card);
        });

        // Create a container bot message to hold the recommendations
        const recommendationMessage = document.createElement('div');
        recommendationMessage.classList.add('message', 'bot-message', 'recommendations-message');
        recommendationMessage.appendChild(booksContainer);
        
        messageList.appendChild(recommendationMessage);
        scrollToBottom();
    }

    /**
     * Scrolls the message list to show the latest message
     */
    function scrollToBottom() {
        messageList.scrollTop = messageList.scrollHeight;
    }

    /**
     * Shows or hides the loading indicator and disables/enables input elements
     * @param {boolean} isLoading - Whether the app is in a loading state
     */
    function showLoading(isLoading) {
        if (!loadingIndicator) return; // Guard if element doesn't exist
        if (isLoading) {
            loadingIndicator.classList.remove('hidden');
            sendButton.disabled = true; // Disable button while loading
            userInput.disabled = true; // Disable input while loading
        } else {
            loadingIndicator.classList.add('hidden');
            sendButton.disabled = false;
            userInput.disabled = false;
            userInput.focus(); // Return focus to input
        }
    }

    /**
     * Handles sending a message to the backend
     */
    async function sendMessage() {
        const messageText = userInput.value.trim();
        if (!messageText) return; // Don't send empty messages

        displayMessage(messageText, 'user');
        const currentUserInput = userInput.value; // Store value before clearing
        userInput.value = ''; // Clear input field
        adjustTextareaHeight(); // Adjust height after clearing
        showLoading(true); // Show loading indicator

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: sessionId, // Use the generated session ID
                    message: currentUserInput // Send the stored message text
                })
            });

            if (!response.ok) {
                // Handle HTTP errors (like 4xx, 5xx)
                const errorData = await response.json().catch(() => ({ detail: "Unknown server error" })); // Attempt to get error detail
                console.error('API Error Response:', errorData);
                throw new Error(`Server error: ${response.status} ${response.statusText} - ${errorData.detail || 'No details'}`);
            }

            const data = await response.json(); // Parse successful response

            // Display bot's text message with suggestions
            if (data.bot_message) {
                displayMessage(data.bot_message, 'bot', data.suggestions);
            }

            // Check for and display book recommendations
            if (data.books && data.books.length > 0) {
                displayBooks(data.books);
            }

            // Scroll after adding all content
            scrollToBottom();

        } catch (error) {
            console.error('Fetch API Error:', error);
            // Display user-friendly error message in the chat
            displayMessage(`Sorry, I encountered an error. Please try again. (${error.message})`, 'bot');
        } finally {
            showLoading(false); // Hide loading indicator regardless of success/failure
        }
    }

    /**
     * Adjusts the textarea height based on content
     */
    function adjustTextareaHeight() {
        userInput.style.height = 'auto'; // Reset height
        userInput.style.height = (userInput.scrollHeight) + 'px';
    }

    // Event Listeners
    sendButton.addEventListener('click', sendMessage);

    userInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault(); // Prevent default newline insertion
            sendMessage();
        }
    });

    userInput.addEventListener('input', adjustTextareaHeight);

    // Initial setup
    adjustTextareaHeight(); // Initial adjustment in case of saved content
    
    // Display welcome message with initial suggestions
    displayMessage("Hi! I'm here to help you discover your next great read. How can I help?", 'bot', 
        ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]);

    // Focus input field for immediate typing
    userInput.focus();
});