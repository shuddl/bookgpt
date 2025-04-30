#!/bin/bash

# BookGPT Deployment Script
# This script automates the deployment of both the backend API and WordPress plugin

echo "===== BookGPT Deployment Script ====="
echo "This script will help you deploy the BookGPT backend and configure the WordPress plugin."

# Check for required commands
check_command() {
  if ! command -v $1 &> /dev/null; then
    echo "Error: $1 is required but not installed."
    exit 1
  fi
}

check_command git
check_command python3
check_command pip3

# Backend deployment
deploy_backend() {
  echo ""
  echo "===== DEPLOYING BACKEND ====="
  
  # Get deployment type
  echo "Choose deployment platform:"
  echo "1) Vercel (recommended)"
  echo "2) Render"
  echo "3) Docker (self-hosted)"
  read -p "Enter your choice (1-3): " deployment_choice
  
  # Get API keys
  read -p "Enter your OpenAI API key: " openai_key
  read -p "Enter your Google Books API key: " google_books_key
  read -p "Enter your Amazon Associate Tag: " amazon_tag
  
  # Generate a webhook secret
  webhook_secret=$(openssl rand -base64 32)
  
  # Create environment variables
  echo "Creating environment variables..."
  
  case $deployment_choice in
    1)
      # Vercel deployment
      echo "Setting up Vercel deployment..."
      
      # Check for Vercel CLI
      if ! command -v vercel &> /dev/null; then
        echo "Installing Vercel CLI..."
        npm install -g vercel
      fi
      
      # Create .env file for Vercel
      cd ../backend
      cat > .env << EOF
OPENAI_API_KEY=$openai_key
GOOGLE_BOOKS_API_KEY=$google_books_key
AMAZON_ASSOCIATE_TAG=$amazon_tag
WEBHOOK_SECRET=$webhook_secret
EOF
      
      echo "Deploying to Vercel..."
      vercel --prod
      
      echo "Please enter the deployment URL provided by Vercel:"
      read backend_url
      ;;
      
    2)
      # Render deployment
      echo "Setting up Render deployment..."
      echo "1. Go to dashboard.render.com and create a new Web Service"
      echo "2. Connect your GitHub repository"
      echo "3. Set the following environment variables:"
      echo "   - OPENAI_API_KEY=$openai_key"
      echo "   - GOOGLE_BOOKS_API_KEY=$google_books_key" 
      echo "   - AMAZON_ASSOCIATE_TAG=$amazon_tag"
      echo "   - WEBHOOK_SECRET=$webhook_secret"
      echo ""
      echo "Please enter the deployment URL provided by Render:"
      read backend_url
      ;;
      
    3)
      # Docker deployment
      echo "Setting up Docker deployment..."
      cd ../backend
      
      # Create .env file for Docker
      cat > .env << EOF
OPENAI_API_KEY=$openai_key
GOOGLE_BOOKS_API_KEY=$google_books_key
AMAZON_ASSOCIATE_TAG=$amazon_tag
WEBHOOK_SECRET=$webhook_secret
EOF
      
      # Build and run Docker container
      docker build -t bookgpt-backend .
      docker run -d --name bookgpt-api -p 8000:8000 --env-file .env bookgpt-backend
      
      # Get server IP
      echo "Enter your server's public IP address or domain:"
      read server_ip
      backend_url="http://$server_ip:8000"
      ;;
      
    *)
      echo "Invalid choice. Exiting..."
      exit 1
      ;;
  esac
  
  echo "Backend deployment completed!"
  echo "Backend API URL: $backend_url"
  
  # Save the backend URL and webhook secret for WordPress plugin
  cd ../wp-plugin
  echo "{\"api_url\":\"$backend_url/api/chat\",\"webhook_secret\":\"$webhook_secret\"}" > deployment_config.json
  
  return 0
}

# WordPress plugin setup
setup_wordpress_plugin() {
  echo ""
  echo "===== SETTING UP WORDPRESS PLUGIN ====="
  
  # Check if deployment_config.json exists
  if [ ! -f "deployment_config.json" ]; then
    echo "Error: deployment_config.json not found. Please run backend deployment first."
    exit 1
  fi
  
  # Parse deployment config
  api_url=$(grep -o '"api_url":"[^"]*' deployment_config.json | grep -o '[^"]*$')
  webhook_secret=$(grep -o '"webhook_secret":"[^"]*' deployment_config.json | grep -o '[^"]*$')
  
  echo "WordPress Plugin Setup Instructions:"
  echo ""
  echo "1. Upload the 'wp-plugin' directory to your WordPress site's wp-content/plugins directory"
  echo "2. Rename it to 'bookgpt-wp'"
  echo "3. Log in to your WordPress admin panel"
  echo "4. Navigate to Plugins > Installed Plugins"
  echo "5. Activate the 'BookGPT - AI Book Recommendations' plugin"
  echo "6. Go to BookGPT > Settings in your WordPress admin menu"
  echo "7. Enter the following settings:"
  echo "   - API URL: $api_url"
  echo "   - Webhook Secret: $webhook_secret"
  echo "   - OpenAI API Key: (your OpenAI API key)"
  echo "   - Google Books API Key: (your Google Books API key)"
  echo "   - Amazon Associate Tag: (your Amazon Associate tag)"
  echo ""
  echo "8. Save the settings"
  echo "9. Go to BookGPT > Dashboard to verify the connection"
  echo ""
  echo "Your BookGPT WordPress plugin is now configured and ready to use!"
  
  # Create a ZIP file of the WordPress plugin for easy installation
  echo "Creating WordPress plugin ZIP file..."
  cd ..
  zip -r bookgpt-wp.zip wp-plugin
  echo "Plugin ZIP file created: bookgpt-wp.zip"
  echo "You can upload this ZIP file directly in WordPress admin > Plugins > Add New > Upload Plugin"
}

# Main execution
deploy_backend
setup_wordpress_plugin

echo ""
echo "===== DEPLOYMENT COMPLETE ====="
echo "Thank you for using BookGPT!"
echo "If you encounter any issues, please check the documentation or contact support."
