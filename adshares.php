<?php
/**
 * Plugin Name: Adshares
 * Plugin URI: http://wordpress.org/plugins/adshares/
 * Description: The easiest way to connect your site to the Adshares network
 * Author: Adshares
 * Version: 0.0.1
 * Author URI: https://adshares.pl
 * Text Domain: adshares
 * License: GPLv3
 *
 * Based on AdSense Plugin WP QUADS
 * https://pl.wordpress.org/plugins/quick-adsense-reloaded/
 */
/**
 * Copyright (C) 2019 Adshares sp. z o.o.
 *
 * This file is part of Adshares WordPress Plugin
 *
 * Adshares WordPress Plugin is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Adshares WordPress Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Adshares WordPress Plugin. If not, see
 * <https://www.gnu.org/licenses/>
 */
/**
 * @package Adshares
 * @version 0.0.1
 */

// Exit if accessed directly
if (!function_exists('add_action')) {
    echo 'Direct access is forbidden.';
    exit;
}

define('ADSHARES_VERSION', '0.0.1');
define('ADSHARES_MINIMUM_WP_VERSION', '4.0');
define('ADSHARES_TEMPLATES', plugin_dir_path(__FILE__) . 'templates');
define('ADSHARES_CACHE', plugin_dir_path(__FILE__) . 'cache');
define('ADSHARES_ASSETS', plugin_dir_url(__FILE__) . 'assets');

require plugin_dir_path(__FILE__) . '/vendor/autoload.php';

register_activation_hook(__FILE__, array('Adshares\WordPress\Plugin', 'handleActivation'));
register_deactivation_hook(__FILE__, array('Adshares\WordPress\Plugin', 'handleDeactivation'));

add_action('init', array('Adshares\WordPress\Plugin', 'handleInit'));

if (is_admin() || (defined('WP_CLI') && WP_CLI)) {
    add_action('init', array('Adshares\WordPress\Admin', 'handleInit'));
}
