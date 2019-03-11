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
    const P_OPEN_TAG = '<p>';
    const P_CLOSE_TAG = '</p>';
    const ADSH_PARAGRAPH_MARKER = '###ADSH_PARAGRAPH_OPEN###';

    private static $instance = null;
    private $initiated = false;
    private $blockquotes;

    /**
     * Create singleton.
     *
     * @return Plugin
     */
    private static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Handle initiating event.
     */
    public static function handleInit()
    {
        $admin = self::getInstance();
        $admin->initHooks();
    }

    /**
     * Activate page hook.
     */
    public static function activate()
    {
        $settings = get_option('adshares_settings');
        if (!$settings) {
            $settings = [];
        }
        $settings = array_merge([
            'postTypes' => ['post', 'page'],
            'visibility' => [
                'homepage' => '1',
                'categories' => '1',
                'archives' => '1',
                'tags' => '1',
            ]
        ], $settings);
//            $quads_options['quicktags']['QckTags'] = '1';

        update_option('adshares_settings', $settings);
    }

    /**
     * Handle plugin activation.
     */
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

    /**
     * Handle plugin deactivation.
     */
    public static function handleDeactivation()
    {
        // not implemented
    }

    /**
     * Init hooks.
     */
    public function initHooks()
    {
        if ($this->initiated) {
            return;
        }
        $this->initiated = true;

        add_action('wp_head', [$this, 'loadAdsScript']);
        add_filter('the_content', [$this, 'filterContent'], 20);
        add_filter('get_the_excerpt', [$this, 'filterExcerpt'], 20);
    }

    /**
     * Loading resources hook.
     */
    public function loadAdsScript()
    {
        $sites = get_option('adshares_sites');
        if (isset($sites[0]['code'])) {
            echo $sites[0]['code'];
        }
    }

    /**
     * Filter post content.
     *
     * @param $content post content
     * @return string
     */
    public function filterContent($content)
    {
        if (!$this->isAdAllowed($content) || !is_main_query()) {
            $content = $this->cleanContent($content);
            return $content;
        }

        $content = $this->sanitizeContent($content);
        $content = $this->insertAds($content);
        $content = $this->cleanContent($content);

        return do_shortcode($content);
    }

    /**
     * Filter post excerpt.
     *
     * @param $content post excerpt
     * @return string
     */
    public function filterExcerpt($excerpt)
    {
        if ($this->isAdAllowed($excerpt) &&
            is_main_query() &&
            $postExcerpt = $this->getPositionAd('post_excerpt', $excerpt)) {
            $excerpt .= $postExcerpt;
        }

        return $excerpt;
    }

    /**
     * Checks if ads turned on.
     *
     * @param $content post content
     * @return bool
     */
    private function isAdAllowed($content)
    {
        $settings = get_option('adshares_settings');

        // Never show ads in ajax calls
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            return false;
        }

        if (is_feed() ||
            is_search() ||
            is_404() ||
            strpos($content, '<!--no_ads-->') !== false ||
            strpos($content, '<!--off_ads-->') !== false ||
            is_front_page() && !isset($settings['visibility']['homepage']) ||
            is_category() && !isset($settings['visibility']['categories']) ||
            is_archive() && !isset($settings['visibility']['archives']) ||
            is_tag() && !isset($settings['visibility']['tags']) ||
            is_user_logged_in() && isset($settings['exceptions']['logged_user'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Remove process tags.
     *
     * @param $content post content
     * @return string
     */
    private function cleanContent($content)
    {
        $tags = [
            'no_ads',
            'off_ads',
            'off_post_beginning',
            'off_post_middle',
            'off_post_end',
            'off_paragraph_first',
            'off_paragraph_second',
            'off_paragraph_third',
            'off_paragraph_last',
        ];
        foreach ($tags as $tag) {
            if (strpos($content, '<!--' . $tag . '-->') !== false) {
                $content = str_replace(['<p><!--' . $tag . '--></p>', '<!--' . $tag . '-->'], '', $content);
            }
        }
        $content = str_replace("##ADS-TP1##", "<p></p>", $content);
        $content = str_replace("##ADS-TP2##", "<p>&nbsp;</p>", $content);

        return $content;
    }

    /**
     * Sanitize post content.
     *
     * @param $content post content
     * @return string
     */
    private function sanitizeContent($content)
    {
        // Replace all <p></p> tags with placeholder ##QA-TP1##
        $content = str_replace('<p></p>', '##ADS-TP1##', $content);
        // Replace all <p>&nbsp;</p> tags with placeholder ##QA-TP2##
        $content = str_replace('<p>&nbsp;</p>', '##ADS-TP2##', $content);
        // Unify paragraph closing tag
        $content = str_replace('<P>', self::P_OPEN_TAG, $content);
        $content = str_replace('</P>', self::P_CLOSE_TAG, $content);

        return $content;
    }

    /**
     * Get position ad code.
     *
     * @param $id position id
     * @param $content post content
     * @return string
     */
    private function getPositionAd($id, $content)
    {
        if (strpos($content, sprintf('<!--off_%s-->', $id)) !== false) {
            return null;
        }

        $settings = get_option('adshares_settings');
        if (empty($settings['positions'][$id])) {
            return null;
        }

        $sites = get_option('adshares_sites');
        $ad = null;
        foreach ($sites as $site) {
            foreach ($site['adUnits'] as $unit) {
                if ($unit['uuid'] == $settings['positions'][$id]) {
                    $ad = $unit;
                    break 2;
                }
            }
        }

        return $ad !== null ? '<div>' . $ad['code'] . '</div>' : null;
    }

    /**
     * Insert ads into post content.
     *
     * @param $content string Original post content
     * @return string Modified content
     */
    private function insertAds($content)
    {
        $postBeginning = $this->getPositionAd('post_beginning', $content);
        $postMiddle = $this->getPositionAd('post_middle', $content);
        $postEnd = $this->getPositionAd('post_end', $content);
        $paragraphFirst = $this->getPositionAd('paragraph_first', $content);
        $paragraphSecond = $this->getPositionAd('paragraph_second', $content);
        $paragraphThird = $this->getPositionAd('paragraph_third', $content);
        $paragraphLast = $this->getPositionAd('paragraph_last', $content);

        $content = $this->preserveBlockQuotes($content);

        $paragraphs = $this->extractParagraphs($content);

        $count = count($paragraphs);

        $idxMiddle = $postMiddle ? (int)floor($count / 2) - 1 : false;
        $idxEnd = $count - 1;
        $idxLast = $paragraphLast ? $idxEnd - 1 : false;

        $idxFirst = $paragraphFirst ? 0 : false;
        $idxSecond = $paragraphSecond ? 1 : false;
        $idxThird = $paragraphThird ? 2 : false;

        $newParagraphs = [];
        foreach ($paragraphs as $index => $paragraph) {
            if (strpos($paragraph, self::P_CLOSE_TAG) === false) {
                $paragraph .= self::P_CLOSE_TAG;
            }

            $newParagraphs[] = $paragraph;

            if ($index === $idxEnd) {
                $newParagraphs[] = $postEnd;
            } elseif ($index === $idxMiddle) {
                $newParagraphs[] = $postMiddle;
            } elseif ($index === $idxLast) {
                $newParagraphs[] = $paragraphLast;
            } elseif ($index === $idxFirst) {
                $newParagraphs[] = $paragraphFirst;
            } elseif ($index === $idxSecond) {
                $newParagraphs[] = $paragraphSecond;
            } elseif ($index === $idxThird) {
                $newParagraphs[] = $paragraphThird;
            }
        }

        $content = $postBeginning . implode($newParagraphs);

        return $this->restoreBlockQuotes($content);
    }

    private function preserveBlockQuotes($content)
    {
        preg_match_all("/<blockquote.*?<\/blockquote>/si", $content, $blockquotes);

        $this->blockquotes = [];
        if (!empty($blockquotes)) {
            foreach ($blockquotes[0] as $bId => $blockquote) {
                $this->blockquotes[$bId] = trim($blockquote);

                $content = str_replace($this->blockquotes[$bId], "#ADSBLOCKQUOTE#$bId#", $content);
            }
        }

        return $content;
    }

    private function restoreBlockQuotes($content)
    {
        foreach ($this->blockquotes as $bId => $blockquote) {
            $content = str_replace("#ADSBLOCKQUOTE#$bId#", $blockquote, $content);
        }

        return $content;
    }

    private function extractParagraphs($content)
    {
        $markedContent = str_ireplace(self::P_OPEN_TAG, self::ADSH_PARAGRAPH_MARKER . self::P_OPEN_TAG, $content);

        return array_values(array_filter(explode(self::ADSH_PARAGRAPH_MARKER, $markedContent), 'trim'));
    }
}
