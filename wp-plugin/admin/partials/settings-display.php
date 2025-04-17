<?php
/**
 * Admin settings display
 */
// Prevent direct access
if (!defined('WPINC')) {
    die;
}

// Get current tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
$options = get_option('bookgpt_options', array());
?>
<div class="wrap bookgpt-admin bookgpt-settings-page">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <h2 class="nav-tab-wrapper">
        <a href="?page=bookgpt-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('General Settings', 'bookgpt-wp'); ?></a>
        <a href="?page=bookgpt-settings&tab=backend" class="nav-tab <?php echo $active_tab === 'backend' ? 'nav-tab-active' : ''; ?>"><?php _e('Backend Logic', 'bookgpt-wp'); ?></a>
        <a href="?page=bookgpt-settings&tab=appearance" class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>"><?php _e('Widget Appearance', 'bookgpt-wp'); ?></a>
        <a href="?page=bookgpt-settings&tab=deployment" class="nav-tab <?php echo $active_tab === 'deployment' ? 'nav-tab-active' : ''; ?>"><?php _e('Deployment', 'bookgpt-wp'); ?></a>
        <a href="?page=bookgpt-settings&tab=affiliate" class="nav-tab <?php echo $active_tab === 'affiliate' ? 'nav-tab-active' : ''; ?>"><?php _e('Affiliate Settings', 'bookgpt-wp'); ?></a>
    </h2>
    
    <div class="bookgpt-settings-container">
        <form method="post" action="options.php" id="bookgpt-settings-form" class="bookgpt-settings-form">
            <?php if ($active_tab === 'general'): ?>
                <!-- General Settings -->
                <?php
                settings_fields('bookgpt_options_group');
                do_settings_sections('bookgpt-settings');
                ?>
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('API Configuration', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API URL', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[api_url]" class="regular-text" value="<?php echo esc_attr($options['api_url'] ?? ''); ?>">
                                    <p class="description"><?php _e('URL of your BookGPT backend API (e.g., https://your-bookgpt-api.vercel.app/api/chat)', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('OpenAI API Key', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="password" name="bookgpt_options[openai_api_key]" class="regular-text" value="<?php echo esc_attr($options['openai_api_key'] ?? ''); ?>">
                                    <p class="description"><?php _e('Your OpenAI API key for generating recommendations.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Google Books API Key', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="password" name="bookgpt_options[google_books_api_key]" class="regular-text" value="<?php echo esc_attr($options['google_books_api_key'] ?? ''); ?>">
                                    <p class="description"><?php _e('Your Google Books API key for fetching book details.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Advanced Settings', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Analytics', 'bookgpt-wp'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="bookgpt_options[enable_analytics]" value="yes" <?php checked(($options['enable_analytics'] ?? 'yes'), 'yes'); ?>>
                                        <?php _e('Track user interactions and API usage', 'bookgpt-wp'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Enable Chat History', 'bookgpt-wp'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="bookgpt_options[enable_chat_history]" value="yes" <?php checked(($options['enable_chat_history'] ?? 'yes'), 'yes'); ?>>
                                        <?php _e('Remember conversation context within a session', 'bookgpt-wp'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Webhook Secret', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="password" name="bookgpt_options[webhook_secret]" class="regular-text" value="<?php echo esc_attr($options['webhook_secret'] ?? ''); ?>">
                                    <p class="description"><?php _e('Secret key for securing webhook communications between your backend and WordPress.', 'bookgpt-wp'); ?></p>
                                    <button type="button" class="button" id="generate-webhook-secret"><?php _e('Generate Secret', 'bookgpt-wp'); ?></button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'backend'): ?>
                <!-- Backend Logic -->
                <?php settings_fields('bookgpt_options_group'); ?>
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Backend Logic Configuration', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('AI Model', 'bookgpt-wp'); ?></th>
                                <td>
                                    <select name="bookgpt_options[ai_model]">
                                        <option value="gpt-4" <?php selected(($options['ai_model'] ?? 'gpt-4'), 'gpt-4'); ?>>GPT-4</option>
                                        <option value="gpt-4-turbo" <?php selected(($options['ai_model'] ?? ''), 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                        <option value="gpt-3.5-turbo" <?php selected(($options['ai_model'] ?? ''), 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                    </select>
                                    <p class="description"><?php _e('Select the OpenAI model to use for recommendations.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Temperature', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="range" name="bookgpt_options[temperature]" min="0" max="1" step="0.1" value="<?php echo esc_attr($options['temperature'] ?? '0.7'); ?>" id="temperature-slider">
                                    <span id="temperature-value"><?php echo esc_html($options['temperature'] ?? '0.7'); ?></span>
                                    <p class="description"><?php _e('Controls randomness (0 = deterministic, 1 = creative).', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Max Recommendations', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="number" name="bookgpt_options[max_recommendations]" min="1" max="5" value="<?php echo esc_attr($options['max_recommendations'] ?? '3'); ?>">
                                    <p class="description"><?php _e('Maximum number of book recommendations to show (1-5).', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('System Prompt', 'bookgpt-wp'); ?></th>
                                <td>
                                    <textarea name="bookgpt_options[prompt_template]" class="large-text" rows="5"><?php echo esc_textarea($options['prompt_template'] ?? 'You are a helpful book recommendation assistant. Analyze the user\'s preferences and suggest relevant books.'); ?></textarea>
                                    <p class="description"><?php _e('System prompt template used for generating recommendations.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="bookgpt-card-footer">
                        <button type="button" class="button button-primary" id="update-backend-logic"><?php _e('Update Backend Logic', 'bookgpt-wp'); ?></button>
                        <span class="bookgpt-spinner"></span>
                    </div>
                </div>
                
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Response Format Configuration', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Response Template', 'bookgpt-wp'); ?></th>
                                <td>
                                    <textarea name="bookgpt_options[response_template]" class="large-text" rows="4"><?php echo esc_textarea($options['response_template'] ?? 'Based on your preferences, I recommend these books:\n{{BOOKS}}'); ?></textarea>
                                    <p class="description"><?php _e('Template for how recommendations are presented. Use {{BOOKS}} placeholder for the book list.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Book Format Template', 'bookgpt-wp'); ?></th>
                                <td>
                                    <textarea name="bookgpt_options[book_format_template]" class="large-text" rows="3"><?php echo esc_textarea($options['book_format_template'] ?? '- **{{TITLE}}** by {{AUTHOR}}: {{DESCRIPTION}}'); ?></textarea>
                                    <p class="description"><?php _e('Template for each book. Available placeholders: {{TITLE}}, {{AUTHOR}}, {{DESCRIPTION}}, {{IMAGE}}, {{LINK}}', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'appearance'): ?>
                <!-- Widget Appearance -->
                <?php settings_fields('bookgpt_options_group'); ?>
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Widget Appearance', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Widget Title', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[chat_widget_title]" class="regular-text" value="<?php echo esc_attr($options['chat_widget_title'] ?? 'Book Recommendations'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Widget Position', 'bookgpt-wp'); ?></th>
                                <td>
                                    <select name="bookgpt_options[chat_widget_position]">
                                        <option value="bottom-right" <?php selected(($options['chat_widget_position'] ?? 'bottom-right'), 'bottom-right'); ?>><?php _e('Bottom Right', 'bookgpt-wp'); ?></option>
                                        <option value="bottom-left" <?php selected(($options['chat_widget_position'] ?? ''), 'bottom-left'); ?>><?php _e('Bottom Left', 'bookgpt-wp'); ?></option>
                                        <option value="top-right" <?php selected(($options['chat_widget_position'] ?? ''), 'top-right'); ?>><?php _e('Top Right', 'bookgpt-wp'); ?></option>
                                        <option value="top-left" <?php selected(($options['chat_widget_position'] ?? ''), 'top-left'); ?>><?php _e('Top Left', 'bookgpt-wp'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Primary Color', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="color" name="bookgpt_options[chat_widget_color]" value="<?php echo esc_attr($options['chat_widget_color'] ?? '#3b82f6'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Widget Width', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="range" name="bookgpt_options[chat_widget_width]" min="300" max="500" step="10" value="<?php echo esc_attr($options['chat_widget_width'] ?? '350'); ?>" id="width-slider">
                                    <span id="width-value"><?php echo esc_html($options['chat_widget_width'] ?? '350'); ?>px</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Widget Height', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="range" name="bookgpt_options[chat_widget_height]" min="400" max="700" step="10" value="<?php echo esc_attr($options['chat_widget_height'] ?? '500'); ?>" id="height-slider">
                                    <span id="height-value"><?php echo esc_html($options['chat_widget_height'] ?? '500'); ?>px</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Font Size', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="range" name="bookgpt_options[chat_font_size]" min="12" max="18" step="1" value="<?php echo esc_attr($options['chat_font_size'] ?? '14'); ?>" id="font-slider">
                                    <span id="font-value"><?php echo esc_html($options['chat_font_size'] ?? '14'); ?>px</span>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Custom CSS', 'bookgpt-wp'); ?></th>
                                <td>
                                    <textarea name="bookgpt_options[custom_css]" class="large-text code" rows="5"><?php echo esc_textarea($options['custom_css'] ?? ''); ?></textarea>
                                    <p class="description"><?php _e('Add custom CSS styles to customize the widget appearance further.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="bookgpt-card-footer">
                        <div class="bookgpt-widget-preview">
                            <h3><?php _e('Widget Preview', 'bookgpt-wp'); ?></h3>
                            <div class="bookgpt-preview-container">
                                <!-- Widget Preview will be rendered via JS -->
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'deployment'): ?>
                <!-- Deployment -->
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('One-Click Deployment', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <p><?php _e('Deploy your BookGPT backend to Vercel with one click. This will set up the API with all your settings automatically.', 'bookgpt-wp'); ?></p>
                        
                        <div class="bookgpt-deployment-options">
                            <div class="bookgpt-deployment-step">
                                <h3><?php _e('Step 1: Configure Environment Variables', 'bookgpt-wp'); ?></h3>
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php _e('OpenAI API Key', 'bookgpt-wp'); ?></th>
                                        <td>
                                            <input type="password" name="deployment[openai_api_key]" class="regular-text" value="<?php echo esc_attr($options['openai_api_key'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e('Google Books API Key', 'bookgpt-wp'); ?></th>
                                        <td>
                                            <input type="password" name="deployment[google_books_api_key]" class="regular-text" value="<?php echo esc_attr($options['google_books_api_key'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e('Webhook Secret', 'bookgpt-wp'); ?></th>
                                        <td>
                                            <input type="password" name="deployment[webhook_secret]" class="regular-text" value="<?php echo esc_attr($options['webhook_secret'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php _e('Amazon Associate Tag', 'bookgpt-wp'); ?></th>
                                        <td>
                                            <input type="text" name="deployment[amazon_associate_tag]" class="regular-text" value="<?php echo esc_attr($options['amazon_associate_tag'] ?? ''); ?>">
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="bookgpt-deployment-step">
                                <h3><?php _e('Step 2: Deploy to Vercel', 'bookgpt-wp'); ?></h3>
                                <p><?php _e('Click the button below to deploy your BookGPT backend to Vercel.', 'bookgpt-wp'); ?></p>
                                
                                <div class="bookgpt-deploy-buttons">
                                    <a href="#" id="deploy-to-vercel" class="button button-primary button-hero">
                                        <?php _e('Deploy to Vercel', 'bookgpt-wp'); ?>
                                    </a>
                                </div>
                                
                                <div class="bookgpt-deploy-notice">
                                    <p><?php _e('Note: Deployment requires a free Vercel account. You will be redirected to Vercel to complete the deployment.', 'bookgpt-wp'); ?></p>
                                </div>
                            </div>
                            
                            <div class="bookgpt-deployment-step">
                                <h3><?php _e('Step 3: Verify Connection', 'bookgpt-wp'); ?></h3>
                                <div class="bookgpt-deployment-verification">
                                    <div class="bookgpt-api-url-input">
                                        <label for="api_url"><?php _e('Vercel API URL:', 'bookgpt-wp'); ?></label>
                                        <input type="text" id="api_url" name="bookgpt_options[api_url]" class="regular-text" value="<?php echo esc_attr($options['api_url'] ?? ''); ?>" placeholder="https://your-bookgpt.vercel.app/api/chat">
                                    </div>
                                    <button type="button" id="verify-connection" class="button"><?php _e('Verify Connection', 'bookgpt-wp'); ?></button>
                                    <div class="bookgpt-connection-status">
                                        <span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Manual Deployment', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <p><?php _e('If you prefer to deploy manually, follow these steps:', 'bookgpt-wp'); ?></p>
                        <ol>
                            <li><?php _e('Clone the GitHub repository: <code>git clone https://github.com/yourusername/bookgptwp.git</code>', 'bookgpt-wp'); ?></li>
                            <li><?php _e('Install Vercel CLI: <code>npm install -g vercel</code>', 'bookgpt-wp'); ?></li>
                            <li><?php _e('Navigate to the project directory: <code>cd bookgptwp</code>', 'bookgpt-wp'); ?></li>
                            <li><?php _e('Create a <code>.env</code> file with the following variables:', 'bookgpt-wp'); ?>
                                <pre>OPENAI_API_KEY=your_openai_api_key
GOOGLE_BOOKS_API_KEY=your_google_books_api_key
WEBHOOK_SECRET=your_webhook_secret
AMAZON_ASSOCIATE_TAG=your_amazon_tag</pre>
                            </li>
                            <li><?php _e('Deploy to Vercel: <code>vercel deploy --prod</code>', 'bookgpt-wp'); ?></li>
                            <li><?php _e('Copy the deployment URL and paste it in the API URL field above.', 'bookgpt-wp'); ?></li>
                        </ol>
                        
                        <div class="bookgpt-manual-deploy-buttons">
                            <button type="button" id="generate-env-file" class="button"><?php _e('Generate .env File', 'bookgpt-wp'); ?></button>
                            <button type="button" id="download-deployment-script" class="button"><?php _e('Download Deploy Script', 'bookgpt-wp'); ?></button>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'affiliate'): ?>
                <!-- Affiliate Settings -->
                <?php settings_fields('bookgpt_options_group'); ?>
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Amazon Affiliate Settings', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Amazon Associate Tag', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[amazon_associate_tag]" class="regular-text" value="<?php echo esc_attr($options['amazon_associate_tag'] ?? ''); ?>">
                                    <p class="description"><?php _e('Your Amazon Associate tracking ID (e.g., yourname-20)', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Default Amazon Domain', 'bookgpt-wp'); ?></th>
                                <td>
                                    <select name="bookgpt_options[amazon_domain]">
                                        <option value=".com" <?php selected(($options['amazon_domain'] ?? '.com'), '.com'); ?>>Amazon.com (US)</option>
                                        <option value=".co.uk" <?php selected(($options['amazon_domain'] ?? ''), '.co.uk'); ?>>Amazon.co.uk (UK)</option>
                                        <option value=".ca" <?php selected(($options['amazon_domain'] ?? ''), '.ca'); ?>>Amazon.ca (Canada)</option>
                                        <option value=".de" <?php selected(($options['amazon_domain'] ?? ''), '.de'); ?>>Amazon.de (Germany)</option>
                                        <option value=".fr" <?php selected(($options['amazon_domain'] ?? ''), '.fr'); ?>>Amazon.fr (France)</option>
                                        <option value=".es" <?php selected(($options['amazon_domain'] ?? ''), '.es'); ?>>Amazon.es (Spain)</option>
                                        <option value=".it" <?php selected(($options['amazon_domain'] ?? ''), '.it'); ?>>Amazon.it (Italy)</option>
                                        <option value=".co.jp" <?php selected(($options['amazon_domain'] ?? ''), '.co.jp'); ?>>Amazon.co.jp (Japan)</option>
                                    </select>
                                    <p class="description"><?php _e('Select the Amazon domain for your affiliate links.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Link Style', 'bookgpt-wp'); ?></th>
                                <td>
                                    <select name="bookgpt_options[affiliate_link_style]">
                                        <option value="text" <?php selected(($options['affiliate_link_style'] ?? 'text'), 'text'); ?>><?php _e('Text Links', 'bookgpt-wp'); ?></option>
                                        <option value="button" <?php selected(($options['affiliate_link_style'] ?? ''), 'button'); ?>><?php _e('Button', 'bookgpt-wp'); ?></option>
                                        <option value="image" <?php selected(($options['affiliate_link_style'] ?? ''), 'image'); ?>><?php _e('Cover Image', 'bookgpt-wp'); ?></option>
                                        <option value="card" <?php selected(($options['affiliate_link_style'] ?? ''), 'card'); ?>><?php _e('Book Card', 'bookgpt-wp'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('How affiliate links will be displayed in the chat.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Link Text', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[affiliate_link_text]" class="regular-text" value="<?php echo esc_attr($options['affiliate_link_text'] ?? 'Buy on Amazon'); ?>">
                                    <p class="description"><?php _e('Text to display for affiliate links (when using button style).', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Affiliate Disclaimer', 'bookgpt-wp'); ?></th>
                                <td>
                                    <textarea name="bookgpt_options[affiliate_disclaimer]" class="large-text" rows="3"><?php echo esc_textarea($options['affiliate_disclaimer'] ?? 'As an Amazon Associate I earn from qualifying purchases.'); ?></textarea>
                                    <p class="description"><?php _e('Disclosure statement for affiliate links. Leave empty to disable.', 'bookgpt-wp'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="bookgpt-card">
                    <div class="bookgpt-card-header">
                        <h2><?php _e('Link Customization', 'bookgpt-wp'); ?></h2>
                    </div>
                    <div class="bookgpt-card-content">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('UTM Tracking', 'bookgpt-wp'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="bookgpt_options[enable_utm_tracking]" value="yes" <?php checked(($options['enable_utm_tracking'] ?? ''), 'yes'); ?>>
                                        <?php _e('Add UTM parameters to affiliate links', 'bookgpt-wp'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('UTM Source', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[utm_source]" class="regular-text" value="<?php echo esc_attr($options['utm_source'] ?? 'bookgpt'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('UTM Medium', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[utm_medium]" class="regular-text" value="<?php echo esc_attr($options['utm_medium'] ?? 'chatbot'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('UTM Campaign', 'bookgpt-wp'); ?></th>
                                <td>
                                    <input type="text" name="bookgpt_options[utm_campaign]" class="regular-text" value="<?php echo esc_attr($options['utm_campaign'] ?? 'book_recommendation'); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Open Links In', 'bookgpt-wp'); ?></th>
                                <td>
                                    <select name="bookgpt_options[link_target]">
                                        <option value="_blank" <?php selected(($options['link_target'] ?? '_blank'), '_blank'); ?>><?php _e('New Tab', 'bookgpt-wp'); ?></option>
                                        <option value="_self" <?php selected(($options['link_target'] ?? ''), '_self'); ?>><?php _e('Same Tab', 'bookgpt-wp'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="submit">
                <?php if ($active_tab !== 'deployment'): ?>
                    <?php submit_button(); ?>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update temperature slider value display
    $('#temperature-slider').on('input', function() {
        $('#temperature-value').text($(this).val());
    });
    
    // Update width slider value display
    $('#width-slider').on('input', function() {
        $('#width-value').text($(this).val() + 'px');
    });
    
    // Update height slider value display
    $('#height-slider').on('input', function() {
        $('#height-value').text($(this).val() + 'px');
    });
    
    // Update font size slider value display
    $('#font-slider').on('input', function() {
        $('#font-value').text($(this).val() + 'px');
    });
    
    // Generate webhook secret
    $('#generate-webhook-secret').on('click', function() {
        var chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
        var secret = '';
        for (var i = 0; i < 32; i++) {
            secret += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        $('input[name="bookgpt_options[webhook_secret]"], input[name="deployment[webhook_secret]"]').val(secret);
    });
    
    // Update backend logic
    $('#update-backend-logic').on('click', function() {
        var $button = $(this);
        var $spinner = $('.bookgpt-spinner');
        
        $button.prop('disabled', true);
        $spinner.show();
        
        var data = {
            action: 'bookgpt_update_backend_logic',
            nonce: bookgpt_admin.nonce,
            ai_model: $('select[name="bookgpt_options[ai_model]"]').val(),
            temperature: $('input[name="bookgpt_options[temperature]"]').val(),
            max_recommendations: $('input[name="bookgpt_options[max_recommendations]"]').val(),
            prompt_template: $('textarea[name="bookgpt_options[prompt_template]"]').val(),
            response_template: $('textarea[name="bookgpt_options[response_template]"]').val(),
            book_format_template: $('textarea[name="bookgpt_options[book_format_template]"]').val()
        };
        
        $.ajax({
            url: bookgpt_admin.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    alert('Backend logic updated successfully.');
                } else {
                    alert('Error updating backend logic: ' + response.data);
                }
                $button.prop('disabled', false);
                $spinner.hide();
            },
            error: function() {
                alert('Error updating backend logic. Please try again.');
                $button.prop('disabled', false);
                $spinner.hide();
            }
        });
    });
    
    // Deploy to Vercel
    $('#deploy-to-vercel').on('click', function(e) {
        e.preventDefault();
        
        var openaiApiKey = $('input[name="deployment[openai_api_key]"]').val();
        var googleBooksApiKey = $('input[name="deployment[google_books_api_key]"]').val();
        var webhookSecret = $('input[name="deployment[webhook_secret]"]').val();
        var amazonAssociateTag = $('input[name="deployment[amazon_associate_tag]"]').val();
        
        if (!openaiApiKey) {
            alert('OpenAI API Key is required for deployment.');
            return;
        }
        
        // Construct Vercel deployment URL with environment variables
        var deployUrl = 'https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fyourusername%2Fbookgptwp&env=OPENAI_API_KEY,GOOGLE_BOOKS_API_KEY,WEBHOOK_SECRET,AMAZON_ASSOCIATE_TAG';
        
        deployUrl += '&envDescription=API%20keys%20needed%20for%20BookGPT';
        deployUrl += '&envLink=https%3A%2F%2Fgithub.com%2Fyourusername%2Fbookgptwp%2Fblob%2Fmain%2FREADME.md';
        deployUrl += '&project-name=bookgpt-backend';
        deployUrl += '&demo-title=BookGPT%20Demo';
        deployUrl += '&demo-description=AI-powered%20book%20recommendation%20chatbot';
        deployUrl += '&demo-image=https%3A%2F%2Fopengraph.githubassets.com%2F1%2Fyourusername%2Fbookgptwp';
        
        // Add environment variable values
        deployUrl += '&OPENAI_API_KEY=' + encodeURIComponent(openaiApiKey);
        if (googleBooksApiKey) deployUrl += '&GOOGLE_BOOKS_API_KEY=' + encodeURIComponent(googleBooksApiKey);
        if (webhookSecret) deployUrl += '&WEBHOOK_SECRET=' + encodeURIComponent(webhookSecret);
        if (amazonAssociateTag) deployUrl += '&AMAZON_ASSOCIATE_TAG=' + encodeURIComponent(amazonAssociateTag);
        
        // Open Vercel deployment in a new tab
        window.open(deployUrl, '_blank');
    });
    
    // Verify API connection
    $('#verify-connection').on('click', function() {
        var apiUrl = $('#api_url').val();
        var $status = $('.bookgpt-connection-status span');
        
        if (!apiUrl) {
            $status.text('Please enter an API URL').addClass('error').removeClass('success');
            return;
        }
        
        $status.text('Testing connection...').removeClass('error success');
        
        $.ajax({
            url: bookgpt_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bookgpt_test_api_connection',
                nonce: bookgpt_admin.nonce,
                api_url: apiUrl
            },
            success: function(response) {
                if (response.success) {
                    $status.text('Connection successful! API is working.').addClass('success').removeClass('error');
                    // Update the API URL in the form
                    $('input[name="bookgpt_options[api_url]"]').val(apiUrl);
                    // Auto-submit the form to save the API URL
                    $('#bookgpt-settings-form').submit();
                } else {
                    $status.text('Connection failed: ' + response.data).addClass('error').removeClass('success');
                }
            },
            error: function() {
                $status.text('Connection failed. Please check the URL and try again.').addClass('error').removeClass('success');
            }
        });
    });
    
    // Generate .env file
    $('#generate-env-file').on('click', function() {
        var openaiApiKey = $('input[name="deployment[openai_api_key]"]').val();
        var googleBooksApiKey = $('input[name="deployment[google_books_api_key]"]').val();
        var webhookSecret = $('input[name="deployment[webhook_secret]"]').val();
        var amazonAssociateTag = $('input[name="deployment[amazon_associate_tag]"]').val();
        
        var envContent = '';
        
        if (openaiApiKey) envContent += 'OPENAI_API_KEY=' + openaiApiKey + '\n';
        if (googleBooksApiKey) envContent += 'GOOGLE_BOOKS_API_KEY=' + googleBooksApiKey + '\n';
        if (webhookSecret) envContent += 'WEBHOOK_SECRET=' + webhookSecret + '\n';
        if (amazonAssociateTag) envContent += 'AMAZON_ASSOCIATE_TAG=' + amazonAssociateTag + '\n';
        
        if (!envContent) {
            alert('Please fill in at least one environment variable.');
            return;
        }
        
        // Create and download the .env file
        var blob = new Blob([envContent], { type: 'text/plain' });
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = '.env';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    // Download deployment script
    $('#download-deployment-script').on('click', function() {
        var scriptContent = '#!/bin/bash\n\n';
        scriptContent += '# BookGPT Deployment Script\n';
        scriptContent += '# This script clones the BookGPT repository and deploys it to Vercel\n\n';
        scriptContent += 'echo "BookGPT Deployment Script"\n';
        scriptContent += 'echo "------------------------"\n\n';
        scriptContent += '# Check if Git is installed\n';
        scriptContent += 'if ! [ -x "$(command -v git)" ]; then\n';
        scriptContent += '  echo "Error: Git is not installed." >&2\n';
        scriptContent += '  exit 1\n';
        scriptContent += 'fi\n\n';
        scriptContent += '# Check if Node.js is installed\n';
        scriptContent += 'if ! [ -x "$(command -v node)" ]; then\n';
        scriptContent += '  echo "Error: Node.js is not installed." >&2\n';
        scriptContent += '  exit 1\n';
        scriptContent += 'fi\n\n';
        scriptContent += '# Check if npm is installed\n';
        scriptContent += 'if ! [ -x "$(command -v npm)" ]; then\n';
        scriptContent += '  echo "Error: npm is not installed." >&2\n';
        scriptContent += '  exit 1\n';
        scriptContent += 'fi\n\n';
        scriptContent += '# Clone the repository\n';
        scriptContent += 'echo "Cloning BookGPT repository..."\n';
        scriptContent += 'git clone https://github.com/yourusername/bookgptwp.git\n';
        scriptContent += 'cd bookgptwp\n\n';
        scriptContent += '# Install Vercel CLI if not installed\n';
        scriptContent += 'echo "Installing Vercel CLI..."\n';
        scriptContent += 'npm install -g vercel\n\n';
        scriptContent += '# Check if .env file exists\n';
        scriptContent += 'if [ -f ".env" ]; then\n';
        scriptContent += '  echo "Using existing .env file."\n';
        scriptContent += 'else\n';
        scriptContent += '  echo "Creating .env file..."\n';
        scriptContent += '  echo "Please enter your OpenAI API Key:"\n';
        scriptContent += '  read OPENAI_API_KEY\n';
        scriptContent += '  echo "OPENAI_API_KEY=$OPENAI_API_KEY" > .env\n\n';
        scriptContent += '  echo "Please enter your Google Books API Key (leave empty if none):"\n';
        scriptContent += '  read GOOGLE_BOOKS_API_KEY\n';
        scriptContent += '  if [ ! -z "$GOOGLE_BOOKS_API_KEY" ]; then\n';
        scriptContent += '    echo "GOOGLE_BOOKS_API_KEY=$GOOGLE_BOOKS_API_KEY" >> .env\n';
        scriptContent += '  fi\n\n';
        scriptContent += '  echo "Please enter your Webhook Secret (leave empty to generate one):"\n';
        scriptContent += '  read WEBHOOK_SECRET\n';
        scriptContent += '  if [ -z "$WEBHOOK_SECRET" ]; then\n';
        scriptContent += '    WEBHOOK_SECRET=$(openssl rand -base64 32)\n';
        scriptContent += '  fi\n';
        scriptContent += '  echo "WEBHOOK_SECRET=$WEBHOOK_SECRET" >> .env\n\n';
        scriptContent += '  echo "Please enter your Amazon Associate Tag (leave empty if none):"\n';
        scriptContent += '  read AMAZON_ASSOCIATE_TAG\n';
        scriptContent += '  if [ ! -z "$AMAZON_ASSOCIATE_TAG" ]; then\n';
        scriptContent += '    echo "AMAZON_ASSOCIATE_TAG=$AMAZON_ASSOCIATE_TAG" >> .env\n';
        scriptContent += '  fi\n';
        scriptContent += 'fi\n\n';
        scriptContent += '# Deploy to Vercel\n';
        scriptContent += 'echo "Deploying to Vercel..."\n';
        scriptContent += 'vercel deploy --prod\n\n';
        scriptContent += 'echo "Deployment complete!"\n';
        scriptContent += 'echo "Please copy the deployment URL and paste it in your WordPress settings."\n';
        
        // Create and download the script file
        var blob = new Blob([scriptContent], { type: 'text/plain' });
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'deploy-bookgpt.sh';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>