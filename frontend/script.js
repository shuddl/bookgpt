/**
 * Book Recommendation Chatbot - Core JavaScript
 * Handles user interactions, message display, and prepares for API integration
 */

document.addEventListener('DOMContentLoaded', () => {
    // Backend API URL - Determine environment dynamically
    // In development, we might be on http://localhost:8081 for frontend and need to point to backend port 8005
    // In production on Vercel, we can use relative path which gets routed via vercel.json
    const isLocalhost = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
    const API_URL = isLocalhost ? 'http://localhost:8005/api/chat' : '/api/chat';
    console.log(`Using API URL: ${API_URL} on host: ${window.location.hostname}`); // Enhanced debugging

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
     * Scrolls an element into view with smooth animation
     * @param {HTMLElement} element - The element to scroll into view
     */
    function scrollElementIntoView(element) {
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'end', inline: 'nearest' });
        } else { // Fallback for safety
            messageList.scrollTop = messageList.scrollHeight;
        }
    }

    /**
     * Displays a message in the chat interface
     * @param {string} text - The message text
     * @param {string} sender - 'user' or 'bot'
     * @param {Array} suggestions - Optional array of suggestion buttons to show
     * @returns {HTMLElement} The created message element
     */
    function displayMessage(text, sender, suggestions = []) {
        const messageDiv = document.createElement('div');
        messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');

        // Create content container
        const contentDiv = document.createElement('div');
        contentDiv.classList.add('message-content');
        
        // Use innerHTML for bot messages to render HTML formatting
        // User messages should still use innerText for security
        if (sender === 'bot') {
            contentDiv.innerHTML = text; // Use innerHTML for bot messages to render HTML tags
        } else {
            contentDiv.innerText = text; // Use innerText for user messages to prevent injection
        }
        
        messageDiv.appendChild(contentDiv);

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
        return messageDiv;
    }

    /**
     * Handles click events on suggestion buttons
     * @param {string} suggestionText - The text of the clicked suggestion
     */
    function handleSuggestionClick(suggestionText) {
        // Display the suggestion as if the user typed it
        const userMessageElement = displayMessage(suggestionText, 'user');
        scrollElementIntoView(userMessageElement);
        
        // Clear any existing inputs if there are any
        userInput.value = '';
        
        // Process the suggestion based on its content
        if (suggestionText.match(/Tell me more about #(\d+)/i)) {
            // This is a request for more details about a specific book
            const bookIndex = parseInt(suggestionText.match(/Tell me more about #(\d+)/i)[1], 10) - 1;
            console.log(`Requesting details for book index: ${bookIndex}`);
        } 
        
        // Send all suggestions to backend - no special case filtering
        sendMessageToBackend(suggestionText);
    }

    /**
     * Sends a message to the backend (works for both typed messages and suggestions)
     * @param {string} messageText - The message text to send
     */
    async function sendMessageToBackend(messageText) {
        if (!messageText) return; // Don't send empty messages

        console.log('Sending message to backend:', { sessionId, message: messageText });
        showLoading(true); // Show loading indicator

        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_id: sessionId,
                    message: messageText
                })
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
                lastAddedElement = displayBooks(data.books);
            }
            
            // If no message or books were returned, show a default response
            if (!lastAddedElement) {
                lastAddedElement = displayMessage("I'm looking into that for you. What other topics are you interested in?", 'bot', 
                    ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit", "Mystery Novels"]);
            }
            
            // Scroll to the last added element
            scrollElementIntoView(lastAddedElement);

        } catch (error) {
            console.error('Fetch API Error:', error);
            const errorElement = displayMessage(`Sorry, I encountered an error. Please try again.`, 'bot', 
                ["Suggest Fantasy Books", "Recommend Sci-Fi", "Mystery Novels", "Contemporary Fiction"]);
            scrollElementIntoView(errorElement);
        } finally {
            showLoading(false);
        }
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
            
            let lastAddedElement;

            if (data.bot_message) {
                lastAddedElement = displayMessage(data.bot_message, 'bot', data.suggestions);
            }
            
            if (data.books && data.books.length > 0) {
                lastAddedElement = displayBooks(data.books);
            }
            
            // Scroll to the last added element
            scrollElementIntoView(lastAddedElement);

        } catch (error) {
            console.error('Fetch API Error:', error);
            const errorElement = displayMessage(`Sorry, I encountered an error. Please try again. (${error.message})`, 'bot');
            scrollElementIntoView(errorElement);
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
        // Set a default Amazon Associate Tag
        const amazonTag = 'bookgpt-20';
        
        const recommendationsMessage = document.createElement('div');
        recommendationsMessage.classList.add('message', 'bot-message', 'recommendations-message');
        
        const booksContainer = document.createElement('div');
        booksContainer.classList.add('recommendations-container');
        recommendationsMessage.appendChild(booksContainer);

        books.forEach((book, index) => {
            const card = document.createElement('div');
            card.classList.add('recommendation-card');
            
            // Process description to handle HTML properly
            let processedDescription = 'No description available.';
            
            if (book.description) {
                // Create a temporary div to safely render HTML
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = book.description;
                
                // Get text content for length checking
                const textContent = tempDiv.textContent || tempDiv.innerText;
                
                // Truncate if needed
                if (textContent.length > 150) {
                    // For truncated content, use plain text to avoid broken HTML
                    processedDescription = textContent.substring(0, 150) + '...';
                } else {
                    // If it's short enough, use the original HTML
                    processedDescription = book.description;
                }
            }

            // Create Amazon link with proper fallback
            let amazonLink = null;
            
            // First check if the book has a direct amazon_link property
            if (book.amazon_link && book.amazon_link.includes('amazon.com')) {
                amazonLink = book.amazon_link;
            } 
            // If we have an ISBN13, use search URL instead of direct product link
            else if (book.isbn13) {
                // Use search URL with ISBN which is more reliable than direct product links
                amazonLink = `https://www.amazon.com/s?k=${book.isbn13}&tag=${amazonTag}`;
            }
            // If all else fails, search by title and author
            else if (book.title) {
                // Create a search query with title and author if available
                let searchQuery = encodeURIComponent(book.title);
                if (book.authors && book.authors.length > 0) {
                    searchQuery += `+${encodeURIComponent(book.authors[0])}`;
                }
                amazonLink = `https://www.amazon.com/s?k=${searchQuery}&tag=${amazonTag}`;
            }
            
            // Create the HTML content for the card
            const cardHTML = `
                <div class="recommendation-content">
                    ${book.thumbnail ? 
                        `<img src="${book.thumbnail}" alt="Cover of ${book.title}" class="book-cover">` : 
                        '<div class="book-cover-placeholder">No Cover</div>'}
                    <div class="book-info">
                        <h3 class="book-title">${book.title || 'No Title'}</h3>
                        <p class="book-author">by ${book.authors ? book.authors.join(', ') : 'Unknown Author'}</p>
                        
                        ${book.reasoning ? 
                            `<div class="book-reasoning">
                                <h4>Why This Book?</h4>
                                <p>${book.reasoning}</p>
                            </div>` : 
                            ''}
                        
                        <div class="book-description">${processedDescription}</div>
                        ${amazonLink ? 
                            `<a href="${amazonLink}" target="_blank" rel="noopener noreferrer" class="book-link">View on Amazon</a>` : 
                            ''}
                    </div>
                </div>
            `;
            
            card.innerHTML = cardHTML;
            booksContainer.appendChild(card);
            
            // Debug logging
            console.log(`Book #${index + 1} - Title: ${book.title}, Amazon Link: ${amazonLink || 'None'}`);
        });

        messageList.appendChild(recommendationsMessage);
        return recommendationsMessage;
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

        const userMessageElement = displayMessage(messageText, 'user');
        scrollElementIntoView(userMessageElement);
        
        const currentUserInput = userInput.value; // Store value before clearing
        userInput.value = ''; // Clear input field
        adjustTextareaHeight(); // Adjust height after clearing
        
        // Use our unified message sending function
        sendMessageToBackend(currentUserInput);
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
    
    // Send an initial empty message to get the welcome message from the backend
    fetch(API_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            user_id: sessionId,
            message: ""  // Empty message will trigger greeting response
        })
    })
    .then(response => response.json())
    .then(data => {
        let initialElement;
        if (data.bot_message) {
            initialElement = displayMessage(data.bot_message, 'bot', data.suggestions);
            scrollElementIntoView(initialElement);
        }
    })
    .catch(error => {
        console.error('Error getting initial greeting:', error);
        // Fallback greeting if backend is unavailable
        const fallbackElement = displayMessage("Hi! I'm here to help you discover your next great read. How can I help?", 'bot', 
            ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]);
        scrollElementIntoView(fallbackElement);
    });

    // Focus input field for immediate typing
    userInput.focus();
});