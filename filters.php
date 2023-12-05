<?php

namespace Helpie\Includes\Actions;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('\Helpie\Includes\Actions\Filters')) {

    class Filters
    {

        public function __construct()
        {
            $this->helper = new \Helpie\Includes\Utils\Pauple_Helper();

            add_filter('body_class', array($this, 'search_body_class'));
            add_filter('body_class', array($this, 'add_theme_body_class'));
        }

        public function search_body_class($c)
        {
            global $post;

            if (isset($post->post_content) && has_shortcode($post->post_content, 'pauple_helpie_search_results_page')) {
                $c[] = 'helpie-kb-search-page';
            }
            return $c;
        }

        public function add_theme_body_class($classes)
        {
            $prefix = 'pauple-theme-';

            // Stylesheet slugs of Our Supported Themes
            $themes = [
                'twentytwenty',
                'astra',
                'hello-elementor',
                'storefront',
                'buddyboss-theme',
                'Avada',
                'enfold',
                'flatsome',
                'betheme',
                'jupiterx',
                'dt-the7',
                'salient',
                'oceanwp',
            ];

            foreach ($themes as $theme) {
                if (get_stylesheet() == $theme && !isset($classes[$prefix . $theme])) {
                    array_push($classes, $prefix . $theme);
                }
            }

            return $classes;
        }
    } // END CLASS
}
