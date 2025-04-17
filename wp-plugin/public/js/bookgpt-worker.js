/**
 * BookGPT Tracking Worker
 * Web Worker for handling tracking operations in the background
 */

// IMPORTANT: Register the message event listener immediately during initial script evaluation
// This prevents the "Event handler of 'message' event must be added on initial evaluation" warning
self.addEventListener('message', function(e) {
    const data = e.data;
    
    switch (data.type) {
        case 'track_event':
            trackEvent(data);
            break;
            
        case 'track_conversion':
            trackConversion(data);
            break;
            
        default:
            self.postMessage({
                type: 'error',
                error: 'Unknown message type: ' + data.type
            });
    }
});

/**
 * Track an analytics event
 * @param {Object} data - The event data
 */
function trackEvent(data) {
    // In a real implementation, this might send data to an analytics service
    // or perform other background processing
    console.log('Worker processing event:', data);
    
    // Simulate processing delay
    setTimeout(() => {
        self.postMessage({
            type: 'analytics_tracked',
            success: true,
            eventType: data.eventType,
            sessionId: data.sessionId
        });
    }, 50);
}

/**
 * Track a conversion event
 * @param {Object} data - The conversion data
 */
function trackConversion(data) {
    // In a real implementation, this might send data to a conversion tracking service
    console.log('Worker processing conversion:', data);
    
    // Simulate processing delay
    setTimeout(() => {
        self.postMessage({
            type: 'analytics_tracked',
            success: true,
            conversionType: data.eventType,
            sessionId: data.sessionId
        });
    }, 50);
}

// Log initialization
console.log('BookGPT Tracking Worker initialized');