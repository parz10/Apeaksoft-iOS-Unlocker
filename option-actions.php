<?php

namespace Helpie\Includes\Actions;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

if (!class_exists('\Helpie\Includes\Actions\Option_Actions')) {

    class Option_Actions
    {
        public function __construct()
        {
            // error_log('Option_Actions: __construct ');
            $this->services = new \Helpie\Includes\Services();
            add_action('update_option', array($this, 'pre_update_option_action'), 10, 3);
        }

        public function pre_update_option_action($option_name, $old_value, $value)
        {
            // error_log('pre_update_option_action option_name: ' . $option_name);
            if ($option_name == 'helpie-kb' && isset($value['helpie_mp_cats']) && $value['helpie_mp_cats'] != '') {
                $included_terms = $value['helpie_mp_cats'];
                $this->services->update_category_order($included_terms);
            }
        }

    } // END CLASS

}