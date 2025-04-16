# AI Book Recommendation Chatbot - MVP v1

## Overview

This project is a functional Minimum Viable Product (MVP) of an AI-powered chatbot designed to provide personalized book recommendations. Users can interact with the chatbot via a web interface to describe their reading preferences. The chatbot uses OpenAI's ChatGPT to understand preferences and suggest relevant books, then fetches detailed metadata (cover, description, author, etc.) using the Google Books API. Recommendations are presented with direct affiliate links to Amazon product pages.

The UI aims for a clean, minimalist aesthetic 

## Live Demo

<!-- Add the deployment URL (e.g., from Vercel/Render) here once deployed -->
**URL:** [Link to deployed application]

## Key Features (MVP v1)

* Interactive Chat Interface (HTML/CSS/JS)
* Conversational Flow for gathering user preferences (genre, author, mood, similar books)
* NLP/Intent Understanding via ChatGPT Integration (Backend)
* Personalized Recommendation Generation via ChatGPT (Backend)
* Live Book Metadata Retrieval via Google Books API v1 (Backend)
* Dynamic Display of Recommendations: Includes cover image, title, author, description
* Amazon Affiliate Link Generation for displayed books (using provided tag)
* Clickable Suggestion Buttons for guided interaction
* Loading Indicators during backend processing
* Basic Session Management (context within a single chat session)
* Responsive design for Desktop/Mobile web browsers

## Technology Stack

* **Backend:** Python 3.9+, FastAPI, Uvicorn, Pydantic, OpenAI SDK, AIOHTTP (for Google Books)
* **Frontend:** HTML5, CSS3, Vanilla JavaScript (ES6+)
* **External APIs:** OpenAI (ChatGPT), Google Books API v1
* **Deployment Options:** Docker, Vercel, or Render (supporting Python backend + Static frontend)

## Project Structure

```
.
├── backend/
│   ├── main.py                # FastAPI app, endpoints, core logic, state, API calls
│   ├── requirements.txt       # Python dependencies
│   ├── .env.example           # Example environment variables
│   ├── Dockerfile             # Docker configuration for backend
│   └── .dockerignore          # Files to exclude from Docker build
├── frontend/
│   ├── index.html             # Frontend HTML
│   ├── style.css              # Frontend CSS
│   └── script.js              # Frontend JavaScript
├── .gitignore
└── README.md                  # This file
```

## Setup Instructions

1. Clone the repository: `git clone [your-repo-url]`
2. Navigate to the project directory: `cd bookgpt`

### Backend Setup

3. Navigate to the backend directory: `cd backend/`
4. Create and activate a Python virtual environment:
   * `python -m venv venv`
   * `source venv/bin/activate` (Linux/macOS) OR `.\venv\Scripts\activate` (Windows)
5. Install dependencies: `pip install -r requirements.txt`
6. Configure Environment Variables:
   * Copy `.env.example` to `.env`: `cp .env.example .env`
   * **Edit `.env`** and add your actual API keys and Amazon tag:

     ```dotenv
     OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     GOOGLE_BOOKS_API_KEY=AIzaSyxxxxxxxxxxxxxxxxxxxxxxxxxx
     AMAZON_ASSOCIATE_TAG=yourtag-20
     ```

### Frontend Setup

* No specific build steps required for the vanilla HTML/CSS/JS frontend.

## Running Locally

1. **Start Backend Server:**
   * Ensure you are in the `backend/` directory with the virtual environment activated.
   * Run: `uvicorn main:app --reload` or `python main.py`
   * The API will typically be available at `http://localhost:8005`.
2. **Start Frontend:**
   * Navigate to the `frontend/` directory (`cd ../frontend/`).
   * Open the `index.html` file directly in your web browser, OR use a simple local server (e.g., `python -m http.server 8081` and browse to `http://localhost:8081`).
   * The frontend should automatically try to connect to the backend at `http://localhost:8005/api/chat` (as configured in `script.js`).

## Deployment

### Option 1: GitHub Deployment with GitHub Actions

1. **Push your code to GitHub:**
   * Create a repository on GitHub if you haven't already.
   * Push your local repository to GitHub.

2. **Set up GitHub Actions for Vercel deployment:**
   * Create a `.github/workflows` directory in your project:
     ```bash
     mkdir -p .github/workflows
     ```
   * Create a GitHub Actions workflow file:
     ```bash
     touch .github/workflows/deploy.yml
     ```
   * Add the following content to `deploy.yml`:
     ```yaml
     name: Deploy to Vercel
     on:
       push:
         branches: [main]
     jobs:
       deploy:
         runs-on: ubuntu-latest
         steps:
           - uses: actions/checkout@v3
           - uses: actions/setup-node@v3
             with:
               node-version: '18'
           - name: Install Vercel CLI
             run: npm install --global vercel@latest
           - name: Deploy to Vercel
             env:
               VERCEL_TOKEN: ${{ secrets.VERCEL_TOKEN }}
               VERCEL_ORG_ID: ${{ secrets.VERCEL_ORG_ID }}
               VERCEL_PROJECT_ID: ${{ secrets.VERCEL_PROJECT_ID }}
             run: vercel deploy --prod --token=$VERCEL_TOKEN
     ```

3. **Set up GitHub Secrets:**
   * Go to your GitHub repository > Settings > Secrets and variables > Actions
   * Add the following secrets:
     * `VERCEL_TOKEN`: Your Vercel API token
     * `VERCEL_ORG_ID`: Your Vercel organization ID
     * `VERCEL_PROJECT_ID`: Your Vercel project ID

   You can obtain these values by running `vercel login` and `vercel link` in your local terminal.

4. **Set up Environment Variables in Vercel:**
   * Add the required environment variables (`OPENAI_API_KEY`, `GOOGLE_BOOKS_API_KEY`, `AMAZON_ASSOCIATE_TAG`) in your Vercel project settings.

### Option 2: Docker Deployment

1. **Prerequisites:** Docker installed and running on your machine or server.
2. **Build the Docker image:**
   * Navigate to the backend directory: `cd backend/`
   * Build the image: `docker build -t bookgpt-backend .`
3. **Run the container:**
   * Run: `docker run -p 8000:8000 --env-file .env bookgpt-backend`
   * This makes the API available at `http://localhost:8000`.
4. **Host the frontend:**
   * You can either:
     * Serve the frontend files using a web server like Nginx or Apache
     * Use a static site hosting service (Netlify, GitHub Pages, etc.)
   * Make sure to update the API_URL in `script.js` to point to your deployed backend.

## Limitations & Next Steps

* **MVP Scope:** This version focuses on core recommendation flows. Features like user accounts, persistent history, ratings, advanced filtering are not included.
* **API Rate Limits:** Heavy usage might encounter rate limits on the free tiers of OpenAI or Google Books API.
* **Recommendation Quality:** Relies on ChatGPT's suggestions and Google Books data quality. Further prompt tuning and data source refinement may be needed.
* **Affiliate Links:** Only Amazon is supported in this MVP.
* **Error Handling:** Basic error handling is implemented; production systems would require more robust monitoring and alerting.
* **Session Management:** Current session state is stored in memory and will be lost on server restart.

Future enhancements could include:

* User accounts and persistent preferences
* Enhanced recommendation algorithms with machine learning
* Additional book retailers beyond Amazon
* Book popularity and user review integration
* Admin dashboard for monitoring and analytics
* Database integration for persistent storage
* Performance optimizations for handling more concurrent users
