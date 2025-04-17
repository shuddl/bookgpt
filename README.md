# AI Book Recommendation Chatbot - MVP v1

## Overview

This project is a functional Minimum Viable Product (MVP) of an AI-powered chatbot designed to provide personalized book recommendations. Users can interact with the chatbot via a web interface to describe their reading preferences. The chatbot uses OpenAI's ChatGPT to understand preferences and suggest relevant books, then fetches detailed metadata (cover, description, author, etc.) using the Google Books API. Recommendations are presented with direct affiliate links to Amazon product pages.

The UI aims for a clean, minimalist aesthetic and is designed to be embedded into other websites.

## Live Demo / Asset Access

<!-- Add the deployment URL (e.g., from Vercel) here once deployed -->
**Deployment URL:** [Link to deployed application, e.g., https://your-project.vercel.app]

**Note:** This deployment primarily serves the embeddable widget assets (`/script.js`). Test embedding using the instructions below. Direct access to the base URL might redirect or show nothing depending on the `vercel.json` configuration.

## Key Features (MVP v1)

* Embeddable Interactive Chat Widget (Vanilla JS/CSS)
* Conversational Flow for gathering user preferences (genre, author, mood, similar books)
* NLP/Intent Understanding via ChatGPT Integration (Backend)
* Personalized Recommendation Generation via ChatGPT (Backend)
* Live Book Metadata Retrieval via Google Books API v1 (Backend)
* Dynamic Display of Recommendations: Includes cover image, title, author, description
* Amazon Affiliate Link Generation for displayed books (using provided tag)
* Clickable Suggestion Buttons for guided interaction
* Loading Indicators during backend processing
* Basic Session Management (context within a single chat session)
* Responsive design for the chat widget

## Technology Stack

* **Backend:** Python 3.9+, FastAPI, Uvicorn, Pydantic, OpenAI SDK, AIOHTTP (for Google Books)
* **Frontend:** Vanilla JavaScript (ES6+), CSS3 (injected via JS)
* **External APIs:** OpenAI (ChatGPT), Google Books API v1
* **Deployment:** Vercel (recommended for this setup)

## Project Structure

```
.
├── backend/
│   ├── main.py                # FastAPI app, endpoints, core logic, state, API calls
│   ├── requirements.txt       # Python dependencies
│   ├── .env.example           # Example environment variables
│   ├── Dockerfile             # Docker configuration (alternative deployment)
│   └── .dockerignore          # Files to exclude from Docker build
├── frontend/
│   ├── script.js              # Frontend JavaScript (contains CSS)
│   └── style.css              # Original CSS (now injected into script.js)
│   └── index.html             # Example HTML for local testing (not deployed)
├── .github/                   # GitHub Actions (optional CI/CD)
│   └── workflows/
│       ├── deploy.yml         # Example Vercel deploy workflow (alternative)
│       └── test.yml           # Example testing workflow
├── .gitignore
├── vercel.json                # Vercel deployment configuration (PRIMARY)
├── Procfile                   # For Heroku-style deployments (alternative)
└── README.md                  # This file
```

## Setup Instructions

1. Clone the repository: `git clone [your-repo-url]`
2. Navigate to the project directory: `cd bookgptwp`

### Backend Setup (for local development)

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

### Frontend Setup (for local testing)

* The `frontend/script.js` file is self-contained (includes CSS).
* You can use the `frontend/index.html` file to test the script locally, but it's not part of the Vercel deployment.

## Running Locally (for Development/Testing)

1. **Start Backend Server:**
    * Ensure you are in the `backend/` directory with the virtual environment activated.
    * Run: `uvicorn main:app --reload --port 8005` (Using port 8005 to avoid conflict with potential frontend servers)
    * The API will be available at `http://localhost:8005`.
2. **Test Frontend:**
    * Navigate to the `frontend/` directory (`cd ../frontend/`).
    * Modify `script.js` temporarily to point `API_URL` to `http://localhost:8005/api/chat`.
    * Open the `index.html` file directly in your web browser. The widget should appear and connect to your local backend.
    * **Remember to revert the `API_URL` change in `script.js` before committing/deploying.**

## Deployment (Vercel - Recommended for Embedding)

This project is configured for easy deployment to Vercel, serving the Python backend as a serverless function and the embeddable JavaScript directly.

1. **Prerequisites:**
    * Vercel Account: Sign up at [vercel.com](https://vercel.com/).
    * Vercel CLI: Install globally: `npm install -g vercel`
    * Login to Vercel CLI: `vercel login`

2. **Link Project:**
    * Navigate to your project's **root directory** in your terminal.
    * Run: `vercel link`
    * Follow the prompts to link the local directory to a new or existing Vercel project.

3. **Vercel Configuration (`vercel.json`):**
    * The `vercel.json` file in the root directory handles the build and routing configuration. It tells Vercel:
        * How to build the Python API from `backend/main.py` using the `@vercel/python` builder.
        * How to serve the `frontend/script.js` file statically using the `@vercel/static` builder.
        * How to route requests starting with `/api/` to the Python backend function.
        * How to route requests for `/script.js` to the correct frontend file (`/frontend/script.js`).
    * **Crucially, ensure your Vercel Project Settings match this configuration:**
        * Go to your Vercel Project Dashboard -> Settings -> General -> Build & Development Settings.
        * Set **Framework Preset:** to **Other**.
        * Ensure **Root Directory** is set to `./` (or leave blank).
        * Ensure **Build Command**, **Output Directory**, and **Install Command** are all **BLANK**. Vercel will use `vercel.json` instead.
        * Click **Save**.

4. **Environment Variables:**
    * Go to your Vercel Project Dashboard -> Settings -> Environment Variables.
    * Add the following secrets, ensuring they apply to the **Production** environment (and Preview/Development if needed):
        * `OPENAI_API_KEY`: Your OpenAI API key.
        * `GOOGLE_BOOKS_API_KEY`: Your Google Books API key.
        * `AMAZON_ASSOCIATE_TAG`: Your Amazon Associate tag.

5. **Deploy:**
    * From your project's **root directory** in the terminal:
    * Run: `vercel --prod`
    * The Vercel CLI will upload your project, build it according to `vercel.json`, and deploy it to production. Note the final deployment URL (e.g., `https://your-project-name.vercel.app`).

6. **Verify Deployment:**
    * **Check Build Logs:** In the Vercel dashboard (Deployments tab), review the logs for the production deployment. Confirm Python dependencies were installed and the builds completed successfully.
    * **Test API:** Use `curl` or Postman to send a POST request to `https://[your-deployment-url]/api/chat` with `{"user_id": "verify-test", "message": "hello"}`. Expect a JSON response.
    * **Test Script Access:** Open `https://[your-deployment-url]/script.js` in your browser. You should see your JavaScript code.

## Embedding the Chat Widget

Once deployed, you can embed the chat widget into any website by adding the following HTML snippet just before the closing `</body>` tag.

**Replace `[YOUR_DEPLOYMENT_URL]` with your actual Vercel deployment URL.**

```html
<!-- BookGPT Chatbot Embed Start -->
<!-- Add this script tag just before the closing </body> tag on your website -->
<!-- Replace [YOUR_DEPLOYMENT_URL] with your actual Vercel deployment URL (e.g., https://your-project.vercel.app) -->
<script src="https://[YOUR_DEPLOYMENT_URL]/script.js" defer></script>
<!-- BookGPT Chatbot Embed End -->
```

**Instructions for Website Owners:**

1. Copy the HTML snippet above.
2. Paste it into the HTML source code of the web page where you want the chatbot to appear. The best place is usually right before the closing `</body>` tag.
3. **Important:** Replace the placeholder `https://[YOUR_DEPLOYMENT_URL]/script.js` with the actual URL provided by your Vercel deployment (e.g., `https://bookgpt-widget.vercel.app/script.js`).
4. Save the changes to your web page. The chat widget should now load and appear on that page (usually fixed in the bottom-right corner).

## Alternative Deployment (Docker)

(Instructions for Docker deployment remain largely the same as before, but note that Vercel is the primary method for this embeddable setup).

1. **Prerequisites:** Docker installed and running.
2. **Build the Docker image:**
    * Navigate to the backend directory: `cd backend/`
    * Build the image: `docker build -t bookgpt-backend .`
3. **Run the container:**
    * Run: `docker run -p 8000:8000 --env-file .env bookgpt-backend`
    * This makes the API available at `http://localhost:8000`.
4. **Host the frontend script:**
    * For Docker, you would need a separate mechanism (like another container running Nginx/Caddy or a CDN) to host the `frontend/script.js` file.
    * You would also need to configure the `API_URL` within `script.js` to point to where your Dockerized backend is publicly accessible. This makes Vercel simpler for this specific use case.

## Limitations & Next Steps

* **MVP Scope:** Focuses on core recommendation and embedding. Features like user accounts, persistent history, ratings, advanced filtering are not included.
* **API Rate Limits:** Heavy usage might encounter rate limits on the free tiers of OpenAI or Google Books API.
* **Recommendation Quality:** Relies on ChatGPT's suggestions and Google Books data quality.
* **Affiliate Links:** Only Amazon is supported.
* **Error Handling:** Basic error handling; production systems need more robustness.
* **Session Management:** In-memory state; lost on serverless function restart (Vercel) or container restart (Docker).

Future enhancements could include:

* User accounts and persistent preferences/history (requires database)
* More sophisticated recommendation logic
* Additional book retailers
* Integration with user reviews/ratings
* Admin dashboard
* Database integration (e.g., Vercel Postgres, Supabase)
* Performance optimizations
