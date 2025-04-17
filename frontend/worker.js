/**
 * BookGPT Web Worker
 * This worker handles messaging operations between the main thread and background processes
 */

// IMPORTANT: Event listeners for 'message' must be registered synchronously
// during the initial evaluation of the worker script to avoid warnings
self.addEventListener('message', function(e) {
  // Process the message data from the main thread
  const data = e.data;
  
  switch (data.type) {
    case 'process_message':
      // Process user message
      processMessage(data.message);
      break;
    
    case 'fetch_recommendations':
      // Fetch book recommendations
      fetchRecommendations(data.query);
      break;
    
    case 'track_analytics':
      // Track analytics event
      trackAnalytics(data.event);
      break;
      
    default:
      // Send back an error for unknown message types
      self.postMessage({
        type: 'error',
        error: 'Unknown message type'
      });
  }
});

/**
 * Process a user message
 * @param {string} message - The message to process
 */
function processMessage(message) {
  // Simple background processing example
  const result = {
    processed: true,
    length: message.length,
    keywords: extractKeywords(message)
  };
  
  // Send result back to main thread
  self.postMessage({
    type: 'message_processed',
    result: result
  });
}

/**
 * Extract potential keywords from a message
 * @param {string} message - The message to analyze
 * @returns {Array} - Extracted keywords
 */
function extractKeywords(message) {
  const text = message.toLowerCase();
  const keywords = [];
  
  const genreKeywords = [
    'fantasy', 'sci-fi', 'science fiction', 'mystery', 'thriller',
    'horror', 'romance', 'historical', 'fiction', 'non-fiction',
    'biography', 'autobiography', 'young adult', 'children'
  ];
  
  genreKeywords.forEach(keyword => {
    if (text.includes(keyword)) {
      keywords.push(keyword);
    }
  });
  
  return keywords;
}

/**
 * Fetch book recommendations based on a query
 * @param {string} query - The search query
 */
function fetchRecommendations(query) {
  // This would normally make a fetch request to an API
  // But for this example we're just sending back a simulated result
  setTimeout(() => {
    self.postMessage({
      type: 'recommendations_result',
      books: [
        { title: 'Example Book 1', author: 'Author 1' },
        { title: 'Example Book 2', author: 'Author 2' }
      ]
    });
  }, 300);
}

/**
 * Track analytics event
 * @param {Object} event - The event data to track
 */
function trackAnalytics(event) {
  try {
    // Determine where to send the analytics
    let analyticsEndpoint = '';
    
    // Check if we're in WordPress context (via bookGptConfig global)
    if (self.bookGptConfig && self.bookGptConfig.analyticsUrl) {
      analyticsEndpoint = self.bookGptConfig.analyticsUrl;
    } else {
      // Default to standard endpoint - would be configured in a real app
      analyticsEndpoint = '/api/track'; // This would be your analytics endpoint
    }
    
    // For now, just log the event
    console.log('Would track analytics event:', event);
    
    // In a real implementation, we would send this data to the server
    // fetch(analyticsEndpoint, {
    //     method: 'POST', 
    //     headers: {'Content-Type': 'application/json'},
    //     body: JSON.stringify(event)
    // });
    
    self.postMessage({
      type: 'analytics_tracked',
      success: true,
      event: event
    });
  } catch (error) {
    self.postMessage({
      type: 'error',
      error: `Analytics error: ${error.message}`
    });
  }
}

// Log worker initialization
console.log('BookGPT Worker Initialized');