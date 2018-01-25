<?php
namespace MM\Custom_Post_Types;

interface Field_Interface {
    function display($prefix, $initial_value, $options = array());

    function meta_box_display($post, $metabox = array());

    function save($input, $post);

    function retrieve($input, $post);
}