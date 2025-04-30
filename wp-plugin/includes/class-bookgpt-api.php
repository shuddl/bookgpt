<?php

/**
 * The API handling functionality of the plugin.
 */
class BookGPT_API {

    /**
     * Initialize the API functionality.
     */
    public function __construct() {
        add_action('wp_ajax_bookgpt_test_api_connection', array($this, 'test_api_connection'));
        add_action('wp_ajax_bookgpt_update_backend_logic', array($this, 'update_backend_logic'));
        add_action('wp_ajax_bookgpt_generate_deployment_script', array($this, 'generate_deployment_script'));
    }

    /**
     * Test the connection to the BookGPT API.
     */
    public function test_api_connection() {
        // Check nonce for security
        check_ajax_referer('bookgpt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }
        
        $api_url = sanitize_text_field($_POST['api_url']);
        
        if (empty($api_url)) {
            wp_send_json_error('API URL is required.');
            return;
        }
        
        // Make sure we have a valid URL
        if (!filter_var($api_url, FILTER_VALIDATE_URL)) {
            wp_send_json_error('Invalid API URL format.');
            return;
        }
        
        // Clean up the URL to ensure it points to the chat endpoint
        $api_url = trailingslashit(preg_replace('/\/api\/chat\/?$/', '', $api_url)) . 'api/chat';
        
        // Test connection with a simple request
        $response = wp_remote_post($api_url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'user_id' => 'test_connection_' . wp_generate_password(8, false),
                'message' => 'test'
            ))
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            wp_send_json_error('API returned status code: ' . $status_code);
            return;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body) || !isset($body['bot_message'])) {
            wp_send_json_error('Invalid response from API.');
            return;
        }
        
        wp_send_json_success('API connection successful!');
    }

    /**
     * Update the backend logic configuration via API.
     */
    public function update_backend_logic() {
        // Check nonce for security
        check_ajax_referer('bookgpt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }
        
        // Get settings
        $options = get_option('bookgpt_options');
        $api_url = isset($options['api_url']) ? $options['api_url'] : '';
        
        if (empty($api_url)) {
            wp_send_json_error('API URL is not configured. Please set up the API URL first.');
            return;
        }
        
        // Get logic settings from the request
        $ai_model = sanitize_text_field($_POST['ai_model']);
        $temperature = floatval($_POST['temperature']);
        $max_recommendations = intval($_POST['max_recommendations']);
        $prompt_template = sanitize_textarea_field($_POST['prompt_template']);
        $response_template = sanitize_textarea_field($_POST['response_template']);
        $book_format_template = sanitize_textarea_field($_POST['book_format_template']);
        
        // Validate inputs
        if (empty($ai_model) || empty($prompt_template)) {
            wp_send_json_error('AI model and prompt template are required.');
            return;
        }
        
        // Endpoint for updating backend logic
        $config_url = trailingslashit(preg_replace('/\/api\/chat\/?$/', '', $api_url)) . 'api/config';
        
        // Send request to update backend logic
        $response = wp_remote_post($config_url, array(
            'timeout' => 15,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . (isset($options['webhook_secret']) ? $options['webhook_secret'] : ''),
            ),
            'body' => json_encode(array(
                'ai_model' => $ai_model,
                'temperature' => $temperature,
                'max_recommendations' => $max_recommendations,
                'prompt_template' => $prompt_template,
                'response_template' => $response_template,
                'book_format_template' => $book_format_template,
            ))
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            wp_send_json_error('API returned status code: ' . $status_code);
            return;
        }
        
        // Update options in WordPress
        $options['ai_model'] = $ai_model;
        $options['temperature'] = $temperature;
        $options['max_recommendations'] = $max_recommendations;
        $options['prompt_template'] = $prompt_template;
        $options['response_template'] = $response_template;
        $options['book_format_template'] = $book_format_template;
        
        update_option('bookgpt_options', $options);
        
        wp_send_json_success('Backend logic updated successfully.');
    }

    /**
     * Generate a deployment script based on current settings.
     */
    public function generate_deployment_script() {
        // Check nonce for security
        check_ajax_referer('bookgpt_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied.');
            return;
        }
        
        $options = get_option('bookgpt_options');
        
        // Get deployment variables
        $openai_api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
        $google_books_api_key = isset($options['google_books_api_key']) ? $options['google_books_api_key'] : '';
        $webhook_secret = isset($options['webhook_secret']) ? $options['webhook_secret'] : '';
        $amazon_associate_tag = isset($options['amazon_associate_tag']) ? $options['amazon_associate_tag'] : '';
        
        // Generate GitHub actions workflow content
        $workflow_content = <<<EOT
name: Deploy BookGPT Backend

on:
  workflow_dispatch:
    inputs:
      environment:
        description: 'Deployment environment'
        required: true
        default: 'production'
        type: choice
        options:
          - production
          - staging
          - development

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout code
        uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Install Vercel CLI
        run: npm install -g vercel
      
      - name: Create .env file
        run: |
          cat << EOF > .env
          OPENAI_API_KEY=${{ secrets.OPENAI_API_KEY }}
          GOOGLE_BOOKS_API_KEY=${{ secrets.GOOGLE_BOOKS_API_KEY }}
          WEBHOOK_SECRET=${{ secrets.WEBHOOK_SECRET }}
          AMAZON_ASSOCIATE_TAG=${{ secrets.AMAZON_ASSOCIATE_TAG }}
          EOF
      
      - name: Deploy to Vercel
        env:
          VERCEL_TOKEN: \${{ secrets.VERCEL_TOKEN }}
          VERCEL_ORG_ID: \${{ secrets.VERCEL_ORG_ID }}
          VERCEL_PROJECT_ID: \${{ secrets.VERCEL_PROJECT_ID }}
        run: |
          if [ "\${{ github.event.inputs.environment }}" = "production" ]; then
            vercel --prod --token \${{ secrets.VERCEL_TOKEN }} --yes
          else
            vercel --token \${{ secrets.VERCEL_TOKEN }} --yes
          fi
      
      - name: Output Deployment URL
        run: echo "Deployed to \$(vercel --token \${{ secrets.VERCEL_TOKEN }} --scope \${{ secrets.VERCEL_ORG_ID }} --yes)"

EOT;

        // Generate shell script content for manual deployment
        $sh_script_content = <<<EOT
#!/bin/bash

# BookGPT Deployment Script
# This script deploys the BookGPT backend to Vercel

# Set environment variables
export OPENAI_API_KEY="{$openai_api_key}"
export GOOGLE_BOOKS_API_KEY="{$google_books_api_key}"
export WEBHOOK_SECRET="{$webhook_secret}"
export AMAZON_ASSOCIATE_TAG="{$amazon_associate_tag}"

echo "BookGPT Deployment Script"
echo "========================="

# Check if Git is installed
if ! [ -x "\$(command -v git)" ]; then
  echo "Error: Git is not installed." >&2
  exit 1
fi

# Check if Node.js is installed
if ! [ -x "\$(command -v node)" ]; then
  echo "Error: Node.js is not installed." >&2
  exit 1
fi

# Check if Vercel CLI is installed
if ! [ -x "\$(command -v vercel)" ]; then
  echo "Installing Vercel CLI..."
  npm install -g vercel
fi

# Clone repository if not exists
if [ ! -d "bookgptwp" ]; then
  echo "Cloning BookGPT repository..."
  git clone https://github.com/yourusername/bookgptwp.git
  cd bookgptwp
else
  echo "Updating existing repository..."
  cd bookgptwp
  git pull
fi

# Create .env file
echo "Creating .env file with your settings..."
cat > .env << EOF
OPENAI_API_KEY={$openai_api_key}
GOOGLE_BOOKS_API_KEY={$google_books_api_key}
WEBHOOK_SECRET={$webhook_secret}
AMAZON_ASSOCIATE_TAG={$amazon_associate_tag}
EOF

# Deploy to Vercel
echo "Deploying to Vercel..."
vercel deploy --prod

echo ""
echo "Deployment complete!"
echo "Please copy the deployment URL from above and paste it in your WordPress settings."
echo "API URL format should be: https://your-deployment-url.vercel.app/api/chat"

EOT;

        // Return both scripts
        wp_send_json_success(array(
            'github_workflow' => $workflow_content,
            'shell_script' => $sh_script_content
        ));
    }

    /**
     * Test the connection to the API
     * 
     * @param string $api_url API URL to test
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function test_connection($api_url) {
        $response = wp_remote_get(
            $api_url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                )
            )
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_Error('api_error', sprintf(__('API returned status code: %d', 'bookgpt-wp'), $status_code));
        }
        
        return true;
    }
    
    /**
     * Update backend logic
     * 
     * @param string $prompt_template New prompt template
     * @param int $max_recommendations Maximum number of recommendations
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function update_backend_logic($prompt_template, $max_recommendations) {
        $options = get_option('bookgpt_options');
        $api_url = !empty($options['api_url']) ? $options['api_url'] : '';
        
        if (empty($api_url)) {
            return new WP_Error('missing_api_url', __('API URL is not configured', 'bookgpt-wp'));
        }
        
        // Extract the base URL without the /chat endpoint
        $base_url = preg_replace('/\/chat$/', '', $api_url);
        $config_url = trailingslashit($base_url) . 'config';
        
        $response = wp_remote_post(
            $config_url,
            array(
                'timeout' => 15,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ),
                'body' => json_encode(array(
                    'system_prompt' => $prompt_template,
                    'max_recommendations' => $max_recommendations,
                    'api_key' => $options['openai_api_key'] ?? '',
                    'google_books_api_key' => $options['google_books_api_key'] ?? '',
                    'amazon_associate_tag' => $options['amazon_associate_tag'] ?? '',
                ))
            )
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200) {
            return new WP_Error('api_error', sprintf(__('API returned status code: %d', 'bookgpt-wp'), $status_code));
        }
        
        return true;
    }
    
    /**
     * Track API usage
     * 
     * @param string $api_type Type of API (e.g., 'openai', 'google_books')
     * @param int $tokens_used Number of tokens used
     * @param float $cost Cost of API usage
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function track_api_usage($api_type, $tokens_used, $cost) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'bookgpt_api_usage';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'api_type' => $api_type,
                'tokens_used' => $tokens_used,
                'cost' => $cost,
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%f', '%s')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', __('Failed to track API usage', 'bookgpt-wp'));
        }
        
        return true;
    }
    
    /**
     * Get estimated token count from text
     * 
     * @param string $text Text to estimate tokens for
     * @return int Estimated token count
     */
    public function estimate_token_count($text) {
        // Simple estimation: ~4 characters per token for English text
        return (int) ceil(mb_strlen($text) / 4);
    }
    
    /**
     * Get API usage cost estimate
     * 
     * @param string $model OpenAI model name
     * @param int $prompt_tokens Number of tokens in prompt
     * @param int $completion_tokens Number of tokens in completion
     * @return float Estimated cost in USD
     */
    public function get_api_cost_estimate($model, $prompt_tokens, $completion_tokens) {
        // Cost per 1000 tokens for different models
        $cost_per_1k = array(
            'gpt-4' => array('prompt' => 0.03, 'completion' => 0.06),
            'gpt-4-turbo' => array('prompt' => 0.01, 'completion' => 0.03),
            'gpt-3.5-turbo' => array('prompt' => 0.0015, 'completion' => 0.002),
            'default' => array('prompt' => 0.0015, 'completion' => 0.002),
        );
        
        $model_rates = $cost_per_1k[$model] ?? $cost_per_1k['default'];
        
        $prompt_cost = ($prompt_tokens / 1000) * $model_rates['prompt'];
        $completion_cost = ($completion_tokens / 1000) * $model_rates['completion'];
        
        return $prompt_cost + $completion_cost;
    }
    
    /**
     * Process a webhook from the API with usage data
     * 
     * @param array $data Webhook data
     * @return bool True on success
     */
    public function process_usage_webhook($data) {
        if (!isset($data['api_type']) || !isset($data['tokens_used'])) {
            return false;
        }
        
        $api_type = sanitize_text_field($data['api_type']);
        $tokens_used = intval($data['tokens_used']);
        $cost = isset($data['cost']) ? floatval($data['cost']) : 0;
        
        // If cost was not provided but we have OpenAI model info, estimate it
        if ($cost === 0 && $api_type === 'openai' && isset($data['model'])) {
            $model = sanitize_text_field($data['model']);
            $prompt_tokens = isset($data['prompt_tokens']) ? intval($data['prompt_tokens']) : $tokens_used;
            $completion_tokens = isset($data['completion_tokens']) ? intval($data['completion_tokens']) : 0;
            
            $cost = $this->get_api_cost_estimate($model, $prompt_tokens, $completion_tokens);
        }
        
        return $this->track_api_usage($api_type, $tokens_used, $cost);
    }
}
