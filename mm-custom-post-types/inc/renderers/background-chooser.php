<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Background_Chooser extends Field_Base {
    const MAX_BACKGROUND_WIDTH = 1920;

    var $tax_name;

    var $tax_title;

    function __construct($tax_name, $tax_title) {
        $this->tax_name = $tax_name;
        $this->tax_title = $tax_title;

        if ($tax_name) {
            add_action('init', array($this, 'register_custom_taxonomy'));
            add_action('admin_head', array($this, 'inject_header_tax'));
            add_image_size($tax_name, self::MAX_BACKGROUND_WIDTH, 9999);
        }
    }

    function display($prefix, $initial_value, $options = array()) {
        $initial_value = array_merge(array(
            'position' => '',
            'id' => '',
        ), $initial_value ?: array());
        ?>
        <div class="background-preview-container">
            <div style="margin-bottom: 10px;">
                Background Position:
                <select name="<?php echo $prefix; ?>[position]" id="<?php echo $prefix; ?>[position]"
                    class="background-position">
                    <option <?php self::option_value('center', $initial_value['position']); ?>>Center</option>
                    <option <?php self::option_value('top', $initial_value['position']); ?>>Top</option>
                    <option <?php self::option_value('bottom', $initial_value['position']); ?>>Bottom</option>
                </select>
            </div>
            <div class="background-preview <?php echo $initial_value['id'] ? '' : 'placeholder'; ?> position-<?php echo $initial_value['position']; ?>"
                id="<?php echo $prefix; ?>[preview]" <?php self::background_style($initial_value['id']); ?>></div>
            <input id="<?php echo $prefix; ?>[upload]" type="button"
                class="button background-upload-button"
                value="Choose background"/> <input
                id="<?php echo $prefix; ?>[clear]" type="button"
                class="button background-clear-button"
                value="Remove background"/> <input type="hidden"
                name="<?php echo $prefix; ?>[id]"
                id="<?php echo $prefix; ?>[id]"
                class="background-id"
                value="<?php echo $initial_value['id']; ?>">
        </div>
        <?php
    }

    /**
     * Registers Header Background taxonomy so that background images can be associated with Headers
     */
    function register_custom_taxonomy() {
        $labels = array(
            'name' => $this->tax_title . 's',
            'singular_name' => $this->tax_title,
            'search_items' => 'Search ' . $this->tax_title . 's',
            'all_items' => 'All ' . $this->tax_title . 's',
            'parent_item' => 'Parent ' . $this->tax_title,
            'parent_item_colon' => 'Parent ' . $this->tax_title . ':',
            'edit_item' => 'Edit ' . $this->tax_title,
            'update_item' => 'Update ' . $this->tax_title,
            'add_new_item' => 'Add New ' . $this->tax_title,
            'new_item_name' => 'New ' . $this->tax_title . ' Name',
            'menu_name' => $this->tax_title . 's',
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => false,
            'rewrite' => true,
            'show_admin_column' => true,
        );

        register_taxonomy($this->tax_name, 'attachment', $args);
    }

    /**
     * Injects list of taxonomy information into HTML for use by media chooser.
     *
     * See JavaScript file for its use.
     */
    function inject_header_tax() {
        if (Main::is_on_edit_screen() == false) return;
        ?>
        <script type="text/javascript">
            var backgroundTaxonomyData = <?php
                echo json_encode(get_terms($this->tax_name, array(
                    'hide_empty' => false,
                )));
                ?>;
        </script>
        <?php
    }

    /**
     * During a save, this will associate each background used in the header with identifying taxonomies.
     *
     * Takes $defaults and $slides lists, extracts background IDs from them, and gives each background a term for:
     *  1) 'Any', which means that the background was once used by any header, and
     *  2) $title, which means that the background was once used by this particular header.
     *
     * @param $input
     * @param $post
     *
     * @return
     */
    function save($input, $post) {
        $title = $post->post_title;

        $term = get_term_by('slug', sanitize_title($title), $this->tax_name);
        $term_id = $term->term_id;

        if (!$term) {
            $term = wp_insert_term($title, $this->tax_name);
            if (is_wp_error($term)) return $input;

            $term_id = $term['term_id'];
        }

        if (!get_term_by('slug', 'any', $this->tax_name)) {
            wp_insert_term('Any', $this->tax_name, array('slug' => 'any'));
        }

        $background_id = $input['background-id'];

        if (!$background_id) return $input;

        wp_set_object_terms($background_id, 'any', $this->tax_name, true);
        wp_set_object_terms($background_id, $term_id, $this->tax_name, true);
        self::resize_background($background_id);

        return $input;
    }

    /**
     * Displays style attribute with background image url given $background_id
     *
     * @param int $background_id
     */
    private static function background_style($background_id) {
        if ($background_id) {
            echo ' style="background-image: url(\'' . wp_get_attachment_url($background_id) . '\');"';
        }
    }

    private static function resize_background($background_id) {
        $path = get_attached_file($background_id);
        if (!$path) return;
        $metadata = @wp_generate_attachment_metadata($background_id, $path);
        if (is_wp_error($metadata)) return;

        wp_update_attachment_metadata($background_id, $metadata);
    }
}