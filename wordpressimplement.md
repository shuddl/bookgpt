# WordPress Plugin Implementation Guide for BookGPT

This guide provides step-by-step instructions for implementing the BookGPT book recommendation chatbot as a WordPress plugin.

## Overview

The BookGPT WordPress plugin adds an AI-powered book recommendation chatbot to your WordPress site. The plugin consists of:

1. **Frontend Widget**: A chat interface that appears on your website
2. **Admin Interface**: Settings, analytics, and management tools
3. **Backend API Connection**: Integration with your deployed BookGPT backend

## Deployment Instructions

### Step 1: Deploy the Backend API

First, deploy the BookGPT backend API using the provided deployment script:

```bash
cd /path/to/bookgptwp/wp-plugin
chmod +x deploy.sh
./deploy.sh
```

The script will guide you through deploying the backend to Vercel, Render, or your own server using Docker.

### Step 2: Install the WordPress Plugin

#### Method 1: Using the ZIP File

1. After running the deployment script, a `bookgpt-wp.zip` file will be created
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New > Upload Plugin**
4. Select the `bookgpt-wp.zip` file and click **Install Now**
5. After installation completes, click **Activate Plugin**

#### Method 2: Manual Installation

1. Upload the entire `wp-plugin` directory to your WordPress site's `wp-content/plugins` directory
2. Rename the directory to `bookgpt-wp`
3. Log in to your WordPress admin panel
4. Navigate to **Plugins > Installed Plugins**
5. Find "BookGPT - AI Book Recommendations" and click **Activate**

### Step 3: Configure the Plugin

1. In your WordPress admin panel, navigate to **BookGPT > Settings**
2. Enter the following information:
   - **API URL**: The URL of your deployed backend API (e.g., `https://your-app.vercel.app/api/chat`)
   - **OpenAI API Key**: Your OpenAI API key
   - **Google Books API Key**: Your Google Books API key
   - **Amazon Associate Tag**: Your Amazon Associates affiliate ID
   - **Webhook Secret**: The secret key generated during deployment

3. Configure chat widget appearance:
   - Adjust colors, position, and size to match your site's design
   - Customize chat messages and prompts

4. Save your settings

### Step 4: Add the Chat Widget to Your Site

The chat widget is automatically added to all pages of your WordPress site. You can:

#### Option 1: Use Default Widget Placement

No further action required - the widget appears in the bottom-right corner of your site by default.

#### Option 2: Use Shortcode

Add the chatbot to specific pages or posts using the shortcode:

```
[bookgpt_chat width="350px" height="500px"]
```

#### Option 3: Use PHP Function

Add to your theme's PHP files:

```php
<?php if (function_exists('bookgpt_display_chat')) { bookgpt_display_chat(); } ?>
```

## Admin Interface Guide

### Dashboard

The **BookGPT > Dashboard** provides an overview of:
- Total conversations
- Book recommendations made
- Affiliate link clicks
- Estimated earnings
- API usage and costs

### Analytics

The **BookGPT > Analytics** page offers detailed insights:
- User interaction metrics
- Conversion data
- Popular book recommendations
- User query analysis
- API usage breakdown

You can:
- Filter data by date range
- Export data as CSV
- View visualization charts

### Settings

The **BookGPT > Settings** page has multiple tabs:

1. **General Settings**: API configuration and basic options
2. **Backend Logic**: AI model settings and prompt customization
3. **Widget Appearance**: Customize look and feel of the chat widget
4. **Affiliate Settings**: Configure Amazon affiliate link settings
5. **Deployment**: Tools for testing and managing backend deployment

## Advanced Configuration

### Custom CSS

You can add custom CSS in the **Widget Appearance** tab to further customize the chat widget.

### Prompt Customization

Modify the system prompt in the **Backend Logic** tab to customize how the AI responds and recommends books.

### Affiliate Link Customization

In the **Affiliate Settings** tab, you can:
- Choose how book links are displayed (text, button, image, card)
- Set default Amazon domain for international targeting
- Add UTM parameters for enhanced tracking
- Customize affiliate disclosure text

## Troubleshooting

### Connection Issues

If the chat widget can't connect to the backend:

1. Verify your API URL in **BookGPT > Settings**
2. Use the "Test Connection" button to diagnose issues
3. Check that your backend service is running
4. Ensure your API keys are valid and properly formatted

### Tracking Issues

If analytics aren't being recorded:

1. Ensure "Enable Analytics" is checked in settings
2. Check browser console for JavaScript errors
3. Verify that tracking endpoints are accessible

### API Errors

If the chat produces errors:

1. Check the API logs on your deployment platform
2. Verify OpenAI API key has sufficient credits
3. Test with simple queries to isolate the problem

## Best Practices

1. **Performance**: Keep API response time in mind; use caching where possible
2. **Privacy**: Be transparent about data collection and AI usage
3. **Testing**: Test the chatbot thoroughly before publishing
4. **Mobile**: Ensure the widget works well on mobile devices
5. **FTC Compliance**: Include proper affiliate disclosure notices

## Support

For additional support:
- Review the documentation in the GitHub repository
- Open an issue for bug reports
- Contact the developer for premium support options

## New Widget Form of AI Chat

### Overview

The new widget form of the AI chat allows you to embed the chat widget seamlessly into your WordPress site. This widget form is designed to be flexible and customizable, providing a consistent user experience across different pages and posts.

### Configuration

1. **Enable Widget Form**: In your WordPress admin panel, navigate to **BookGPT > Settings** and enable the widget form option.
2. **Customize Appearance**: Adjust the appearance settings to match your site's design. You can customize the widget's colors, position, and size.
3. **Save Settings**: Save your settings to apply the changes.

### Adding the Widget to Your Site

You can add the widget form of the AI chat to your site using the following methods:

#### Option 1: Use Default Widget Placement

No further action required - the widget appears in the bottom-right corner of your site by default.

#### Option 2: Use Shortcode

Add the chatbot to specific pages or posts using the shortcode:

```
[bookgpt_widget width="350px" height="500px"]
```

#### Option 3: Use PHP Function

Add to your theme's PHP files:

```php
<?php if (function_exists('bookgpt_display_widget')) { bookgpt_display_widget(); } ?>
```

### Customization Options

The widget form of the AI chat can be customized through the following options:

1. **Position**: Choose the position of the widget on the screen (e.g., bottom-right, bottom-left).
2. **Theme**: Select the theme for the widget (e.g., light, dark).
3. **Size**: Adjust the width and height of the widget to fit your site's layout.
4. **Custom CSS**: Add custom CSS to further customize the appearance of the widget.

### Troubleshooting

If you encounter any issues with the widget form of the AI chat, follow these steps:

1. **Check Configuration**: Ensure that the widget form is enabled and configured correctly in the settings.
2. **Verify API URL**: Make sure the API URL is correctly set in the settings.
3. **Inspect Console**: Check the browser console for any JavaScript errors or warnings.
4. **Test Connection**: Use the "Test Connection" button in the settings to diagnose any connection issues.

For additional support, refer to the main troubleshooting section or contact the developer for assistance.
