<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Text_Editor extends Field_Base {
    var $options;

    function __construct($options) {
        $this->options = $options;
    }

    function display($prefix, $initial_value, $options = array()) {
        wp_editor($initial_value, $prefix, array(
            'textarea_name' => $prefix,
        ) + $this->options);
    }
}