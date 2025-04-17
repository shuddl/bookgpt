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
  // Simulate processing delay
  setTimeout(() => {
    self.postMessage({
      type: 'message_processed',
      result: `Processed: ${message}`
    });
  }, 100);
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
  // Simulate sending analytics
  console.log('Worker tracking event:', event);
  self.postMessage({
    type: 'analytics_tracked',
    success: true
  });
}

// Log worker initialization
console.log('BookGPT Worker Initialized');