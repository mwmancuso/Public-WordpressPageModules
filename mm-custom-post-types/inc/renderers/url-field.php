<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class URL_Field extends Field_Base {
    function display($prefix, $initial_value, $options = array()) {
        ?>
        <input type="text" name="<?php echo $prefix; ?>"
            id="<?php echo $prefix; ?>"
            value="<?php echo htmlentities($initial_value); ?>" class="regular-text"
            placeholder="URL or Page ID"/>
        <?php
    }

    function save($input, $post) {
        if (!is_numeric($input)) {
            $page_id = url_to_postid($input);
            $fragment = parse_url($input, PHP_URL_FRAGMENT);

            if ($page_id) {
                $input = $fragment ? $page_id . '#' . $fragment : $page_id;
            } else if (parse_url(get_home_url(), PHP_URL_HOST) == parse_url($input, PHP_URL_HOST)
                && parse_url($input, PHP_URL_HOST)
            ) {
                $input = wp_make_link_relative($input);
            }
        }

        return $input;
    }

    function retrieve($input, $post) {
        $split = explode('#', $input, 2);
        $url = $split[0];
        $fragment = count($split) == 2 ? '#' . $split[1] : '';
        if (is_numeric($url)) {
            return get_permalink($url) . $fragment;
        }

        return $input;
    }
}