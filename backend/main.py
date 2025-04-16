from fastapi import FastAPI
import uvicorn
from pydantic import BaseModel
from typing import List, Optional, Dict, Any
from fastapi.middleware.cors import CORSMiddleware
import asyncio
import random
import os
import openai
import aiohttp
import json
from dotenv import load_dotenv

# Load environment variables from .env file
load_dotenv()

# Initialize OpenAI client
openai_api_key = os.getenv("OPENAI_API_KEY")
if not openai_api_key:
    print("WARNING: OPENAI_API_KEY environment variable not set.")
    # Consider raising an error in a real app: raise ValueError("Missing OpenAI API Key")
    client = None  # Or handle appropriately
else:
    client = openai.AsyncOpenAI(api_key=openai_api_key)

# Initialize Google Books API key
google_books_api_key = os.getenv("GOOGLE_BOOKS_API_KEY")
if not google_books_api_key:
    print("WARNING: GOOGLE_BOOKS_API_KEY environment variable not set.")
    # Consider raising an error in a real app or disabling functionality

class ChatRequest(BaseModel):
    user_id: str
    message: str

class ChatResponse(BaseModel):
    user_id: str
    bot_message: str
    suggestions: List[str] = []
    books: List[dict] = []  # Placeholder for book data

app = FastAPI()

# In-memory session state management
session_states: Dict[str, Dict[str, Any]] = {}

# Configure CORS
origins = [
    "http://localhost",  # Add other origins if needed e.g. frontend deployment URL
    "http://localhost:8080",
    "http://localhost:8081",
    "null"  # Allow local file:// origin
]

