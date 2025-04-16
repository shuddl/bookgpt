Guidance for AI Collaboration (AI Book Recommendation Chatbot MVP)

## Project Goal

Develop a **Minimum Viable Product (MVP)** web application for an AI-powered book recommendation chatbot. The chatbot will:

1. Engage users in a conversation to understand their reading preferences (genres, authors, moods, similar books).
2. Use OpenAI's ChatGPT API to generate personalized book recommendation ideas based on user input.
3. Query the **Google Books API (v1)** to fetch metadata (title, author, description, cover, ISBN) for the suggested books.
4. Display these recommendations in an attractive, user-friendly chat interface, including book covers and functioning **Amazon affiliate links**.
5. Be deployed as a functional web application.

The UI should take inspiration from Perplexity.ai – minimalist, clean, focused on the conversation and results.

## Project Structure Overview

- `backend/`
  - `main.py`: FastAPI application, API endpoints, chat logic, state management.
  - `llm.py` (Optional): Functions for interacting with ChatGPT.
  - `google_books.py` (Optional): Functions for interacting with Google Books API (initially mocked, then real).
  - `requirements.txt`: Python dependencies.
  - `.env.example`: Environment variable template (API Keys, Affiliate Tag).
- `frontend/` (or `public/`)
  - `index.html`: Main HTML structure for the chat interface.
  - `style.css`: CSS styling (Perplexity-inspired).
  - `script.js`: JavaScript for UI interaction, API calls, displaying messages/recommendations.
- `README.md`: Project documentation, setup, deployment instructions.
- `Dockerfile` / `render.yaml` / `vercel.json` (Optional): Deployment configuration.

## Setup & Running

1. **Clone:** `git clone ...`
2. **Backend Setup:**
    - `cd backend/`
    - Create/activate Python virtual environment.
    - `pip install -r requirements.txt`
    - Copy `.env.example` to `.env` and add `OPENAI_API_KEY`, `GOOGLE_BOOKS_API_KEY`, `AMAZON_ASSOCIATE_TAG`.
    - Run Backend: `uvicorn main:app --reload` (typically on `http://localhost:8000`).
3. **Frontend Setup:**
    - Navigate to `frontend/`.
    - Open `index.html` in browser or use local server (`python -m http.server 8081`).
    - Ensure `script.js` fetch URL points to the local backend API.

## Development Workflow & Commands

- **Backend:** Follow the prompts to build FastAPI endpoints, state logic, LLM calls, and Google Books API integration (mock first, then real). Test API endpoints. Use Black/Ruff for formatting.
- **Frontend:** Follow prompts to build HTML structure, Perplexity-inspired CSS, and JavaScript for dynamic UI updates and backend communication. Test heavily in browser.
- **Iteration:** Expect iteration on ChatGPT prompts (Prompt 4) and Google Books API query logic (Prompt 12) based on results.

## Code Style & Conventions

- **Backend:** Python 3.9+, FastAPI, Pydantic, Async/Await. Clean, modular code where possible. Secure API key handling via environment variables.
- **Frontend:** Modern Vanilla JavaScript (ES6+), Semantic HTML5, CSS3. Aim for minimalist Perplexity.ai aesthetic (clean layout, good typography, limited color palette). Responsive design.
- **API Contract:** Consistent JSON request/response structure for `/api/chat`.
- **Error Handling:** Basic error handling for API calls (both internal and external) should be implemented.

## Key Libraries/APIs

- **Backend:** `fastapi`, `uvicorn`, `pydantic`, `openai`, `requests`/`aiohttp`, `python-dotenv`.
- **Frontend:** Standard Browser APIs (`fetch`, DOM).
- **External:** OpenAI API (ChatGPT), Google Books API (v1).

## Important Reminders

- **API Keys:** Protect API keys. Use `.env` for local development and environment variables in deployment.
- **Mocking First:** Build with mocked Google Books API first (Prompt 3) to enable parallel frontend work and isolate external dependencies. Replace with real calls later (Prompt 12).
- **UI Style:** Strictly adhere to a clean, minimalist, Perplexity.ai-inspired aesthetic. Focus on readability and clarity of recommendations.
- **Affiliate Link:** Ensure the correct Amazon Associate Tag is used and links are functional (linking to standard Amazon product pages via ISBN).
- **MVP Scope:** Stick to the requirements defined in the PRD for MVP v1. Avoid feature creep.
