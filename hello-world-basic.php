<?php

/**
 * Plugin Name: Hello World Basic
 * Plugin URI: https://github.com/big-hero-dev/hello-world-basic
 * Description: A simple hello world plugin for WordPress with GitHub auto-update
 * Version: 1.0.0
 * Author: Khanh Tran
 * License: GPL v2 or later
 * Text Domain: hello-world-basic
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Define plugin constants
define('HW_PLUGIN_VERSION', '1.0.0');
define('HW_PLUGIN_FILE', __FILE__);
define('HW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HW_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include the updater class
require_once HW_PLUGIN_DIR . 'includes/class-github-updater.php';

// Initialize the updater
function hw_init_updater()
{
	if (is_admin()) {
		new HW_GitHub_Updater([
			'slug' => 'hello-world-basic',
			'plugin_file' => __FILE__,
			'github_username' => 'big-hero-dev',
			'github_repo' => 'hello-world-basic',
			'github_token' => '', // Optional: for private repos
		]);
	}
}
add_action('init', 'hw_init_updater');

// Rest of your plugin code...
// [Previous code remains the same]
