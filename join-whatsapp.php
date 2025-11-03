<?php

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not permitted.');
}

// =============================================================================
// Configuração -> modificar dentro da aba de configs do plugin no wordpress.
// =============================================================================

define('WHATSAPP_DEFAULT_GROUP_LINK', 'https://chat.whatsapp.com/EUi5lGtwD65GRTvpYjmofK');
define('WHATSAPP_DEFAULT_SLUG', 'whatsapp-redirect');


function whatsapp_get_group_link() {
    return get_option('whatsapp_group_link', WHATSAPP_DEFAULT_GROUP_LINK);
}

function whatsapp_get_redirect_slug() {
    return get_option('whatsapp_redirect_slug', WHATSAPP_DEFAULT_SLUG);
}

// =============================================================================
// REGISTER CUSTOM ENDPOINT
// =============================================================================

/**
 * custom rewrite rule for wpp redir
 */
function whatsapp_redirect_add_rewrite_rule() {
    $slug = whatsapp_get_redirect_slug();
    add_rewrite_rule(
        '^' . $slug . '/?$',
        'index.php?whatsapp_redirect=1',
        'top'
    );
}
add_action('init', 'whatsapp_redirect_add_rewrite_rule');

/**
 * custom query variable
 */
function whatsapp_redirect_add_query_var($vars) {
    $vars[] = 'whatsapp_redirect';
    return $vars;
}
add_filter('query_vars', 'whatsapp_redirect_add_query_var');

/**
 * redirect request handler
 */
function whatsapp_redirect_handle_request() {
    global $wp_query;

    // custom endpoint check
    if (isset($wp_query->query_vars['whatsapp_redirect']) && $wp_query->query_vars['whatsapp_redirect'] == '1') {

        // get form data
        $form_data = array(
            'name'  => isset($_GET['name']) ? sanitize_text_field($_GET['name']) : '',
            'email' => isset($_GET['email']) ? sanitize_email($_GET['email']) : '',
            'phone' => isset($_GET['phone']) ? sanitize_text_field($_GET['phone']) : '',
            'source' => 'whatsapp_redirect',
            'timestamp' => current_time('mysql'),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '',
        );

        // Log data if email is present
        if (!empty($form_data['email'])) {
            whatsapp_redirect_log_lead($form_data);
        }

        // junk, dont mind
        // whatsapp_redirect_trigger_webhook($form_data);

        // RIP auto redirect, instead LP -> JS
        // allows custom URL schemes to open the app
        whatsapp_redirect_landing_page();
        exit;
    }
}
add_action('template_redirect', 'whatsapp_redirect_handle_request');

// =============================================================================
// LANDING PAGE WITH APP OPENING JAVASCRIPT
// =============================================================================

/**
 * LP
 * cus HTTP redir wont trigger app opening
 */
