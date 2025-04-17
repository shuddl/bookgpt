/**
 * BookGPT Tracking Script
 * Handles tracking of user interactions with the chat widget and affiliate link conversions
 */
(function($) {
    'use strict';

    // Initialize tracking when DOM is ready
    $(document).ready(function() {
        BookGPTTracking.init();
    });

    // Main tracking object
    var BookGPTTracking = {
        // Session ID for this user
        sessionId: '',
        
        // Initialize tracking
        init: function() {
            // Generate or retrieve session ID
            this.sessionId = this.getSessionId();
            
            // Setup event listeners
            this.setupEventListeners();
            
            console.log('BookGPT Tracking initialized with session ID: ' + this.sessionId);
        },
        
        // Generate a unique session ID or retrieve from storage
        getSessionId: function() {
            // Try to get from sessionStorage first
            var sessionId = sessionStorage.getItem('bookgpt_session_id');
            
            // If not found, create a new one
            if (!sessionId) {
                sessionId = 'session_' + this.generateUniqueId();
                sessionStorage.setItem('bookgpt_session_id', sessionId);
            }
            
            return sessionId;
        },
        
        // Generate a unique ID
        generateUniqueId: function() {
            return Date.now().toString(36) + Math.random().toString(36).substring(2);
        },
        
        // Setup event listeners for tracking
        setupEventListeners: function() {
            var self = this;
            
            // Listen for chat interactions from the widget
            $(window).on('bookgpt_chat_interaction', function(event, data) {
                self.trackChatInteraction(data);
            });
            
            // Listen for book recommendations from the widget
            $(window).on('bookgpt_book_recommendation', function(event, data) {
                self.trackBookRecommendation(data);
            });
            
            // Track clicks on Amazon affiliate links
            $(document).on('click', '.widget-book-link, a[href*="amazon.com"]', function(e) {
                var $link = $(this);
                var bookTitle = $link.data('book-title') || $link.closest('.widget-recommendation-card').find('.widget-book-title').text() || '';
                var bookAuthor = $link.data('book-author') || $link.closest('.widget-recommendation-card').find('.widget-book-author').text() || '';
                var bookISBN = $link.data('book-isbn') || '';
                var amazonId = $link.attr('href').match(/\/([A-Z0-9]{10})($|\?|\/)/) ? $link.attr('href').match(/\/([A-Z0-9]{10})($|\?|\/)/)[1] : '';
                
                self.trackAffiliateClick({
                    book_title: bookTitle,
                    book_author: bookAuthor.replace('by ', ''),
                    book_isbn: bookISBN,
                    amazon_id: amazonId
                });
                
                // Store click data in localStorage for purchase tracking
                self.storeClickData(bookTitle, amazonId);
            });
            
            // Check for purchase completion on page load
            this.checkPurchaseCompletion();
        },
        
        // Track a chat interaction
        trackChatInteraction: function(data) {
            var payload = {
                action: 'bookgpt_track_event',
                nonce: bookgpt_tracking.nonce,
                session_id: this.sessionId,
                user_input: data.userMessage || '',
                bot_response: data.botResponse || '',
                book_title: data.bookTitle || '',
                book_author: data.bookAuthor || '',
                book_isbn: data.bookISBN || ''
            };
            
            $.post(bookgpt_tracking.ajax_url, payload);
        },
        
        // Track a book recommendation
        trackBookRecommendation: function(data) {
            var books = data.books || [];
            var self = this;
            
            // Track each recommended book
            books.forEach(function(book) {
                var payload = {
                    action: 'bookgpt_track_event',
                    nonce: bookgpt_tracking.nonce,
                    session_id: self.sessionId,
                    user_input: data.userQuery || '',
                    bot_response: 'Book recommendation',
                    book_title: book.title || '',
                    book_author: book.author || '',
                    book_isbn: book.isbn || ''
                };
                
                $.post(bookgpt_tracking.ajax_url, payload);
            });
        },
        
        // Track an affiliate link click
        trackAffiliateClick: function(data) {
            var payload = {
                action: 'bookgpt_track_conversion',
                nonce: bookgpt_tracking.nonce,
                session_id: this.sessionId,
                book_title: data.book_title || '',
                book_author: data.book_author || '',
                book_isbn: data.book_isbn || '',
                amazon_id: data.amazon_id || '',
                conversion_type: 'click',
                value: 0
            };
            
            $.post(bookgpt_tracking.ajax_url, payload);
        },
        
        // Store click data for later purchase tracking
        storeClickData: function(bookTitle, amazonId) {
            if (!bookTitle || !amazonId) {
                return;
            }
            
            var clickTime = new Date().getTime();
            var clickData = {
                book_title: bookTitle,
                amazon_id: amazonId,
                timestamp: clickTime,
                session_id: this.sessionId
            };
            
            // Store in localStorage
            localStorage.setItem('bookgpt_last_click', JSON.stringify(clickData));
            
            // Set cookie for cross-domain tracking (Amazon to this site)
            this.setCookie('bookgpt_click_id', amazonId, 30);
            this.setCookie('bookgpt_click_book', encodeURIComponent(bookTitle), 30);
            this.setCookie('bookgpt_click_session', this.sessionId, 30);
        },
        
        // Check if this is a purchase completion page
        checkPurchaseCompletion: function() {
            // Look for Amazon purchase thank you page indicators
            var isPurchasePage = window.location.href.indexOf('thank-you') > -1 || 
                                 window.location.href.indexOf('order-confirmation') > -1 ||
                                 $('h1:contains("Thank you for your purchase")').length > 0;
                                 
            // Check for Amazon order confirmation page
            if (window.location.href.indexOf('amazon.com') > -1 && 
                (window.location.href.indexOf('/gp/buy/thankyou') > -1 || 
                 $('h1:contains("Thank you")').length > 0)) {
                isPurchasePage = true;
            }
            
            if (isPurchasePage) {
                this.processPurchaseCompletion();
            }
        },
        
        // Process purchase completion
        processPurchaseCompletion: function() {
            // Get last click data
            var lastClickData = localStorage.getItem('bookgpt_last_click');
            
            if (!lastClickData) {
                // Check cookies as fallback
                var amazonId = this.getCookie('bookgpt_click_id');
                var bookTitle = this.getCookie('bookgpt_click_book');
                var sessionId = this.getCookie('bookgpt_click_session');
                
                if (amazonId && bookTitle) {
                    lastClickData = {
                        amazon_id: amazonId,
                        book_title: decodeURIComponent(bookTitle),
                        session_id: sessionId || this.sessionId
                    };
                } else {
                    return; // No data to track
                }
            } else {
                lastClickData = JSON.parse(lastClickData);
            }
            
            // Check if purchase already tracked
            if (localStorage.getItem('bookgpt_purchase_tracked_' + lastClickData.amazon_id)) {
                return; // Don't track duplicate purchases
            }
            
            // Track the purchase conversion
            var payload = {
                action: 'bookgpt_track_conversion',
                nonce: bookgpt_tracking.nonce,
                session_id: lastClickData.session_id || this.sessionId,
                book_title: lastClickData.book_title || '',
                amazon_id: lastClickData.amazon_id || '',
                conversion_type: 'purchase',
                value: this.estimatePurchaseValue(lastClickData.book_title)
            };
            
            $.post(bookgpt_tracking.ajax_url, payload);
            
            // Mark as tracked to prevent duplicates
            localStorage.setItem('bookgpt_purchase_tracked_' + lastClickData.amazon_id, 'true');
        },
        
        // Estimate purchase value based on book genre/title
        estimatePurchaseValue: function(bookTitle) {
            // Default values by book type (estimated revenue after Amazon's cut)
            var defaultValue = 2.00;  // Default commission for books
            var kindleValue = 0.70;   // Kindle books typically less
            var hardcoverValue = 3.50; // Hardcovers typically more
            
            // Very basic estimation logic
            if (!bookTitle) {
                return defaultValue;
            }
            
            var lowerTitle = bookTitle.toLowerCase();
            
            if (lowerTitle.indexOf('kindle') > -1 || lowerTitle.indexOf('ebook') > -1) {
                return kindleValue;
            } else if (lowerTitle.indexOf('hardcover') > -1 || lowerTitle.indexOf('hardback') > -1) {
                return hardcoverValue;
            }
            
            return defaultValue;
        },
        
        // Helper: Set cookie
        setCookie: function(name, value, days) {
            var expires = '';
            
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            
            document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
        },
        
        // Helper: Get cookie
        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') {
                    c = c.substring(1, c.length);
                }
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            
            return null;
        },
        
        // Helper: Delete cookie
        deleteCookie: function(name) {
            this.setCookie(name, '', -1);
        }
    };

})(jQuery);