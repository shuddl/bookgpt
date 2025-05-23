# Simplified BookGPT Deployment Guide

After numerous attempts with Vercel, here are simpler deployment options that should get your BookGPT project up and running quickly.

## Option 1: Deploy on Render.com (Recommended)

Render is often simpler than Vercel for Python backend services.

### Step 1: Deploy Backend API

1. Create a [Render.com](https://render.com) account
2. Select "New Web Service"
3. Connect your GitHub repository or upload code directly
4. Configure as follows:
   - **Name**: bookgpt-backend
   - **Environment**: Python 3
   - **Build Command**: `pip install -r requirements.txt`
   - **Start Command**: `python main.py`
5. Add environment variables:
   ```
   OPENAI_API_KEY=your_openai_key_here
   GOOGLE_BOOKS_API_KEY=your_google_books_key_here
   AMAZON_ASSOCIATE_TAG=your_amazon_tag_here
   WEBHOOK_SECRET=generate_random_string_here
   ```
   (Generate a random webhook secret with: `openssl rand -base64 32`)
6. Deploy and note the URL (e.g., https://bookgpt-backend.onrender.com)

### Step 2: Configure WordPress Plugin

1. Edit the `wp-plugin/deployment_config.json` file:
   ```json
   {
     "api_url": "https://your-backend-url.onrender.com/api/chat",
     "webhook_secret": "same_webhook_secret_from_backend"
   }
   ```
2. Zip the WordPress plugin folder:
   ```bash
   zip -r bookgpt-wp.zip wp-plugin
   ```
3. Upload to WordPress: Admin → Plugins → Add New → Upload Plugin

## Option 2: Docker Deployment

If you prefer to self-host or use any cloud provider with Docker support:

### Step 1: Build and Deploy Docker Container

```bash
cd backend
docker build -t bookgpt-backend .
docker run -d -p 8000:8000 \
  -e OPENAI_API_KEY=your_openai_key \
  -e GOOGLE_BOOKS_API_KEY=your_google_books_key \
  -e AMAZON_ASSOCIATE_TAG=your_amazon_tag \
  -e WEBHOOK_SECRET=your_webhook_secret \
  --name bookgpt-api bookgpt-backend
```

### Step 2: Set Up Domain (Optional)

Set up a domain with nginx as a reverse proxy to your Docker container.

### Step 3: Configure WordPress Plugin

Same as Option 1, but use your Docker host URL.

## Option 3: Direct Hosting on PythonAnywhere or Similar

1. Create an account on [PythonAnywhere](https://www.pythonanywhere.com/)
2. Upload your backend code
3. Install requirements: `pip install -r requirements.txt`
4. Configure a WSGI file to run your app
5. Set environment variables through their dashboard
6. Configure WordPress plugin with the provided URL

## Debugging Common Issues

### Connection Problems

If the WordPress plugin can't connect to your backend:

1. Check CORS settings in your backend
2. Verify the API URL is correct and includes the full path
3. Ensure the webhook secret matches exactly
4. Test the API endpoint directly with tools like Postman

### Environment Variables

Double-check that all required environment variables are set:
- OPENAI_API_KEY
- GOOGLE_BOOKS_API_KEY
- AMAZON_ASSOCIATE_TAG
- WEBHOOK_SECRET

### WordPress Plugin Install Issues

If having trouble with the plugin:
1. Check PHP error logs
2. Try activating with debugging enabled
3. Verify file permissions

## Moving Forward

The webhook_secret only needs to match between your backend and WordPress plugin - it's a shared secret that both components use to verify communications.

If you're still experiencing issues after trying these simpler deployment options, please provide specific error messages for more targeted help.

## Option 4: Automated Deployment with Vercel

For those who prefer an automated deployment process, Vercel offers a streamlined solution.

### Step 1: Set Up Vercel Project

1. Create a [Vercel](https://vercel.com) account
2. Install Vercel CLI: `npm install -g vercel`
3. Clone your GitHub repository: `git clone https://github.com/yourusername/bookgptwp.git`
4. Navigate to the project directory: `cd bookgptwp`

### Step 2: Configure Environment Variables

Create a `.env` file in the project root with the following variables:

```
OPENAI_API_KEY=your_openai_api_key
GOOGLE_BOOKS_API_KEY=your_google_books_api_key
WEBHOOK_SECRET=a_random_secure_string
AMAZON_ASSOCIATE_TAG=your_amazon_associate_tag
```

### Step 3: Deploy to Vercel

The simplest way to deploy is using the Vercel CLI:

```bash
vercel login  # If not already logged in
vercel        # For preview deployment
# OR
vercel --prod # For production deployment
```

Alternatively, you can connect your GitHub repository to Vercel for automatic deployments.

### Step 4: Configure Secrets in Vercel

After the initial deployment, make sure to add environment variables in the Vercel dashboard:

1. Go to your Vercel project
2. Navigate to Settings → Environment Variables
3. Add the same environment variables from your `.env` file

### Step 5: Verify Deployment

Once deployed, visit your Vercel URL to confirm the application is working correctly.

### Step 6: Configure WordPress Plugin

1. Edit the `wp-plugin/deployment_config.json` file:
   ```json
   {
     "api_url": "https://your-vercel-url.vercel.app/api/chat",
     "webhook_secret": "same_webhook_secret_from_backend"
   }
   ```
2. Zip the WordPress plugin folder:
   ```bash
   zip -r bookgpt-wp.zip wp-plugin
   ```
3. Upload to WordPress: Admin → Plugins → Add New → Upload Plugin

## Conclusion

With these deployment options, you can choose the method that best fits your needs. Whether you prefer the simplicity of Render.com, the flexibility of Docker, the direct approach of PythonAnywhere, or the automation of Vercel, you can get your BookGPT project up and running smoothly.
