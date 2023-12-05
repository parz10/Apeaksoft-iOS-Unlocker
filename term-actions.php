<?php

namespace Helpie\Includes\Actions;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

// TODO: Move DND related methods / rename this class
if (!class_exists('\Helpie\Includes\Actions\Term_Actions')) {

    class Term_Actions
    {
        public function __construct()
        {
            add_action('create_term', array($this, 'create_term_action'), 10, 3);
            add_action('edit_term', array($this, 'update_term_action'), 10, 3);
            add_action('delete_term', array($this, 'delete_term_action'), 10, 3);
            add_action('csf_init', [$this, 'check_missing_topics']);
            add_action('csf_init', [$this, 'remove_nonexistant_terms_from_dnd']);
            // error_log('Term_Actions');
        }

        public function create_term_action($term_id, $tt_id, $taxonomy)
        {
            if ($taxonomy == 'helpdesk_category') {
                $last_order_value = get_option('helpie_kb_category_last_order');
                $order_value = $last_order_value + 1;
                update_term_meta($term_id, 'term_order', $order_value);

                $this->add_to_mp_cats_option($term_id);
            }

        }

        public function update_term_action($term_id, $tt_id, $taxonomy)
        {
            $term = get_term($term_id, 'helpdesk_category');

            /* RETURN CONDITIONS */

            // If parent term, return
            if ($term->parent == 0) {
                return;
            }

            if ($taxonomy != 'helpdesk_category') {
                return;
            }

            $options = get_option('helpie-kb');

            if (!isset($options) || empty($options) || !isset($options['helpie_mp_cats']) || empty($options['helpie_mp_cats'])) {
                return;
            }

            if (isset($options['helpie_mp_cats']['enabled']) && !empty($options['helpie_mp_cats']['enabled'])) {
                // If term is found, remove it

                foreach ($options['helpie_mp_cats']['enabled'] as $key => $value) {
                    $enabled_term_id = str_replace('term-id_', '', $key);
                    if ($term_id == $enabled_term_id) {
                        unset($options['helpie_mp_cats']['enabled'][$key]);
                    }
                }
            }

            if (isset($options['helpie_mp_cats']['disabled']) && !empty($options['helpie_mp_cats']['disabled'])) {

                foreach ($options['helpie_mp_cats']['disabled'] as $key => $value) {
                    $disabled_term_id = str_replace('term-id_', '', $key);
                    if ($term_id == $disabled_term_id) {
                        unset($options['helpie_mp_cats']['disabled'][$key]);
                    }
                }
            }

            update_option('helpie-kb', $options);
        }

        public function delete_term_action($term_id, $tt_id, $taxonomy)
        {
            $options = get_option('helpie-kb'); // unique id of the framework

            if (isset($options['helpie_mp_cats']['enabled']['term-id_' . $term_id])) {
                unset($options['helpie_mp_cats']['enabled']['term-id_' . $term_id]);
            }

            if (isset($options['helpie_mp_cats']['disabled']['term-id_' . $term_id])) {
                unset($options['helpie_mp_cats']['disabled']['term-id_' . $term_id]);
            }

            update_option('helpie-kb', $options);
        }

        public function add_to_mp_cats_option($term_id)
        {
            $term = get_term($term_id, 'helpdesk_category');

            /* RETURN CONDITIONS */

            // If NOT parent term, return
            if (!isset($term) || $term->parent != 0) {
                return;
            }

            $options = get_option('helpie-kb'); // unique id of the framework
            $term = get_term($term_id, 'helpdesk_category');

            $options['helpie_mp_cats']['enabled']['term-id_' . $term_id] = $term->name;
            $result = update_option('helpie-kb', $options);
        }

        /* Methods to reset old bad behavior - remove_nonexistant_terms_from_dnd(), check_missing_topics() */

        public function remove_nonexistant_terms_from_dnd()
        {
            // 1. Set $options from current value
            $options = get_option('helpie-kb'); // unique id of the framework
            $options = !isset($options) && !is_array($options) ? [] : $options;

            // 2. Initial setttings here to get updated value
            $this->settings = new \Helpie\Includes\Settings\Getters\Settings();
            $mp_cats = $this->settings->main_page->get_mp_cats();

            $mp_categories = [
                'helpie_mp_cats' => [
                    'enabled' => $this->get_enabled_and_disbled_terms($mp_cats['enabled']),
                    'disabled' => $this->get_enabled_and_disbled_terms($mp_cats['disabled']),
                ],
            ];

            $options = array_merge($options, $mp_categories);

            update_option('helpie-kb', $options);
        }

        /* Check and add missing topics to Main Page DND */
        public function check_missing_topics()
        {

            // error_log('csf_init - check_missing_topics');
            // 1. Set $options from current value
            $options = get_option('helpie-kb'); // unique id of the framework

            // 2. Initial setttings here to get updated value
            $this->settings = new \Helpie\Includes\Settings\Getters\Settings();
            $mp_cats = $this->settings->main_page->get_mp_cats();

            $enabled = $mp_cats['enabled'];
            $disabled = $mp_cats['disabled'];

            $terms = helpie_call_function_without_filters('get_terms', ['helpdesk_category', ['hide_empty' => false, 'parent' => 0]]);

            // 3. Check if term_key exists in drag and drop fields. If not, add to enabled side.
            foreach ($terms as $key => $term) {

                if (isset($term->term_id) && !empty($term->term_id)) {

                    $term_key = 'term-id_' . $term->term_id;

                    if (array_key_exists($term_key, $enabled) || array_key_exists($term_key, $disabled)) {
                        continue;
                    }

                    $enabled[$term_key] = $term->name;
                }
            }

            $options['helpie_mp_cats']['enabled'] = $enabled;

            // error_log('$options : ' . print_r($options['helpie_mp_cats'], true));
            $result = update_option('helpie-kb', $options);
        }

        /* Remove non-existant terms from enabled and disabled */
        protected function get_enabled_and_disbled_terms($enabled_and_disabled_terms = [])
        {
            $has_terms = !empty($enabled_and_disabled_terms) && is_array($enabled_and_disabled_terms) ? true : false;

            if (!$has_terms) {
                return [];
            }

            foreach ($enabled_and_disabled_terms as $key => $value) {
                $term_id = str_replace('term-id_', '', $key);
                $term_exists = term_exists((int) $term_id, HELPIE_TAXONOMY);

                if (!$term_exists) {
                    unset($enabled_and_disabled_terms[$key]);
                }
            }

            return $enabled_and_disabled_terms;
        }
    } // END CLASS

}
