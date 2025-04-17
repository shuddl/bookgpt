/**
 * BookGPT API Test Script
 * 
 * This script can be used to verify that a BookGPT API deployment is functioning correctly.
 * It sends a test message to the API and checks the response.
 */

const fetch = require('node-fetch');

// Configuration
const API_URL = process.env.API_URL || 'http://localhost:8005/api/chat';
const SESSION_ID = `test_session_${Date.now()}`;
const TEST_MESSAGE = 'recommend fantasy books';

/**
 * Test the API with a simple message
 */
async function testApi() {
  console.log(`Testing BookGPT API at: ${API_URL}`);
  console.log(`Session ID: ${SESSION_ID}`);
  console.log(`Test message: "${TEST_MESSAGE}"`);
  console.log('-------------------');
  
  try {
    // Send test message
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        user_id: SESSION_ID,
        message: TEST_MESSAGE
      })
    });
    
    if (!response.ok) {
      throw new Error(`API Error: ${response.status} ${response.statusText}`);
    }
    
    const data = await response.json();
    
    // Verify response format
    if (!data.bot_message) {
      console.error('❌ API response missing bot_message field');
      console.error('Response data:', data);
      process.exit(1);
    }
    
    if (!Array.isArray(data.suggestions)) {
      console.error('❌ API response missing suggestions array');
      console.error('Response data:', data);
      process.exit(1);
    }
    
    // Check if we got book recommendations
    const hasBooks = Array.isArray(data.books) && data.books.length > 0;
    
    // Print results
    console.log('✅ API responded successfully!');
    console.log(`✅ Bot message: "${data.bot_message.substring(0, 50)}..."`);
    console.log(`✅ Suggestions: ${data.suggestions.length} provided`);
    
    if (hasBooks) {
      console.log(`✅ Books: ${data.books.length} recommendations`);
      data.books.forEach((book, i) => {
        console.log(`   Book #${i+1}: ${book.title} by ${book.authors ? book.authors.join(', ') : 'Unknown'}`);
      });
    } else {
      console.log('⚠️  No book recommendations in this response (might be normal for initial message)');
    }
    
    console.log('-------------------');
    console.log('API TEST PASSED!');
    
  } catch (error) {
    console.error('❌ API TEST FAILED!');
    console.error(error);
    process.exit(1);
  }
}

// Run the test
testApi();