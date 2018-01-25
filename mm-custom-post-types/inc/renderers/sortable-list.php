<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Sortable_List extends Field_Base {
    var $fields;

    var $settings;

    function __construct($fields, $settings) {
        if ($settings['has_defaults']) {
            add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
            if (!isset($settings['default_has_types'])) $settings['default_has_types'] = $settings['has_types'];
        }

        if ($settings['has_types']) {
            $settings['show_hide_pre_contained'] = true;
        }

        $this->fields = $fields;
        $this->settings = $settings;
    }

    function add_meta_boxes() {
        add_meta_box($this->settings['default_meta'], $this->settings['default_title'], array($this, 'display_defaults'), $this->settings['meta_context'], 'normal', 'high');
    }

    function display($prefix, $initial_value, $options = array()) {
        $table = new Table($this->fields, $this->settings);
        ?>
        <ol class="sortable-array">
            <?php for ($i = 0; $i < max($this->settings['max_items'], count($initial_value)); $i++) {
                $table_identifier = substr(md5($prefix . '[' . $i . ']'), 0, 10);
                $show_none = isset($initial_value[$i]['type'])
                    ? $initial_value[$i]['type'] == 'none'
                    : true;
                ?>
                <li <?php if ($this->settings['has_types']) { ?>
                        class="sort-indices show-hide-container<?php if ($show_none) { echo ' show-none'; } ?>" data-identifier="<?php echo $table_identifier; ?>"
                    <?php } else { ?>
                        class="sort-indices"
                    <?php } ?>>
                    <div class="handle-container"><span class="dashicons dashicons-menu handle"></span> (Reorder)</div>
                    <?php
                    $table_values = isset($initial_value[$i]) ? $initial_value[$i] : null;
                    $table->display($prefix . '[' . $i . ']', $table_values);
                    ?>
                    <input type="hidden" name="<?php echo $prefix; ?>[<?php echo $i; ?>][index]" class="sort-index"
                        value="<?php echo $i; ?>"/>
                </li>
            <?php } ?>
        </ol>
        <?php
    }

    function display_defaults($post) {
        $fields = array();

        for ($i=0; $i<count($this->fields); $i++) {
            if ($this->fields[$i]['options']['has_default']) $fields[] = $this->fields[$i];
        }

        $interim_settings = $this->settings;
        $interim_settings['has_types'] = $interim_settings['default_has_types'];
        $table = new Table($fields, $interim_settings);

        $initial_values = $post && $post->ID ? get_post_meta($post->ID, $this->settings['default_meta'], true) : null;

        $table->display($this->settings['default_meta'], $initial_values, array(
            'display_default' => true,
        ));
    }

    function save($input, $post) {
        if ($this->settings['has_types']) {
            self::order_clean_sorted_array($input, 'type', 'none');
        } else {
            self::order_clean_sorted_array($input);
        }

        $table = new Table($this->fields, $this->settings);

        for ($i=0; $i<count($input); $i++) {
            $input[$i] = $table->save($input[$i], $post);
        }

        if ($this->settings['has_defaults']) {
            $fields = array();

            for ($i=0; $i<count($this->fields); $i++) {
                if ($this->fields[$i]['options']['has_default']) $fields[] = $this->fields[$i];
            }

            $interim_settings = $this->settings;
            $interim_settings['has_types'] = $interim_settings['default_has_types'];
            $table = new Table($fields, $interim_settings);

            update_post_meta($post->ID, $this->settings['default_meta'], $table->save($_REQUEST[$this->settings['default_meta']], null));
        }

        return $input;
    }

    function retrieve($input, $post) {
        $table = new Table($this->fields, $this->settings);

        $fields = array();
        $defaults = array();

        if ($this->settings['has_defaults']) {
            $defaults = get_post_meta($post->ID, $this->settings['default_meta'], true);

            for ($i=0; $i<count($this->fields); $i++) {
                if ($this->fields[$i]['options']['has_default']) $fields[] = $this->fields[$i];
            }

            $interim_settings = $this->settings;
            $interim_settings['has_types'] = $interim_settings['default_has_types'];
            $table = new Table($fields, $interim_settings);

            $defaults = $table->retrieve($defaults, $post);
        }

        for ($i=0; $i<count($input); $i++) {
            $input[$i] = $table->retrieve($input[$i], $post);

            if ($this->settings['has_defaults']) {
                for ($c=0; $c<count($fields); $c++) {
                    if (!$input[$i][$fields[$c]['name']] || (isset($input[$i][$fields[$c]['name']]['id']) && !$input[$i][$fields[$c]['name']]['id'])) {
                        $input[$i][$fields[$c]['name']] = $defaults[$fields[$c]['name']];
                    }
                }
            }
        }

        return $input;
    }

    /**
     * Orders $array by its `index` keys, which were probably assigned by JavaScript on the front-end during reordering.
     *
     * Also removes any element where the value of $array[$i][$test_key] == $remove_value.
     *
     * @param array $array
     * @param string $test_key
     * @param string $remove_value
     */
    private static function order_clean_sorted_array(&$array = array(), $test_key = null, $remove_value = null) {
        if (!is_array($array)) $array = array();

        usort($array, function($a, $b) {
            if (!isset($a['index']) || !isset($b['index'])) return 0;
            if ($a['index'] == $b['index']) return 0;
            return ($a['index'] < $b['index']) ? -1 : 1;
        });

        for ($i=0; $i<count($array); $i++) {
            if (isset($array[$i]['index'])) unset($array[$i]['index']);
            if ($test_key) {
                if (isset($array[$i][$test_key]) && $array[$i][$test_key] == $remove_value) unset($array[$i--]);
                $array = array_values($array);
            }
        }

    }
}