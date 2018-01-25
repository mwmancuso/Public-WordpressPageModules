<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class News_Category_Select extends Field_Base {
    function display($prefix, $initial_value, $options = array()) {
        wp_dropdown_categories(array(
            'exclude' => Main::EVENT_CATEGORY_PARENT,
            'hierarchical' => 1,
            'orderby' => 'name',
            'name' => $prefix,
            'selected' => $initial_value,
        ));
    }
}