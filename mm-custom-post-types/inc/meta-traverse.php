<?php
namespace MM\Custom_Post_Types;
/**
 * Facilitates allowing user independent control of page meta, while utilizing a hierarchical structure.
 *
 * Provides a front-end and back-end interface for retrieving hierarchical meta, defined as follows:
 * Front-end:
 *  Allows user to select the piece of meta for the current page and its sub-pages--separately or together.
 * Back-end:
 *  When searching for the meta of a page, will first check to see if the given page has the meta entry set. If
 *  not, will then check to see if parent has sub-page meta set. If not, will check to see if parent has its page
 *  meta set. If not, it will continue the process up the hierarchy.
 *
 * This plugin should be flexible and allow easy transition between plugins.
 */
class Meta_Traverse {
    /**
     * Prefixes the names on input/select boxes.
     */
    const INPUT_NAME_PREFIX = 'meta_';

    /**
     * Suffix on meta key for separate child boolean.
     */
    const SEPARATE_CHILD_SUFFIX = '_separate_child';

    /**
     * Suffix on meta key for child value.
     */
    const CHILD_META_SUFFIX = '_children';

    /**
     * Suffix on meta key for separate post boolean.
     */
    const SEPARATE_POST_SUFFIX = '_separate_posts';

    /**
     * Suffix on meta key for post value.
     */
    const POST_META_SUFFIX = '_posts';

    /**
     * @var string base name of meta key. Assigned in constructor.
     */
    public $meta_name;

    /**
     * @var string pretty name of meta key, used for interface. Assigned in constructor.
     */
    public $meta_title;

    /**
     * @var string either "text" for textbox or "select" for select field. Assigned in constructor.
     */
    public $meta_type;

    /**
     * @var callable if $this->meta_type is "string," this will be called to get options for select field. Should return
     * an array with each member having a `value` and `title` field, i.e. array(array('value'=>4356, 'title'=>'Test'))
     */
    public $select_options_function;

    public $select_options_callback_args;

    /**
     * Meta Traverse constructor, assigns variables and adds actions.
     *
     * @param $meta_name
     * @param $meta_title
     * @param $meta_type
     * @param array $select_options_function
     */
    function __construct($meta_name, $meta_title, $meta_type, $select_options_function = array(), $select_options_callback_args = array()) {
        $this->meta_name = $meta_name;
        $this->meta_title = $meta_title;
        $this->meta_type = $meta_type;
        $this->select_options_function = $select_options_function;
        $this->select_options_callback_args = $select_options_callback_args;

        add_action('add_meta_boxes', array($this, 'add_attribute_box'));
        add_action('save_post', array($this, 'save_page'), 10, 3);

        add_action('category_edit_form_fields', array($this, 'show_category_box'));
        add_action('edited_category', array($this, 'save_category'), 10, 1);
    }

    /**
     * Adds meta box to page edit pages on the right-hand side.
     */
    function add_attribute_box() {
        add_meta_box(
            self::INPUT_NAME_PREFIX . $this->meta_name . '_meta_box',
            $this->meta_title, array($this, 'show_attribute_box'), 'page', 'side');
    }

    /**
     * Displays the contents of the attribute box
     *
     * @param $post
     */
    function show_attribute_box($post) {
        $separate_child = get_post_meta($post->ID, $this->meta_name . self::SEPARATE_CHILD_SUFFIX, true);
        ?>
        <div class="meta-traverse-container">
            <p>
                <strong><?php echo $this->meta_title; ?></strong>
            </p>
            <?php $this->show_a_box('page', $post, $this->meta_name); ?>
            <p>
                <strong>Children's <?php echo $this->meta_title; ?></strong>
            </p>
            <p>
                <input type="checkbox" class="meta-traverse-checkbox" name="<?php echo self::INPUT_NAME_PREFIX . $this->meta_name . self::SEPARATE_CHILD_SUFFIX; ?>"<?php if ($separate_child) echo ' checked'; ?>>
                Different behavior for children.
            </p>
            <div class="meta-traverse-toggle"<?php if (!$separate_child) echo ' style="display:none;"'; ?>>
                <?php $this->show_a_box('page', $post, $this->meta_name . self::CHILD_META_SUFFIX); ?>
            </div>
        </div>
        <?php
    }