function whatsapp_redirect_landing_page() {
    $whatsapp_link = whatsapp_get_group_link();
    $group_code = str_replace('https://chat.whatsapp.com/', '', $whatsapp_link);

    // Disable caching
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Entrando no Grupo...</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-align: center;
                padding: 20px;
            }
            .container {
                max-width: 400px;
            }
            .spinner {
                width: 60px;
                height: 60px;
                border: 5px solid rgba(255, 255, 255, 0.3);
                border-top-color: white;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 0 auto 30px;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            h1 {
                font-size: 24px;
                margin-bottom: 15px;
            }
            p {
                font-size: 16px;
                opacity: 0.9;
                margin-bottom: 10px;
            }
            .whatsapp-icon {
                width: 80px;
                height: 80px;
                margin-bottom: 20px;
            }
            .fallback-link {
                display: none;
                margin-top: 30px;
                padding: 15px 30px;
                background: white;
                color: #667eea;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                font-size: 16px;
                transition: transform 0.2s;
            }
            .fallback-link:hover {
                transform: translateY(-2px);
            }
            .fallback-link.show {
                display: inline-block;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <svg class="whatsapp-icon" viewBox="0 0 24 24" fill="white" xmlns="http://www.w3.org/2000/svg">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>

            <div class="spinner"></div>
            <h1>Entrando no Grupo...</h1>
            <p>Abrindo WhatsApp...</p>

            <a href="<?php echo esc_url($whatsapp_link); ?>" class="fallback-link" id="fallback-link">
                Clique aqui se não abrir automaticamente
            </a>
        </div>

        <script>
        (function() {
            'use strict';

            var groupCode = '<?php echo esc_js($group_code); ?>';
            var universalLink = '<?php echo esc_js($whatsapp_link); ?>';
            var appOpened = false;

            // Detect mobile OS
            var isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
            var isAndroid = /Android/i.test(navigator.userAgent);
            var isMobile = isIOS || isAndroid;

            // Multiple URL schemes to try
            var urls = {
                // Custom scheme - forces app open
                customScheme: 'whatsapp://chat?code=' + groupCode,
                // Universal link
                universal: universalLink
            };

            // Monitor if app opened
            var startTime = Date.now();

            var visibilityHandler = function() {
                if (document.hidden) {
                    appOpened = true;
                }
            };

            var blurHandler = function() {
                appOpened = true;
            };

            document.addEventListener('visibilitychange', visibilityHandler);
            window.addEventListener('blur', blurHandler);
            window.addEventListener('pagehide', blurHandler);

            // Try to open app
            function tryOpenApp() {
                if (!isMobile) {
                    // Desktop - just open universal link
                    window.location.href = universalLink;
                    return;
                }

                // Mobile - try custom scheme first, then universal link
                var iframe = document.createElement('iframe');
                iframe.style.display = 'none';
                document.body.appendChild(iframe);

                // Try custom scheme
                iframe.src = urls.customScheme;

                // After 1.5s, if app didn't open, try universal link
                setTimeout(function() {
                    if (!appOpened && !document.hidden) {
                        console.log('Custom scheme failed, trying universal link...');
                        window.location.href = urls.universal;
                    }

                    // Remove iframe
                    try {
                        document.body.removeChild(iframe);
                    } catch(e) {}
                }, 1500);

                // After 3s total, show fallback button
                setTimeout(function() {
                    if (!appOpened && !document.hidden) {
                        document.getElementById('fallback-link').classList.add('show');
                    }
                }, 3000);
            }

            // Start immediately
            tryOpenApp();

            // Cleanup
            setTimeout(function() {
                document.removeEventListener('visibilitychange', visibilityHandler);
                window.removeEventListener('blur', blurHandler);
                window.removeEventListener('pagehide', blurHandler);
            }, 5000);
        })();
        </script>
    </body>
    </html>
    <?php
}

// =============================================================================
// DATA LOGGING
// =============================================================================

/**
 * Log lead data to WordPress options
 */
function whatsapp_redirect_log_lead($data) {
    // get
    $leads = get_option('whatsapp_redirect_leads', array());

    // add
    $leads[] = $data;

    // Keep only last 10000
    if (count($leads) > 10000) {
        $leads = array_slice($leads, -10000);
    }

    // Save back to options
    update_option('whatsapp_redirect_leads', $leads);

    // opt: log to debug.log if WP_DEBUG is enabled
    if (defined('WP_DEBUG') && WP_DEBUG === true) {
        error_log(sprintf(
            'WhatsApp Redirect: %s (%s) - Phone: %s',
            $data['name'],
            $data['email'],
            $data['phone']
        ));
    }
}

// =============================================================================
// igore, just webhook event
// =============================================================================

function whatsapp_redirect_trigger_webhook($data) {
    
    $webhook_url = '';

    if (empty($webhook_url) || empty($data['email'])) {
        return;
    }

    wp_remote_post($webhook_url, array(
        'body' => array(
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'source' => 'whatsapp_redirect_plugin',
            'timestamp' => $data['timestamp']
        ),
        'timeout' => 5,
        'blocking' => false, // Don't wait for response
    ));
}

// =============================================================================
// ADMIN MENU - VIEW LEADS
// =============================================================================

/**
 * Add admin menu to view leads and settings
 */
function whatsapp_redirect_admin_menu() {
    // Main menu - Leads
    add_menu_page(
        'WhatsApp Leads',           // Page title
        'WhatsApp Leads',           // Menu title
        'manage_options',           // Capability
        'whatsapp-redirect-leads',  // Menu slug
        'whatsapp_redirect_leads_page', // Callback function
        'dashicons-whatsapp',       // Icon
        30                          // Position
    );

    // Submenu - Settings
    add_submenu_page(
        'whatsapp-redirect-leads',     // Parent slug
        'WhatsApp Settings',           // Page title
        'Settings',                    // Menu title
        'manage_options',              // Capability
        'whatsapp-redirect-settings',  // Menu slug
        'whatsapp_redirect_settings_page' // Callback function
    );
}
add_action('admin_menu', 'whatsapp_redirect_admin_menu');

/**
 * Display settings page in admin
 */
function whatsapp_redirect_settings_page() {
    // Save settings
    if (isset($_POST['whatsapp_save_settings']) && check_admin_referer('whatsapp_settings_nonce')) {
        $group_link = isset($_POST['whatsapp_group_link']) ? esc_url_raw($_POST['whatsapp_group_link']) : '';
        $redirect_slug = isset($_POST['whatsapp_redirect_slug']) ? sanitize_title($_POST['whatsapp_redirect_slug']) : 'whatsapp-redirect';

        // Validate WhatsApp group link
        if (strpos($group_link, 'chat.whatsapp.com') === false) {
            echo '<div class="notice notice-error"><p>Invalid WhatsApp group link. Must be a chat.whatsapp.com URL.</p></div>';
        } else {
            // Save settings
            update_option('whatsapp_group_link', $group_link);
            update_option('whatsapp_redirect_slug', $redirect_slug);

            // Flush rewrite rules to update the URL
            flush_rewrite_rules();

            echo '<div class="notice notice-success"><p>Settings saved successfully! Make sure to test your redirect.</p></div>';
        }
    }

    $current_group_link = whatsapp_get_group_link();
    $current_slug = whatsapp_get_redirect_slug();
    ?>
    <div class="wrap">
        <h1>WhatsApp Redirect Settings</h1>

        <form method="post" action="">
            <?php wp_nonce_field('whatsapp_settings_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="whatsapp_group_link">WhatsApp Group Link</label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="whatsapp_group_link"
                            name="whatsapp_group_link"
                            value="<?php echo esc_attr($current_group_link); ?>"
                            class="regular-text"
                            placeholder="https://chat.whatsapp.com/YOUR_GROUP_CODE"
                            required
                        />
                        <p class="description">
                            Your WhatsApp group invitation link. Must start with <code>https://chat.whatsapp.com/</code>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="whatsapp_redirect_slug">Redirect URL Path</label>
                    </th>
                    <td>
                        <code><?php echo home_url('/'); ?></code>
                        <input
                            type="text"
                            id="whatsapp_redirect_slug"
                            name="whatsapp_redirect_slug"
                            value="<?php echo esc_attr($current_slug); ?>"
                            class="regular-text"
                            placeholder="whatsapp-redirect"
                            required
                        />
                        <code>/</code>
                        <p class="description">
                            The URL path for your redirect page. Example: <code><?php echo home_url('/' . $current_slug . '/'); ?></code>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="whatsapp_save_settings" class="button button-primary">
                    Save Settings
                </button>
                <a href="<?php echo admin_url('admin.php?page=whatsapp-redirect-leads'); ?>" class="button">
                    View Leads
                </a>
            </p>
        </form>

        <div class="card" style="max-width: 800px; margin-top: 30px;">
            <h2>Current Configuration</h2>
            <table class="widefat">
                <tr>
                    <td><strong>Redirect URL:</strong></td>
                    <td>
                        <code><?php echo home_url('/' . $current_slug . '/'); ?></code>
                        <a href="<?php echo home_url('/' . $current_slug . '/'); ?>?name=Test&email=test@test.com&phone=123" target="_blank" class="button button-small" style="margin-left: 10px;">Test Redirect</a>
                    </td>
                </tr>
                <tr>
                    <td><strong>WhatsApp Group:</strong></td>
                    <td>
                        <a href="<?php echo esc_url($current_group_link); ?>" target="_blank"><?php echo esc_html($current_group_link); ?></a>
                    </td>
                </tr>
            </table>
        </div>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>How to Get Your WhatsApp Group Link</h2>
            <ol>
                <li>Open your WhatsApp group on your phone</li>
                <li>Tap the group name at the top</li>
                <li>Scroll down and tap <strong>"Invite via link"</strong></li>
                <li>Tap <strong>"Copy link"</strong></li>
                <li>Paste the link here (should look like: <code>https://chat.whatsapp.com/ABC123xyz</code>)</li>
            </ol>
        </div>

        <div class="card" style="max-width: 800px; margin-top: 20px;">
            <h2>Important Notes</h2>
            <ul>
                <li>After changing settings, WordPress will automatically update your URL rules</li>
                <li>If the redirect stops working, go to <a href="<?php echo admin_url('options-permalink.php'); ?>">Settings → Permalinks</a> and click "Save Changes"</li>
                <li>Make sure to update your Elementor form redirect URL if you change the slug</li>
                <li>Test the redirect after making changes by clicking the "Test Redirect" button above</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Display leads page in admin
 */
function whatsapp_redirect_leads_page() {
    $leads = get_option('whatsapp_redirect_leads', array());
    $total_leads = count($leads);

    // Handle export
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        whatsapp_redirect_export_csv($leads);
        exit;
    }

    // Handle clear leads
    if (isset($_POST['clear_leads']) && check_admin_referer('clear_whatsapp_leads')) {
        update_option('whatsapp_redirect_leads', array());
        $leads = array();
        echo '<div class="notice notice-success"><p>Leads cleared successfully!</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>
            WhatsApp Redirect Leads
            <a href="?page=whatsapp-redirect-leads&export=csv" class="button button-primary" style="margin-left: 10px;">
                Export CSV
            </a>
        </h1>

        <div class="card" style="max-width: 100%; margin-top: 20px;">
            <h2>Statistics</h2>
            <p><strong>Total Leads:</strong> <?php echo $total_leads; ?></p>
            <p><strong>Redirect URL:</strong> <code><?php echo home_url('/' . whatsapp_get_redirect_slug() . '/'); ?></code></p>
            <p><strong>WhatsApp Group:</strong> <a href="<?php echo esc_url(whatsapp_get_group_link()); ?>" target="_blank"><?php echo esc_html(whatsapp_get_group_link()); ?></a></p>
            <p><a href="<?php echo admin_url('admin.php?page=whatsapp-redirect-settings'); ?>" class="button">Change Settings</a></p>
        </div>

        <?php if ($total_leads > 0) : ?>
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('clear_whatsapp_leads'); ?>
                <button type="submit" name="clear_leads" class="button button-secondary" onclick="return confirm('Are you sure you want to delete all leads?');">
                    Clear All Leads
                </button>
            </form>

            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 20%;">Name</th>
                        <th style="width: 25%;">Email</th>
                        <th style="width: 15%;">Phone</th>
                        <th style="width: 20%;">Date/Time</th>
                        <th style="width: 15%;">Source</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = $total_leads;
                    foreach (array_reverse($leads) as $lead) :
                    ?>
                    <tr>
                        <td><?php echo $counter--; ?></td>
                        <td><?php echo esc_html($lead['name']); ?></td>
                        <td><?php echo esc_html($lead['email']); ?></td>
                        <td><?php echo esc_html($lead['phone']); ?></td>
                        <td><?php echo esc_html($lead['timestamp']); ?></td>
                        <td><?php echo esc_html($lead['source']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-info" style="margin-top: 20px;">
                <p>No leads yet. Leads will appear here after someone submits the form.</p>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width: 100%; margin-top: 30px;">
            <h2>How to Use</h2>
            <p><strong>Step 1:</strong> Set your Elementor form to redirect to:</p>
            <code style="display: block; padding: 10px; background: #f0f0f0; margin: 10px 0;">
                <?php echo home_url('/' . whatsapp_get_redirect_slug() . '/'); ?>?name=[field id="name"]&email=[field id="email"]&phone=[field id="phone"]
            </code>
            <p><strong>Step 2:</strong> Test by visiting:</p>
            <code style="display: block; padding: 10px; background: #f0f0f0; margin: 10px 0;">
                <?php echo home_url('/' . whatsapp_get_redirect_slug() . '/'); ?>?name=Test&email=test@test.com&phone=123456789
            </code>
            <p><strong>Step 3:</strong> Submit your form and check that leads appear here!</p>
        </div>
    </div>
    <?php
}

/**
 * Export leads to CSV
 */
function whatsapp_redirect_export_csv($leads) {
    $filename = 'whatsapp-leads-' . date('Y-m-d-His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // CSV headers
    fputcsv($output, array('Name', 'Email', 'Phone', 'Date/Time', 'Source', 'User Agent', 'IP Address'));

    // CSV rows
    foreach ($leads as $lead) {
        fputcsv($output, array(
            $lead['name'],
            $lead['email'],
            $lead['phone'],
            $lead['timestamp'],
            $lead['source'],
            $lead['user_agent'],
            $lead['ip_address']
        ));
    }

    fclose($output);
}

// =============================================================================
// PLUGIN ACTIVATION
// =============================================================================

/**
 * On plugin activation, flush rewrite rules
 */
function whatsapp_redirect_activate() {
    whatsapp_redirect_add_rewrite_rule();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'whatsapp_redirect_activate');

/**
 * On plugin deactivation, flush rewrite rules
 */
function whatsapp_redirect_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'whatsapp_redirect_deactivate');

// =============================================================================
// ADMIN NOTICE - REMIND TO FLUSH PERMALINKS IF NEEDED
// =============================================================================

/**
 * Show admin notice if redirect doesn't work
 */
function whatsapp_redirect_admin_notice() {
    // Check if endpoint works
    $test_url = home_url('/' . whatsapp_get_redirect_slug() . '/');

    // Only show notice on plugin pages
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_whatsapp-redirect-leads') {
        ?>
        <div class="notice notice-info">
            <p>
                <strong>Test your redirect:</strong>
                <a href="<?php echo $test_url; ?>?name=Test&email=test@test.com&phone=123" target="_blank">
                    Click here to test
                </a>
                (should redirect to WhatsApp)
            </p>
            <p>
                <strong>If redirect doesn't work:</strong> Go to
                <a href="<?php echo admin_url('options-permalink.php'); ?>">Settings → Permalinks</a>
                and click "Save Changes" to refresh URL rules.
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'whatsapp_redirect_admin_notice');
