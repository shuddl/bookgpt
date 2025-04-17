# BookGPT - AI Book Recommendation Chatbot

BookGPT is an AI-powered book recommendation chatbot that helps users discover their next great read. It can be deployed as a standalone web application or embedded as a WordPress plugin.

## Features

- **AI-Powered Recommendations**: Uses OpenAI's GPT to understand user preferences and provide personalized book recommendations
- **Book Details**: Integrates with Google Books API to fetch detailed book information
- **Affiliate Integration**: Amazon Associates links for book recommendations
- **Dual Deployment Options**:
  - Standalone web application hosted on Vercel
  - WordPress plugin for easy website integration
- **Customizable Widget**: Configure position, theme, and dimensions
- **Analytics**: Track interactions and conversions

## Project Structure

```
bookgptwp/
├── backend/            # FastAPI backend application
│   ├── main.py         # API endpoints & recommendation logic
│   └── requirements.txt
├── frontend/           # Frontend assets
│   ├── index.html      # Standalone application HTML
│   ├── script.js       # Main widget script (embeddable)
│   ├── worker.js       # Web worker for background processing
│   └── style.css       # CSS styles
├── wp-plugin/          # WordPress plugin files
├── scripts/            # Build and deployment scripts
├── vercel.json         # Vercel configuration
└── deployment-guide.md # Comprehensive deployment instructions
```

## Quick Start

### Standalone Web Application

1. Clone the repository
2. Set up environment variables (OpenAI API key, etc.)
3. Deploy to Vercel

```bash
git clone https://github.com/yourusername/bookgptwp.git
cd bookgptwp
vercel
```

### WordPress Plugin

1. Download the latest `bookgpt-wp.zip` from [Releases](https://github.com/yourusername/bookgptwp/releases)
2. Install in WordPress admin (Plugins → Add New → Upload Plugin)
3. Configure the plugin with your Vercel deployment URL

## Detailed Documentation

For complete setup and deployment instructions, see:

- [Deployment Guide](deployment-guide.md) - Step-by-step deployment instructions
- [WordPress Plugin Documentation](wp-plugin/README.md) - WordPress integration guide
- [API Documentation](backend/README.md) - Backend API documentation

## Local Development

### Backend

```bash
cd backend
pip install -r requirements.txt
uvicorn main:app --reload --port 8005
```

### Frontend

```bash
# Serve the frontend files using any static server, e.g.:
cd frontend
python -m http.server 8080
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgements

- OpenAI for the GPT API
- Google Books API for book data
- Vercel for hosting