app.add_middleware(
    CORSMiddleware,
    allow_origins=origins,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

async def process_nlp(text: str, current_stage: str) -> dict:
    """
    Placeholder for NLP processing (Intent Recognition & Entity Extraction).
    Currently uses basic keywords, will be replaced by an LLM call.
    Input: user message, current conversation stage.
    Output: dict e.g., {'intent': 'REQUEST_RECOMMENDATION', 'entities': {'genre': 'sci-fi'}, 'refined_message': '...'}
    """
    print(f"NLP Placeholder: Processing text: '{text}' in stage: {current_stage}")
    intent = "UNKNOWN"
    entities = {}
    # --- Simple Keyword Logic (Replace with LLM in Prompt 9/Integration) ---
    lower_text = text.lower()
    if any(word in lower_text for word in ["book", "recommend", "read", "suggest"]):
        intent = "REQUEST_RECOMMENDATION"
    elif any(word in lower_text for word in ["like", "similar", "another"]):
        intent = "REQUEST_SIMILAR" # Could be used later
    elif any(word in lower_text for word in ["hi", "hello", "hey"]):
         intent = "GREETING"

    # Basic entity extraction (very rudimentary)
    if "sci-fi" in lower_text or "science fiction" in lower_text:
        entities["genre"] = "Science Fiction"
    if "fantasy" in lower_text:
        entities["genre"] = "Fantasy"
    if "thriller" in lower_text:
        entities["genre"] = "Thriller"
    # --- End Simple Logic ---

    nlp_result = {"intent": intent, "entities": entities, "refined_message": text} # Pass original message for now
    print(f"NLP Placeholder: Mock result: {nlp_result}")
    return nlp_result

@app.get("/")
async def root():
    return {"message": "Book Recommendation Bot API"}

@app.post("/api/chat", response_model=ChatResponse)
async def handle_chat(request: ChatRequest):
    print(f"Received: user_id={request.user_id}, message='{request.message}'")  # Basic logging
    
    # Retrieve/Initialize State
    session_id = request.user_id
    user_state = session_states.get(session_id, {"history": [], "stage": "INIT", "details": {}})
    current_stage = user_state.get("stage", "INIT")
    print(f"User {session_id} - Current Stage: {current_stage}, State: {user_state}")
    
    # Append user message for context
    user_state["history"].append({"role": "user", "content": request.message})
    # Keep history concise for MVP if needed
    user_state["history"] = user_state["history"][-10:]  # Keep last 10 turns max for example
    
    # Process user message with NLP placeholder
    nlp_result = await process_nlp(request.message, current_stage)
    intent = nlp_result.get("intent", "UNKNOWN")
    entities = nlp_result.get("entities", {})
    refined_message = nlp_result.get("refined_message")

    print(f"NLP Result - Intent: {intent}, Entities: {entities}")

    # Initialize variables for response
    bot_message = ""
    response_suggestions = []
    
    # Core Conversation Logic - State Machine
    if current_stage == "INIT" or intent == "GREETING":
        # Initial greeting or explicit greeting intent
        bot_message = "Hi! I'm here to help you discover your next great read. How can I help? You can tell me about genres you like, authors, or a book you recently enjoyed."
        response_suggestions = ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]
        user_state["stage"] = "AWAITING_PREFERENCES"
        
    elif current_stage == "AWAITING_PREFERENCES" or intent == "REQUEST_RECOMMENDATION":
        # User has provided preferences or explicitly requested recommendations
        bot_message = f"Okay, searching for recommendations based on: '{request.message}'..."
        
        # Store preferences from this interaction
        user_state["details"]["preferences_text"] = request.message
        user_state["details"]["nlp_entities"] = entities
        
        # Call ChatGPT to get recommendation ideas
        recommendation_ideas = await get_chatgpt_recommendations(
            preferences=entities or {"raw_query": request.message}, 
            history=user_state["history"],
            max_recommendations=5
        )
        
        if recommendation_ideas:
            book_results = []
            
            # Process each recommendation idea
            for idea in recommendation_ideas:
                print(f"Processing recommendation idea: {idea}")
                # Call mocked search function
                search_results = await search_google_books(query=idea, max_results=1)
                
                if search_results:
                    book_id = search_results[0].get("id")
                    if book_id:
                        # Get detailed information for the book
                        book_details = await get_book_details_by_id(book_id)
                        if book_details:
                            # Add mock affiliate link
                            book_details["amazon_link"] = f"https://www.amazon.com/dp/{book_details.get('isbn13', '')}?tag={os.getenv('AMAZON_ASSOCIATE_TAG', 'bookgpt-20')}"
                            book_results.append(book_details)
            
            if book_results:
                bot_message = "Here are a few recommendations I found based on your request:"
                user_state["details"]["last_recommendations"] = book_results  # Store for later reference
                response_suggestions = ["Tell me more about #1", "Show different recommendations", "Start Over"]
                user_state["stage"] = "SHOWING_RECOMMENDATIONS"
            else:
                # ChatGPT gave ideas but search/details failed
                bot_message = "I came up with some ideas, but couldn't find specific book details right now. Could you try rephrasing your request?"
                response_suggestions = ["Try Fantasy genre", "Suggest popular Sci-Fi", "Recommend Thriller books"]
        else:
            # ChatGPT failed to generate recommendations
            bot_message = "I couldn't come up with recommendations for that right now. Please try again with different keywords or genres."
            response_suggestions = ["Try Fantasy genre", "Suggest popular books", "Recommend Thriller books"]

    elif current_stage == "SHOWING_RECOMMENDATIONS":
        # Handle follow-up requests after showing recommendations
        lower_message = request.message.lower()
        
        if "more" in lower_message or "detail" in lower_message or "#1" in lower_message:
            bot_message = "I can tell you more once the detailed view is built! For now, here's a summary of what I recommended earlier."
            response_suggestions = ["Show different recommendations", "Start Over"]
            
        elif "different" in lower_message or "other" in lower_message or "new" in lower_message:
            bot_message = "Okay, what else are you looking for? Please tell me about genres, authors, or books you enjoy."
            response_suggestions = ["Fantasy recommendations", "Sci-Fi books", "Popular Thrillers"]
            user_state["stage"] = "AWAITING_PREFERENCES"
            
        elif "start" in lower_message or "reset" in lower_message or "over" in lower_message:
            # Reset the conversation
            user_state = {"history": [{"role": "user", "content": request.message}], "stage": "INIT", "details": {}}
            bot_message = "Let's start over! How can I help you find your next great read?"
            response_suggestions = ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]
            user_state["stage"] = "AWAITING_PREFERENCES"
            
        else:
            bot_message = "You can ask for more details about these books, different recommendations, or start over."
            response_suggestions = ["Tell me more about #1", "Show different recommendations", "Start Over"]
    
    else:
        # Handle unknown intents or stages
        bot_message = "Sorry, I'm focused on book recommendations right now. How can I help you find a book?"
        response_suggestions = ["Suggest Fantasy Books", "Recommend Sci-Fi", "Books like The Hobbit"]
        user_state["stage"] = "AWAITING_PREFERENCES"
    
    # Prepare final response
    final_books_data = user_state["details"].get("last_recommendations", []) if user_state["stage"] == "SHOWING_RECOMMENDATIONS" else []
    
    # Append bot response to history
    user_state["history"].append({"role": "assistant", "content": bot_message})
    
    # Clean up sensitive temporary data if needed before saving state
    # user_state["details"].pop("temp_data", None)
    
    # Save updated state
    session_states[session_id] = user_state
    print(f"User {session_id} - Saving New State: {user_state}")
    
    return ChatResponse(
        user_id=session_id,
        bot_message=bot_message,
        suggestions=response_suggestions,
        books=final_books_data  # Send book data only when showing recommendations
    )