    function show_category_box($term) {
        $separate_child = get_term_meta($term->term_id, $this->meta_name . self::SEPARATE_CHILD_SUFFIX, true);
        $separate_posts = get_term_meta($term->term_id, $this->meta_name . self::SEPARATE_POST_SUFFIX, true);
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for=""><?php echo $this->meta_title; ?></label>
            </th>
            <td>
                <?php $this->show_a_box('category', $term, $this->meta_name); ?>
                <div class="meta-traverse-container">
                    <p style="margin: 1em 0;">
                        <input type="checkbox" class="meta-traverse-checkbox" name="<?php echo self::INPUT_NAME_PREFIX . $this->meta_name . self::SEPARATE_CHILD_SUFFIX; ?>"<?php if ($separate_child) echo ' checked'; ?>>
                        Different behavior for children.
                    </p>
                    <div class="meta-traverse-toggle"<?php if (!$separate_child) echo ' style="display:none;"'; ?>>
                        <?php $this->show_a_box('category', $term, $this->meta_name . self::CHILD_META_SUFFIX); ?>
                    </div>
                </div>
                <div class="meta-traverse-container">
                    <p style="margin: 1em 0;">
                        <input type="checkbox" class="meta-traverse-checkbox" name="<?php echo self::INPUT_NAME_PREFIX . $this->meta_name . self::SEPARATE_POST_SUFFIX; ?>"<?php if ($separate_posts) echo ' checked'; ?>>
                        Different behavior for posts.
                    </p>
                    <div class="meta-traverse-toggle"<?php if (!$separate_posts) echo ' style="display:none;"'; ?>>
                        <?php $this->show_a_box('category', $term, $this->meta_name . self::POST_META_SUFFIX); ?>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Decides between showing select or text box based on meta type
     *
     * @param $type
     * @param $object
     * @param $meta_field
     */
    private function show_a_box($type, $object, $meta_field) {
        switch($this->meta_type) {
            case 'select':
                $this->show_select_box($type, $object, $meta_field);
                break;
            default:
                $this->show_text_box($type, $object, $meta_field);
                break;
        }
    }

    /**
     * @param $type
     * @param $object
     * @param $meta_field
     */
    private function show_text_box($type, $object, $meta_field) {
        if ($type == 'page') {
            $current_value = get_post_meta($object->ID, $meta_field, true);
        } else if ($type == 'category') {
            $current_value = get_term_meta($object->term_id, $meta_field, true);
        } else {
            $current_value = '';
        }
        ?>
        <input type="text" value="<?php echo $current_value; ?>" name="<?php echo self::INPUT_NAME_PREFIX . $meta_field; ?>"<?php if ($type == 'page') { ?> style="width: 100%"<?php } ?>>
        <?php
    }

    /**
     * Shows a select box with options returned by $this->select_options_function callable
     *
     * @param $type
     * @param $object
     * @param $meta_field
     */
    private function show_select_box($type, $object, $meta_field) {
        if ($type == 'page') {
            $current_value = get_post_meta($object->ID, $meta_field, true);
        } else if ($type == 'category') {
            $current_value = get_term_meta($object->term_id, $meta_field, true);
        } else {
            $current_value = '';
        }
        ?>
        <select name="<?php echo self::INPUT_NAME_PREFIX . $meta_field; ?>"<?php if ($type == 'page') { ?> style="width: 100%"<?php } ?>>
            <option value="">Inherit</option>
            <?php
            $values = call_user_func_array($this->select_options_function, $this->select_options_callback_args);
            if (is_array($values) || $values instanceof \Traversable) {
                foreach($values as $value) {
                    echo '<option value="' . $value['value'] . '"';
                    if ($current_value !== '' && $value['value'] == $current_value) {
                        echo ' selected';
                    }
                    echo '>' . $value['title'] . '</option>';
                }
            }
            ?>
        </select>
        <?php
    }

    /**
     * Responds to save_post action, only saves meta for pages and if their meta value is set
     *
     * @param $object_id
     * @param $post
     * @param $update
     */
    function save_object($object_type, $object_id) {
        if (!isset($_REQUEST[self::INPUT_NAME_PREFIX . $this->meta_name])) return;
        $meta_name = $this->meta_name;
        $separate_name = $this->meta_name . self::SEPARATE_CHILD_SUFFIX;
        $child_name = $this->meta_name . self::CHILD_META_SUFFIX;

        $meta_value = $_REQUEST[self::INPUT_NAME_PREFIX . $meta_name] ?: '';
        $separate_value = isset($_REQUEST[self::INPUT_NAME_PREFIX . $separate_name]);
        $child_value = $_REQUEST[self::INPUT_NAME_PREFIX . $child_name] ?: '';

        update_metadata($object_type, $object_id, $meta_name, $meta_value);
        update_metadata($object_type, $object_id, $separate_name, $separate_value);
        update_metadata($object_type, $object_id, $child_name, $child_value);

        if ($object_type == 'term') {
            $separate_posts_name = $this->meta_name . self::SEPARATE_POST_SUFFIX;
            $child_posts_name = $this->meta_name . self::POST_META_SUFFIX;

            $separate_posts_value = isset($_REQUEST[self::INPUT_NAME_PREFIX . $separate_posts_name]);
            $child_posts_value = $_REQUEST[self::INPUT_NAME_PREFIX . $child_posts_name] ?: '';

            update_metadata($object_type, $object_id, $separate_posts_name, $separate_posts_value);
            update_metadata($object_type, $object_id, $child_posts_name, $child_posts_value);
        }
    }

    function save_page($post_id, $post, $update) {
        if ($post->post_type != 'page') return;
        $this->save_object('post', $post_id);
    }

    function save_category($term_id) {
        $this->save_object('term', $term_id);
    }

    /**
     * First checks to see if the post given by $post_id has a value for this meta field. If not, looks at parent to see
     * if it has a separate value for children. If so, looks at that value, if not, looks at the parent's meta value.
     * If no value for the parent, it keeps going up the hierarchy until a value is found. If not, returns false. Also
     * returns false if post given by $post_id is not a page.
     *
     * @param $post_id
     *
     * @param bool $is_inherited updates reference depending on whether value is set or inherited
     *
     * @return bool|mixed
     */
    function get_meta_value($type, $post_id, &$is_inherited = false, $is_single = false) {
        $is_inherited = false;

        if ($type == 'category') {
            $meta_type = 'term';
        } else {
            $meta_type = 'post';
        }

        $meta_name = $this->meta_name;
        $separate_name = $this->meta_name . self::SEPARATE_CHILD_SUFFIX;
        $child_name = $this->meta_name . self::CHILD_META_SUFFIX;
        $separate_posts_name = $this->meta_name . self::SEPARATE_POST_SUFFIX;
        $child_posts_name = $this->meta_name . self::POST_META_SUFFIX;

        if ($is_single && get_metadata($meta_type, $post_id, $separate_posts_name, true)) {
            $post_value = get_metadata($meta_type, $post_id, $child_posts_name, true);
            if ($post_value) return $post_value;
        }

        $this_value = get_metadata($meta_type, $post_id, $meta_name, true);

        if ($this_value) return $this_value;

        $is_inherited = true;

        $ancestors = get_ancestors($post_id, $type);

        foreach($ancestors as $ancestor) {
            if ($is_single && get_metadata($meta_type, $ancestor, $separate_posts_name, true)) {
                $value = get_metadata($meta_type, $ancestor, $child_posts_name, true);
            } else if (get_metadata($meta_type, $ancestor, $separate_name, true)) {
                $value = get_metadata($meta_type, $ancestor, $child_name, true);
            } else {
                $value = get_metadata($meta_type, $ancestor, $meta_name, true);
            }

            if ($value !== '') return $value;
        }

        return false;
    }
}