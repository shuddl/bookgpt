/**
 * BookGPT Analytics Tracking Script
 * Handles tracking book recommendations, interactions, and conversions
 */
(function($) {
    'use strict';
    
    // Store click handlers to avoid duplicates
    const trackingHandlers = {};
    
    $(document).ready(function() {
        // Initialize tracking after a delay to ensure the widget is loaded
        setTimeout(initializeTracking, 2000);
    });
    
    /**
     * Initialize analytics tracking
     */
    function initializeTracking() {
        // Check if analytics are enabled
        if (!window.bookGptConfig || !window.bookGptConfig.enableAnalytics) {
            console.log('BookGPT: Analytics tracking disabled');
            return;
        }
        
        // Track page view
        trackEvent({
            type: 'page_view',
            page: window.location.href,
            title: document.title
        });
        
        // Track widget interactions
        attachChatInteractionTrackers();
        
        // Track book clicks
        attachBookClickTrackers();
        
        console.log('BookGPT: Analytics tracking initialized');
    }
    
    /**
     * Attach event handlers to track chat interactions
     */
    function attachChatInteractionTrackers() {
        const widgetContainer = document.getElementById('book-chat-widget-container');
        if (!widgetContainer) {
            // Widget not yet initialized, try again later
            setTimeout(attachChatInteractionTrackers, 1000);
            return;
        }
        
        // Track send button clicks
        const sendButton = widgetContainer.querySelector('#widget-send-button');
        if (sendButton && !trackingHandlers.sendButton) {
            trackingHandlers.sendButton = true;
            sendButton.addEventListener('click', function() {
                const messageInput = widgetContainer.querySelector('#widget-user-input');
                if (messageInput && messageInput.value.trim()) {
                    trackEvent({
                        type: 'user_message',
                        content_length: messageInput.value.trim().length,
                        timestamp: new Date().toISOString()
                    });
                }
            });
        }
        
        // Track suggestion button clicks
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            // Check for newly added suggestion buttons
                            const suggestionButtons = node.querySelectorAll('.widget-suggestion-button');
                            suggestionButtons.forEach(function(button) {
                                if (!button.hasAttribute('data-tracking')) {
                                    button.setAttribute('data-tracking', 'true');
                                    button.addEventListener('click', function() {
                                        trackEvent({
                                            type: 'suggestion_click',
                                            suggestion: button.textContent,
                                            timestamp: new Date().toISOString()
                                        });
                                    });
                                }
                            });
                            
                            // Check for book links
                            const bookLinks = node.querySelectorAll('.widget-book-link');
                            bookLinks.forEach(function(link) {
                                if (!link.hasAttribute('data-tracking')) {
                                    link.setAttribute('data-tracking', 'true');
                                    link.addEventListener('click', function(e) {
                                        const bookTitle = link.closest('.widget-recommendation-card')?.querySelector('.widget-book-title')?.textContent || 'Unknown Book';
                                        const bookAuthor = link.closest('.widget-recommendation-card')?.querySelector('.widget-book-author')?.textContent || 'Unknown Author';
                                        
                                        trackConversion({
                                            type: 'book_click',
                                            book_title: bookTitle,
                                            book_author: bookAuthor,
                                            amazon_link: link.href,
                                            timestamp: new Date().toISOString()
                                        });
                                    });
                                }
                            });
                        }
                    });
                }
            });
        });
        
        // Start observing the widget for changes
        observer.observe(widgetContainer, { 
            childList: true, 
            subtree: true 
        });
    }
    
    /**
     * Attach click trackers to book recommendation links
     */
    function attachBookClickTrackers() {
        // This is already handled by the mutation observer in attachChatInteractionTrackers
    }
    
    /**
     * Track an event via AJAX to WordPress
     * @param {Object} eventData - The event data to track
     */
    function trackEvent(eventData) {
        // Add session ID and page info
        eventData.session_id = getSessionId();
        eventData.page_url = window.location.href;
        eventData.referrer = document.referrer || null;
        
        // Send event to WordPress via AJAX
        $.ajax({
            url: window.bookGptConfig.analyticsUrl,
            method: 'POST',
            data: {
                action: 'bookgpt_track_analytics',
                nonce: window.bookGptConfig.nonce,
                event_data: JSON.stringify(eventData)
            },
            success: function(response) {
                if (response.success) {
                    console.log('BookGPT: Event tracked successfully');
                } else {
                    console.error('BookGPT: Failed to track event', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('BookGPT: Error tracking event', error);
            }
        });
    }
    
    /**
     * Track a conversion event (like a book click)
     * @param {Object} conversionData - The conversion data
     */
    function trackConversion(conversionData) {
        // Add session ID and page info
        conversionData.session_id = getSessionId();
        conversionData.page_url = window.location.href;
        
        // Send conversion to WordPress via AJAX
        $.ajax({
            url: window.bookGptConfig.analyticsUrl,
            method: 'POST',
            data: {
                action: 'bookgpt_track_conversion',
                nonce: window.bookGptConfig.nonce,
                conversion_data: JSON.stringify(conversionData)
            },
            success: function(response) {
                if (response.success) {
                    console.log('BookGPT: Conversion tracked successfully');
                } else {
                    console.error('BookGPT: Failed to track conversion', response);
                }
            },
            error: function(xhr, status, error) {
                console.error('BookGPT: Error tracking conversion', error);
            }
        });
    }
    
    /**
     * Get or create a session ID for tracking
     * @returns {string} - The session ID
     */
    function getSessionId() {
        let sessionId = sessionStorage.getItem('bookgpt_session_id');
        
        if (!sessionId) {
            sessionId = 'wp_session_' + Date.now() + '_' + Math.random().toString(36).substring(2, 9);
            sessionStorage.setItem('bookgpt_session_id', sessionId);
        }
        
        return sessionId;
    }
})(jQuery);