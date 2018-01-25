<?php
namespace MM\Custom_Post_Types;

require_once 'field-interface.php';

abstract class Field_Base implements Field_Interface {
    function meta_box_display($post, $metabox = array()) {
        $args = $metabox['args'];
        $initial_values = $post && $post->ID ? get_post_meta($post->ID, $args['name'], true) : null;

        $this->display($args['name'], $initial_values, $args['options']);
    }

    function save($input, $post) {
        return $input;
    }

    function retrieve($input, $post) {
        return $input;
    }

    /**
     * Displays value field for HTML option element, displaying selected if $test_value matches.
     *
     * @param string $value value displayed all of the time
     * @param string $test_value if this equals $value, "selected" will be added as well
     */
    protected static function option_value($value, $test_value) {
        echo ' value="' . $value . '"';
        if ($value === $test_value) echo ' selected';
    }
}