# --- Mocked Google Books API Interface ---
# These functions simulate calls to the Google Books API.
# Their internal logic will be replaced with actual API calls in Prompt 12.

async def search_google_books(query: str, max_results: int = 5) -> List[Dict[str, Any]]:
    """
    Searches the Google Books API v1 for books matching a query.
    Returns a list of book summaries (id, title, authors).
    """
    if not google_books_api_key:
        print("Error: Google Books API key not configured.")
        return []

    search_url = "https://www.googleapis.com/books/v1/volumes"
    params = {
        'q': query,
        'key': google_books_api_key,
        'maxResults': min(max_results, 40),  # Google API max is 40
        'projection': 'lite'  # Request less data for search results
    }
    print(f"Google Books API: Searching for '{query}'")

    try:
        async with aiohttp.ClientSession() as session:
            async with session.get(search_url, params=params) as response:
                response.raise_for_status()  # Raise exception for bad status codes (4xx or 5xx)
                data = await response.json()

                items = data.get('items', [])
                results = []
                for item in items:
                    volume_info = item.get('volumeInfo', {})
                    results.append({
                        'id': item.get('id'),
                        'title': volume_info.get('title'),
                        'authors': volume_info.get('authors', []),  # Authors is a list
                    })
                print(f"Google Books API: Found {len(results)} results.")
                return results[:max_results]  # Limit to requested number

    except aiohttp.ClientResponseError as e:
        print(f"Google Books API Error (Search): HTTP Status {e.status} - {e.message}")
    except aiohttp.ClientConnectionError as e:
        print(f"Google Books API Error (Search): Connection Error - {e}")
    except json.JSONDecodeError:
        print(f"Google Books API Error (Search): Could not decode JSON response")
    except Exception as e:
        print(f"Google Books API Error (Search): An unexpected error occurred: {e}")

    return []  # Return empty list on error

