<?php
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

namespace Adshares\WordPress;

class Plugin
{
    public static function init()
    {
        // not implemented
    }

    public static function activate()
    {
        // First time installation
        // Get all settings and update them only if they are empty
        $quads_options = get_option('quads_settings');
        if (!$quads_options) {
            $quads_options['post_types'] = array('post', 'page');
            $quads_options['visibility']['AppHome'] = '1';
            $quads_options['visibility']['AppCate'] = '1';
            $quads_options['visibility']['AppArch'] = '1';
            $quads_options['visibility']['AppTags'] = '1';
            $quads_options['quicktags']['QckTags'] = '1';

            update_option('adshares_settings', $quads_options);
        }
    }

    public static function handleActivation()
    {
        if (version_compare($GLOBALS['wp_version'], ADSHARES_MINIMUM_WP_VERSION, '<')) {
            load_plugin_textdomain('adshares');
            throw new \RuntimeException(sprintf(
                __('Adshares %s requires WordPress %s or higher.', 'adshares'),
                ADSHARES_VERSION,
                ADSHARES_MINIMUM_WP_VERSION
            ));
        }

        // check if WordPress Multisite is enabled
        if (function_exists('is_multisite') && is_multisite()) {
            global $wpdb;
            $currentBlogId = $wpdb->blogid;
            // Get all blog ids
            $blogIds = $wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
            foreach ($blogIds as $blogId) {
                switch_to_blog($blogId);
                self::activate();
            }
            switch_to_blog($currentBlogId);
            return;
        }

        self::activate();
    }

    public static function handleDeactivation()
    {
        // not implemented
    }
}
