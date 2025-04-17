<?php
/**
 * Admin dashboard display
 */
// Prevent direct access
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap bookgpt-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="bookgpt-admin-header">
        <div class="bookgpt-version">
            <span><?php echo sprintf(__('BookGPT v%s', 'bookgpt-wp'), BOOKGPT_VERSION); ?></span>
        </div>
        <div class="bookgpt-quick-actions">
            <a href="<?php echo admin_url('admin.php?page=bookgpt-settings'); ?>" class="button"><?php _e('Settings', 'bookgpt-wp'); ?></a>
            <a href="<?php echo admin_url('admin.php?page=bookgpt-analytics'); ?>" class="button"><?php _e('Detailed Analytics', 'bookgpt-wp'); ?></a>
        </div>
    </div>
    
    <div class="bookgpt-dashboard-overview">
        <div class="bookgpt-card-container">
            <!-- Conversations Stats -->
            <div class="bookgpt-card">
                <div class="bookgpt-card-header">
                    <h2><?php _e('Conversation Stats', 'bookgpt-wp'); ?></h2>
                </div>
                <div class="bookgpt-card-content">
                    <div class="bookgpt-stat-grid">
                        <div class="bookgpt-stat">
                            <h3><?php echo absint($total_conversations); ?></h3>
                            <p><?php _e('Total Conversations', 'bookgpt-wp'); ?></p>
                        </div>
                        <div class="bookgpt-stat">
                            <h3><?php echo absint($total_recommendations); ?></h3>
                            <p><?php _e('Books Recommended', 'bookgpt-wp'); ?></p>
                        </div>
                        <div class="bookgpt-stat">
                            <h3><?php echo absint($total_clicks); ?></h3>
                            <p><?php _e('Amazon Clicks', 'bookgpt-wp'); ?></p>
                        </div>
                        <div class="bookgpt-stat">
                            <h3><?php echo esc_html($click_through_rate); ?>%</h3>
                            <p><?php _e('Click Rate', 'bookgpt-wp'); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bookgpt-card-footer">
                    <a href="<?php echo admin_url('admin.php?page=bookgpt-analytics'); ?>"><?php _e('View Detailed Metrics →', 'bookgpt-wp'); ?></a>
                </div>
            </div>
            
            <!-- API Usage Stats -->
            <div class="bookgpt-card">
                <div class="bookgpt-card-header">
                    <h2><?php _e('API Usage', 'bookgpt-wp'); ?></h2>
                </div>
                <div class="bookgpt-card-content">
                    <div class="bookgpt-stat-grid">
                        <div class="bookgpt-stat">
                            <h3>$<?php echo number_format((float)$monthly_api_cost, 2); ?></h3>
                            <p><?php _e('Monthly Cost', 'bookgpt-wp'); ?></p>
                        </div>
                        <div class="bookgpt-stat">
                            <h3><?php echo number_format($total_tokens); ?></h3>
                            <p><?php _e('Tokens Used', 'bookgpt-wp'); ?></p>
                        </div>
                    </div>
                    <div class="bookgpt-api-chart">
                        <canvas id="apiUsageChart" height="200"></canvas>
                    </div>
                </div>
                <div class="bookgpt-card-footer">
                    <a href="<?php echo admin_url('admin.php?page=bookgpt-analytics#api-usage'); ?>"><?php _e('View API Usage Details →', 'bookgpt-wp'); ?></a>
                </div>
            </div>
            
            <!-- Quick Deploy -->
            <div class="bookgpt-card">
                <div class="bookgpt-card-header">
                    <h2><?php _e('Deployment', 'bookgpt-wp'); ?></h2>
                </div>
                <div class="bookgpt-card-content">
                    <p><?php _e('Deploy your BookGPT backend to Vercel with one click.', 'bookgpt-wp'); ?></p>
                    
                    <div class="bookgpt-deployment-status">
                        <?php
                        $options = get_option('bookgpt_options');
                        $api_url = !empty($options['api_url']) ? $options['api_url'] : '';
                        $status_class = !empty($api_url) ? 'connected' : 'disconnected';
                        $status_text = !empty($api_url) ? __('Connected', 'bookgpt-wp') : __('Not Connected', 'bookgpt-wp');
                        ?>
                        <div class="bookgpt-status <?php echo esc_attr($status_class); ?>">
                            <span class="status-indicator"></span>
                            <span class="status-text"><?php echo esc_html($status_text); ?></span>
                        </div>
                        
                        <?php if (!empty($api_url)): ?>
                        <div class="bookgpt-api-url">
                            <p><strong><?php _e('API URL:', 'bookgpt-wp'); ?></strong> <code><?php echo esc_html($api_url); ?></code></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="bookgpt-deploy-actions">
                        <a href="<?php echo admin_url('admin.php?page=bookgpt-settings&tab=deployment'); ?>" class="button button-primary"><?php _e('Deploy Backend', 'bookgpt-wp'); ?></a>
                        <?php if (!empty($api_url)): ?>
                        <button class="button bookgpt-test-connection"><?php _e('Test Connection', 'bookgpt-wp'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Test API connection
    $('.bookgpt-test-connection').on('click', function() {
        var $button = $(this);
        var originalText = $button.text();
        
        $button.text('Testing...').prop('disabled', true);
        
        $.ajax({
            url: bookgpt_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'bookgpt_test_api_connection',
                nonce: bookgpt_admin.nonce,
                api_url: '<?php echo esc_js($api_url); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Success: ' + response.data);
                } else {
                    alert('Error: ' + response.data);
                }
                $button.text(originalText).prop('disabled', false);
            },
            error: function() {
                alert('Connection error. Please try again.');
                $button.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // API Usage Chart
    var ctx = document.getElementById('apiUsageChart').getContext('2d');
    var chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['7 Days Ago', '6 Days Ago', '5 Days Ago', '4 Days Ago', '3 Days Ago', '2 Days Ago', 'Yesterday', 'Today'],
            datasets: [{
                label: 'API Tokens Used',
                data: [0, 0, 0, 0, 0, 0, 0, 0],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Load API usage data
    $.ajax({
        url: bookgpt_admin.ajax_url,
        type: 'POST',
        data: {
            action: 'bookgpt_get_analytics',
            nonce: bookgpt_admin.nonce,
            period: '7days',
            type: 'api_usage'
        },
        success: function(response) {
            if (response.success && response.data) {
                chart.data.labels = response.data.labels;
                chart.data.datasets[0].data = response.data.values;
                chart.update();
            }
        },
        error: function(xhr, status, error) {
            console.error('Failed to load API usage data:', error);
            // Display a user-friendly error message in the chart area
            $('.bookgpt-api-chart').html('<p class="bookgpt-error-message">Failed to load analytics data. Please try again later.</p>');
        },
        complete: function() {
            // Add toggle button for chart display options after chart is loaded
            $('.bookgpt-card-header:contains("API Usage")').append(
                '<div class="bookgpt-chart-controls">' +
                '<button type="button" class="button toggle-chart-type" data-current="line">Toggle Chart Type</button>' +
                '</div>'
            );
            
            // Handle toggle chart type button
            $('.toggle-chart-type').on('click', function() {
                var currentType = $(this).data('current');
                var newType = currentType === 'line' ? 'bar' : 'line';
                
                // Update chart type
                chart.config.type = newType;
                chart.update();
                
                // Update button data attribute
                $(this).data('current', newType);
            });
        }
    });
});
</script>