async def get_book_details_by_id(book_id: str) -> Optional[Dict[str, Any]]:
    """
    Fetches detailed information for a specific book ID from Google Books API v1.
    Returns a dictionary with details or None if not found or error.
    """
    if not google_books_api_key:
        print("Error: Google Books API key not configured.")
        return None
    if not book_id:  # Prevent calling with empty ID
        return None

    detail_url = f"https://www.googleapis.com/books/v1/volumes/{book_id}"
    params = {'key': google_books_api_key}
    print(f"Google Books API: Getting details for book_id '{book_id}'")

    try:
        async with aiohttp.ClientSession() as session:
            async with session.get(detail_url, params=params) as response:
                response.raise_for_status()
                data = await response.json()

                volume_info = data.get('volumeInfo', {})
                isbn13 = None
                identifiers = volume_info.get('industryIdentifiers', [])
                for identifier in identifiers:
                    if identifier.get('type') == 'ISBN_13':
                        isbn13 = identifier.get('identifier')
                        break  # Prefer ISBN_13

                details = {
                    'id': data.get('id'),
                    'title': volume_info.get('title'),
                    'authors': volume_info.get('authors', []),
                    'description': volume_info.get('description'),
                    'thumbnail': volume_info.get('imageLinks', {}).get('thumbnail') or \
                                volume_info.get('imageLinks', {}).get('smallThumbnail'),  # Get best available thumbnail
                    'isbn13': isbn13,
                    'categories': volume_info.get('categories', [])
                    # Add other fields if needed e.g., publishedDate, averageRating etc.
                }
                print(f"Google Books API: Details found for {book_id}.")
                return details

    except aiohttp.ClientResponseError as e:
        # Specifically handle 404 Not Found if needed
        if e.status == 404:
            print(f"Google Books API: Book ID '{book_id}' not found (404).")
        else:
            print(f"Google Books API Error (Details): HTTP Status {e.status} - {e.message}")
    except aiohttp.ClientConnectionError as e:
        print(f"Google Books API Error (Details): Connection Error - {e}")
    except json.JSONDecodeError:
        print(f"Google Books API Error (Details): Could not decode JSON response")
    except Exception as e:
        print(f"Google Books API Error (Details): An unexpected error occurred: {e}")

    return None  # Return None if details not found or error occurs

# --- End Mocked Interface ---

async def get_chatgpt_recommendations(
    preferences: Dict[str, Any],
    history: List[Dict[str, str]],
    max_recommendations: int = 5
) -> List[str]:
    """
    Calls ChatGPT to get book recommendation ideas based on user preferences and history.
    Input:
        preferences: Dict containing extracted entities like {'genre': 'sci-fi', 'liked_book': 'Dune'}
        history: List of recent conversation turns [{'role': 'user', 'content': '...'}, ...]
        max_recommendations: How many distinct book ideas to ask for.
    Output:
        List of strings, where each string is a book title or title/author pair.
        Returns empty list on error.
    """
    if not client:  # Handle missing API key case
        print("Error: OpenAI client not initialized.")
        return []

    print(f"LLM: Getting recommendations based on preferences: {preferences}")

    # --- Construct Prompt ---
    # Basic example, refine based on actual preferences captured
    prompt_lines = [
        f"You are a helpful book recommendation assistant. A user has the following preferences: {preferences}. ",
        f"Based ONLY on these preferences, suggest {max_recommendations} specific book titles (and authors if possible) that they might enjoy. ",
        "Consider variety. Do not repeat books mentioned in the brief history if provided. ",
        "Format your response as a simple list, one book per line, like:\nTitle by Author\nAnother Title by Author",
        # Optional: Add conversation history for context if needed
        # f"Brief recent conversation context:\n{history}"
    ]
    system_prompt = "You are a helpful book recommendation assistant providing specific titles and authors."
    user_prompt = "\n".join(prompt_lines)

    messages = [
        {"role": "system", "content": system_prompt},
        {"role": "user", "content": user_prompt}
    ]
    # You could prepend relevant history turns to messages here if desired

    print(f"LLM: Sending prompt to ChatGPT: {user_prompt}")
    # --- Call OpenAI API ---
    try:
        response = await client.chat.completions.create(
            model="gpt-3.5-turbo",  # Or "gpt-4o" etc. - Make configurable?
            messages=messages,
            temperature=0.7,  # Allow some creativity
            max_tokens=150 * max_recommendations,  # Estimate tokens needed
            n=1,  # We want one response list
            stop=None
        )
        content = response.choices[0].message.content
        print(f"LLM Raw Response: {content}")

        # --- Parse Response ---
        # Simple parsing: split by newline, remove empty lines
        recommendations = [line.strip() for line in content.strip().split('\n') if line.strip()]
        print(f"LLM Parsed Recommendations: {recommendations}")
        return recommendations[:max_recommendations]  # Ensure we don't exceed max

    except openai.APIError as e:
        print(f"LLM Error: OpenAI API returned an API Error: {e}")
    except Exception as e:
        print(f"LLM Error: An unexpected error occurred: {e}")

    return []  # Return empty list on error

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)