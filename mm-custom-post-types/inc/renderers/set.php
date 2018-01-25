<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Set extends Field_Base {
    var $renderers;

    function __construct($renderers) {
        $this->renderers = $renderers;
    }

    function display($prefix, $initial_value, $options = array()) {
        $i = 0;
        foreach ($this->renderers as $renderer) {
            if (!($renderer instanceof Field_Interface)) continue;

            $renderer->display($prefix . '[' . $i++ . ']', $initial_value, $options);
        }
    }

    function save($input, $post) {
        $i = 0;
        $output = array();
        foreach ($this->renderers as $renderer) {
            if (!($renderer instanceof Field_Interface)) continue;

            $row_values = $renderer->save($input[$i++], $post);
            if (!is_array($row_values)) $row_values = array();

            $output = $row_values + $output;
        }

        return $output;
    }

    function retrieve($input, $post) {
        $output = array();
        foreach ($this->renderers as $renderer) {
            if (!($renderer instanceof Field_Interface)) continue;

            $row_values = $renderer->retrieve($input, $post);
            if (!is_array($row_values)) $row_values = array();

            $output = $row_values + $output;
        }

        return $output;
    }
}