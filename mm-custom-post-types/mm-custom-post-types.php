<?php
/**
 * Plugin Name: MM Custom Post Types
 * Version: 1.0.0
 * Author: Matthew Mancuso
 * Description: Adds custom post types with back-end editor and inclusion on pages with hierarchy.
 *
 * --- HOW TO USE ------------------------------------------------------------------------------------------------------
 * The following will use the example of adding a custom header to each page.
 *
 * To use a header, we first need to make a template within the template directory. Use (or change) the path defined by
 * self::TEMPLATE_PATH to determine where the template file should go. We'll assume that the path is
 * `module-templates/header`. In `module-templates/header.php`, the following variables will be available initially:
 *  - $header_is_inherited whether header is inherited for given page or not
 *  - $header_slide_count the number of slides available. May be 0.
 *  - $header_slides the array of slides. See self::$DEFAULT_SLIDE for available fields
 *  - $header_title the title of the header post in pretty form
 *  - $header_slug the slug of the header post, safe for use in HTML IDs, for example
 *  - $header_id ID of the header post, in case further information is needed
 * A good method would be to loop through $header_slides, and have custom functionality if there is only one slide. Make
 * sure to account for fields that may be empty.
 *
 * To include a header in the page template, grab the global $foxwd_page_headers, test if it exists to ensure the plugin
 * is enabled, and call $foxwd_page_headers->get_header() to get the current page header or pass in a page ID for
 * another header:
 *  global $foxwd_page_headers;
 *  if ($foxwd_page_headers) $foxwd_page_headers->get_header();
 * Note that get_header() uses the algorithm in Meta_Traverse class (inc/meta-traverse.php) to retrieve the correct
 * hierarchical header.
 *
 * --- HOW TO MODIFY ---------------------------------------------------------------------------------------------------
 * - Adding new slide fields:
 * If new fields need to be added, first we'll want to modify the show_slide_element() function. To add a new field,
 * reference an existing `tr` element. Modify the name of the field (slides[<?php echo $index; ?>][newFieldName] and
 * ensure that the labels match. Make sure that the "show-for-type-*" classes on the `tr` element are set to the correct
 * list of types this field should show for (see Show-Hide Type Selectors below for more information).
 * If the field may also have a default value, add the field in the same manner to the show_defaults_meta_box()
 * function, using one of those `tr`s for reference. IMPORTANT: make sure to modify set_slide_defaults() to assign the
 * default to the slide list.
 * Also make sure to add the field to self::$DEFAULT_SLIDE and if a default field was created, self::$DEFAULT_VALUES.
 *
 * - Adding new button fields:
 * Modify the show_button_element() function to add a `tr` in the same manner as discussed above in "Adding new slide
 * fields".
 * Also make sure to add the field to self::$DEFAULT_SLIDE['buttons'].
 *
 * - Adding new slide or button type:
 * See "Show-Hide Type Selectors" below for more detailed information.
 * When adding a new type, we need to be mindful of the type selectors. The simple procedure, if we're adding a new
 * slide type:
 *  - Add the option to the select field within show_slide_element(), following the existing format.
 *  - Set the "show-for-type-*" classes on the other `tr` elements where necessary.
 *  - Add a new line to the styles.css file under "Show-Hide Type List" (replacing * with the new type):
 *      .show-hide-container > .show-type-* > table > tbody > tr.show-for-type-*,
 *  - Add the type to scripts.js showHideValues as type-*.
 * The same can be done for buttons, substituting show-for-type with show-for-button, etc.
 *
 * - Custom fields:
 * The save_header_post_meta() function may need to be modified if custom functionality is required to format the form
 * data.
 *
 * ---------------------------------------------------------------------------------------------------------------------
 *
 * This plugin also provides some PHP/HTML/CSS/JavaScript templates for use in other custom post editing interfaces,
 * which are defined as follows:
 *
 * --- Show-Hide Type Selectors ----------------------------------------------------------------------------------------
 * Since we're dealing with elements which may have many different types (i.e. header slides may be text slides, video
 * slides, etc., or buttons can be link buttons, dropdown buttons or event buttons), we need a way to control which
 * fields are shown for a specific type. Text slides, for example, shouldn't show a field for video IDs. To implement,
 * the HTML, CSS and JavaScript must be in agreement.
 *
 * - HTML:
 * The layout for Show-Hide selectors is as follows:
 *  .show-hide-container
 *      .show-hide.show-{prefix1}-{currentType1}
 *          table
 *              tbody
 *                  tr.show-for-all
 *                      select[data-prefix={prefix1}]
 *                          option[value={type1}]
 *                          option[value={type2}]
 *                  tr.show-for-{prefix1}-{type1}
 *                      (field shown for type1)
 *                  tr.show-for-{prefix1}-{type2}
 *                      (field shown for type2)
 *                  tr.show-for-{prefix1}-{type1}.show-for-{prefix1}-{type2}
 *                      (field shown for type1 and type2)
 * Note that there may be multiple .show-hide elements in a single show-hide-container. Likewise, .show-hide-container
 * elements may be nested within a tr.show-for-*, however a different prefix should be used to prevent overlap.
 *
 * - CSS:
 * See styles.css for information on how CSS should be applied.
 *
 * - JavaScript:
 * Only the showHideValues in scripts.js should need to be updated to change the functionality. See scripts.js for the
 * code and information.
 *
 * - Note on `.show-none`:
 * `.show-hide` elements behave differently when the type is set to "none". For this reason, prefixes should be removed
 * when the type is set to none (self::get_show_hide() can aid in doing so, see function for more information). Current
 * custom functionality includes hiding subsequent .show-hide.show-none elements to prevent crowding. For example,
 * instead of showing:
 *  Slide Type: Text ...
 *  Slide Type: Video ...
 *  Slide Type: None
 *  Slide Type: None
 *  Slide Type: None
 * We can instead show just:
 *  Slide Type: Text ...
 *  Slide Type: Video ...
 *  Slide Type: None
 * The Sortable Array functionality also hooks into this to determine which elements to sort.
 *
 * --- Sortable Arrays -------------------------------------------------------------------------------------------------
 * Though jQuery provides methods to sort list elements, getting the sorted information back to PHP for processing
 * isn't as straightforward. This template uses jQuery and an "index" field so that PHP can later resort the elements.
 * The original array of elements should also have original indices assigned to group the elements together. For
 * example, if we have an array called `slides` and the fields `type`, `size` and `title` inside each element, the
 * initial array would be as follows:
 *  slides = array(
 *      0 => array(
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 0,
 *      ),
 *      1 => array(
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 1,
 *      ),
 *      2 => array(
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 2,
 *      ),
 *  )
 * After retrieving the array from the browser, it could be:
 *  $_REQUEST['slides'] = array(
 *      0 => array(
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 2,   <-- Note that these indices have changed
 *      ),
 *      1 => array(         <-- While these have stayed the same
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 0,
 *      ),
 *      2 => array(
 *          'type' => 'none',
 *          'size' => 'normal',
 *          'title' => '',
 *          'index' => 1,
 *      ),
 *  )
 *
 * - HTML:
 * The following HTML structure is used for sortable arrays:
 *  ol.sortable-array
 *      li.sort-indices
 *          .handle-container
 *              .handle
 *          input[name="arrayName[{originalIndex}][{field}]"]    <-- May be repeated for different fields
 *          input[name="arrayName[{originalIndex}][index]", type=hidden].sort-index
 * Note the input names, they must contain the original indices to group the fields within an array element together.
 *
 * - CSS:
 * The only CSS for this is an extension of the Show-Hide Selectors, where only the first list element with type set to
 * "none" is shown if there are multiple li.show-none elements back-to-back. See styles.css for code.
 *
 * - JavaScript:
 * See scripts.js for code.
 *
 * - PHP:
 * Provided function self::order_clean_sorted_array() will take an array with index fields, sort it by those indices,
 * and remove the index fields. It will also remove any elements in which the field matches the value. See
 * self::order_clean_sorted_array() for more info.
 *
 * --- ICON PREVIEWS ---------------------------------------------------------------------------------------------------
 * When we're picking a FontAwesome icon, we should be able to see which one we're using before publishing the post.
 * This template places a large preview of the icon above the textbox to make sure we get it right.
 *
 * - HTML:
 * The structure for the Icon Previews HTML is as follows:
 *  .icon-example-container
 *      i.fa.fa-fw.fa-border.fa-4x.icon-example-base
 *      i.fa.fa-fw.fa-border.fa-4x.icon-example.fa-{placeholderIcon}
 *      "fa-" input[type=text].icon-example-text
 * The textbox should be set up so that "fa-" is left out of the text--this is done easiest by placing helper text "fa-"
 * before the textbox. Also, consider giving the textbox placeholder text such as "check-square-o".
 * Note: The stock FontAwesome classes (fa, fa-fw, fa-border, etc) can be changed from above. Just make sure that the
 * icon-example-base and icon-example have the same classes, since JavaScript will overwrite the icon-example element
 * with the icon-example-base element whenever the textbox changes.
 *
 * - CSS:
 * This can be themed in any way, or the default styles in styles.css may be used.
 *
 * - JavaScript:
 * Look in scripts.js for the necessary JavaScript for this template.
 *
 * - PHP:
 * Ensure that PLACEHOLDER_ICON is set the same as defaultIcon in the JavaScript file, and that an icon (either the
 * placeholder or the saved icon) is always available to the HTML.
 */

namespace MM\Custom_Post_Types;

require_once 'inc/meta-traverse.php';
require_once 'inc/ajax-request.php';
require_once 'inc/renderers/background-chooser.php';
require_once 'inc/renderers/collapsed-select.php';
require_once 'inc/renderers/date-picker.php';
require_once 'inc/renderers/dropdown-url-field.php';
require_once 'inc/renderers/field-interface.php';
require_once 'inc/renderers/icon-chooser.php';
require_once 'inc/renderers/nav-bar-item.php';
require_once 'inc/renderers/news-category-select.php';
require_once 'inc/renderers/plain-text.php';
require_once 'inc/renderers/plain-textarea.php';
require_once 'inc/renderers/select.php';
require_once 'inc/renderers/set.php';
require_once 'inc/renderers/sortable-list.php';
require_once 'inc/renderers/table.php';
require_once 'inc/renderers/text-editor.php';
require_once 'inc/renderers/texturized-text.php';
require_once 'inc/renderers/type-select.php';
require_once 'inc/renderers/url-field.php';

register_activation_hook(__FILE__, array('\MM\Custom_Post_Types\Main', 'plugin_activation'));

// Initializes plugin. Anything in Main->__construct() is executed.
global $mm_custom_post_types;
$mm_custom_post_types = new Main();

/**
 * Defines global plugin settings, sets up plugin, provides handlers for Wordpress hooks.
 *
 * @package MM\Custom_Post_Types
 */
class Main {
    static $POST_TYPE_LIST;

    static function init_module_list() {
        self::$POST_TYPE_LIST = array(
            array(
                'slug' => 'header',
                'name' => 'Headers',
                'singular_name' => 'Header',
                'roles' => array('administrator', 'editor'),
                'icon' => 'dashicons-slides',
                'template_path' => 'template-parts/modules/header',
                'is_module' => true,
                'meta' => array(
                    array(
                        'name' => 'header_info',
                        'title' => 'Header Info',
                        'renderer' => new Table(
                            array(
                                array(
                                    'name' => 'type',
                                    'title' => 'Type',
                                    'renderer' => new Type_Select(
                                        array(
                                            'title' => 'Title',
                                            'image' => 'Image Background',
                                            'video' => 'Video Background',
                                        )
                                    ),
                                ),
                                array(
                                    'name' => 'title',
                                    'title' => 'Title',
                                    'renderer' => new Texturized_Text('Title'),
                                    'options' => array(
                                        'types' => array('image', 'video', 'title'),
                                    ),
                                ),
                                array(
                                    'name' => 'sub-title',
                                    'title' => 'Title',
                                    'renderer' => new Texturized_Text('Pre-Title'),
                                    'options' => array(
                                        'types' => array('image', 'video', 'title'),
                                    ),
                                ),
                                array(
                                    'name' => 'text-align',
                                    'title' => 'Text Alignment',
                                    'renderer' => new Select(
                                        array(
                                            'center' => 'Center',
                                            'left' => 'Left',
                                            'right' => 'Right',
                                        )
                                    ),
                                    'options' => array(
                                        'types' => array('image', 'video'),
                                    ),
                                ),
                                array(
                                    'name' => 'background',
                                    'title' => 'Background Image',
                                    'renderer' => new Background_Chooser(
                                        'header_background',
                                        'Header Background'
                                    ),
                                    'options' => array(
                                        'types' => array('image'),
                                        'has_default' => true,
                                    ),
                                ),
                                array(
                                    'name' => 'background-video',
                                    'title' => 'Background Video',
                                    'renderer' => new Table(
                                        array(
                                            array(
                                                'name' => 'mp4',
                                                'title' => 'MP4 File',
                                                'renderer' => new Plain_Text('MP4 File URL'),
                                            ),
                                            array(
                                                'name' => 'ogv',
                                                'title' => 'OGV File',
                                                'renderer' => new Plain_Text('OGV File URL'),
                                            ),
                                        )
                                    ),
                                    'options' => array(
                                        'types' => array('video'),
                                    ),
                                ),
                                array(
                                    'name' => 'content',
                                    'title' => 'Contents',
                                    'renderer' => new Text_Editor(
                                        array(
                                            'teeny' => true,
                                            'media_buttons' => false,
                                            'textarea_rows' => 4,
                                        )
                                    ),
                                    'options' => array(
                                        'types' => array('image', 'video'),
                                    ),
                                ),
                            ),
                            array(
                                'has_types' => true,
                            )
                        ),
                    ),
                ),
            ),
            array(
                'slug' => 'analytics',
                'name' => 'Analytics',
                'singular_name' => 'Analytics Snippets',
                'icon' => 'dashicons-chart-area',
                'template_path' => 'template-parts/modules/analytics',
                'meta' => array(
                    array(
                        'name' => 'analytics_code',
                        'title' => 'Analytics Code',
                        'renderer' => new Table(
                            array(
                                array(
                                    'name' => 'code',
                                    'title' => 'Code',
                                    'renderer' => new Plain_Textarea('Analytics Code'),
                                ),
                            )
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Main constructor.
     *
     * Sets up hooks.
     */
    function __construct() {
        if (!self::$POST_TYPE_LIST) self::init_module_list();

        // Registers post types and taxonomies
        add_action('init', array($this, 'register_post_types'));
        add_action('init', array($this, 'register_taxonomies'));

        // Adds metaboxes to post edit page
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));

        // When ready to enqueue scripts/stylesheets, do so
        add_action('admin_enqueue_scripts', array($this, 'load_scripts'), 11);

        for ($i = 0; $i < count(self::$POST_TYPE_LIST); $i++) {
            $module = self::$POST_TYPE_LIST[$i];
            // When post is created/updated, call this
            add_action('save_post_' . $module['slug'], array($this, 'save_post_meta'), 99, 3);

            if (self::$POST_TYPE_LIST[$i]['is_module']) {
                self::$POST_TYPE_LIST[$i]['meta_traverse'] = new Meta_Traverse($module['slug'], $module['singular_name'], 'select', array($this, 'list_modules'), array($module['slug']));
            }
        }
    }

    function list_modules($module) {
        $posts = get_posts(array(
            'post_type' => $module,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'order' => 'ASC',
            'orderby' => 'title',
        ));

        $ret[] = array(
            'value' => 'none',
            'title' => 'None',
        );

        foreach ($posts as $post) {
            $ret[] = array(
                'value' => $post->ID,
                'title' => $post->post_title,
            );
        }

        return $ret;
    }

    /**
     * Registers the Headers post type, adding it to the navigation bar and allowing other hooks to function
     */
    function register_post_types() {
        foreach (self::$POST_TYPE_LIST as $module) {
            if (post_type_exists($module['slug'])) continue;

            $labels = array(
                'name' => __($module['name'], 'custom-' . $module['slug'] . '-post-type'),
                'singular_name' => __($module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'add_new' => __('Add ' . $module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'add_new_item' => __('Add ' . $module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'edit_item' => __('Edit ' . $module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'new_item' => __('New ' . $module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'view_item' => __('View ' . $module['singular_name'], 'custom-' . $module['slug'] . '-post-type'),
                'search_items' => __('Search ' . $module['name'], 'custom-' . $module['slug'] . '-post-type'),
                'not_found' => __('No ' . strtolower($module['name']) . ' found', 'custom-' . $module['slug'] . '-post-type'),
                'not_found_in_trash' => __('No ' . strtolower($module['name']) . ' in the trash', 'custom-' . $module['slug'] . '-post-type'),
            );

            if (is_array($module['labels'])) {
                $labels = $module['labels'];
            }

            $supports = array(
                'title',
                'author',
            );

            if (is_array($module['supports'])) {
                $supports = $module['supports'];
            }

            $args = array(
                'labels' => $labels,
                'supports' => $supports,
                'public' => true,
                'publicly_queryable' => isset($module['archive']) && $module['archive'],
                'capability_type' => $module['slug'],
                'rewrite' => array('slug' => $module['slug']), // Permalinks format
                'menu_position' => 30,
                'menu_icon' => $module['icon'],
                'show_in_rest' => isset($module['rest']) && $module['rest'],
                'has_archive' => isset($module['archive']) && $module['archive'],
            );

            if (is_array($module['args'])) {
                $args = $module['args'];
            }

            $args = apply_filters('custom_' . strtolower($module['name']) . '_post_type_args', $args);
            register_post_type($module['slug'], $args);
        }
    }

    static function register_taxonomies() {
        foreach (self::$POST_TYPE_LIST as $module) {
            if (!is_array($module['taxonomies'])) continue;

            foreach ($module['taxonomies'] as $taxonomy) {
                if (taxonomy_exists($taxonomy['slug'])) {
                    register_taxonomy($taxonomy['slug'], array($module['slug']));
                    continue;
                }

                $labels = array(
                    'name' => __($taxonomy['name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'singular_name' => __($taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'add_new' => __('Add ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'edit_item' => __('Edit ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'new_item' => __('New ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'view_item' => __('View ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'search_items' => __('Search ' . $taxonomy['name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'parent_item' => __('Parent ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'parent_item_colon' => __('Parent ' . $taxonomy['singular_name'] . ':', 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'update_item' => __('Update ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'add_new_item' => __('Add New ' . $taxonomy['singular_name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'new_item_name' => __('New ' . $taxonomy['singular_name'] . ' Name', 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                    'menu_name' => __($taxonomy['name'], 'custom-' . $taxonomy['slug'] . '-taxonomy-type'),
                );

                if (is_array($taxonomy['labels'])) {
                    $labels = $taxonomy['labels'];
                }

                $args = array(
                    'hierarchical' => $taxonomy['hierarchical'],
                    'labels' => $labels,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'query_var' => true,
                    'rewrite' => array('slug' => $taxonomy['slug']),
                    'show_in_rest' => isset($module['rest']) && $module['rest'],
                );

                if (is_array($taxonomy['args'])) {
                    $args = $taxonomy['args'];
                }

                register_taxonomy($taxonomy['slug'], array($module['slug']), $args);
            }
        }
    }

    /**
     * Adds required capabilities to admin role on plugin activation
     */
    static function plugin_activation() {
        if (!self::$POST_TYPE_LIST) self::init_module_list();

        foreach (self::$POST_TYPE_LIST as $module) {
            $roles = array('administrator');

            if (is_array($module['roles'])) {
                $roles = $module['roles'];
            }

            foreach ($roles as $role_name) {
                $role = get_role($role_name);

                $role->add_cap('edit_' . $module['slug']);
                $role->add_cap('read_' . $module['slug']);
                $role->add_cap('delete_' . $module['slug']);
                $role->add_cap('edit_' . $module['slug'] . 's');
                $role->add_cap('edit_others_' . $module['slug'] . 's');
                $role->add_cap('publish_' . $module['slug'] . 's');
                $role->add_cap('read_private_' . $module['slug'] . 's');
                $role->add_cap('create_' . $module['slug'] . 's');
            }
        }
    }

    /**
     * Enqueues scripts and stylesheets for Wordpress to put in header. Called as action handler.
     */
    function load_scripts() {
        wp_enqueue_script('mm-meta-traverse', plugins_url('mm-custom-post-types/js/meta-traverse.js'), array('jquery'), '2.0.0', true);

        if (!self::is_on_edit_screen()) return;

        wp_enqueue_script('jquery-serialize-object', plugins_url('mm-custom-post-types/js/jquery.serialize-object.js'), array('jquery'));

        //TODO: Remove these rands
        wp_enqueue_script('mm-custom-post-types', plugins_url('mm-custom-post-types/js/scripts.js'), array('jquery', 'jquery-ui-sortable', 'jquery-serialize-object'), '2.0.0', true);

        wp_enqueue_style('font-awesome', "//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css", array());

        wp_enqueue_style('mm-custom-post-types', plugins_url('mm-custom-post-types/styles/styles.css'), array('font-awesome'), '2.0.0');

        wp_enqueue_style('jquery-ui-datepicker');
    }

    /**
     * Adds metaboxes into page. See show_defaults_meta_box and show_slides_meta_box for more info.
     */
    function add_meta_boxes() {
        wp_enqueue_media();

        foreach (self::$POST_TYPE_LIST as $module) {
            foreach ($module['meta'] as $meta_data) {
                if (!$meta_data['name']) continue;
                if (!($meta_data['renderer'] instanceof Field_Interface)) continue;
                add_meta_box($meta_data['name'], $meta_data['title'], array($meta_data['renderer'], 'meta_box_display'), $module['slug'], 'normal', 'high', array('name' => $meta_data['name']));
            }
        }
    }

    function save_post_meta($post_id, $post, $update) {
        $post_type = $post->post_type;
        $module = array();

        foreach (self::$POST_TYPE_LIST as $item) {
            if ($item['slug'] == $post_type) {
                $module = $item;
                break;
            }
        }

        if (!$module) return;

        foreach ($module['meta'] as $meta_data) {
            if (!$meta_data['name']) continue;
            if (!isset($_REQUEST[$meta_data['name']])) return;
        }

        foreach ($module['meta'] as $meta_data) {
            if (!$meta_data['name']) continue;
            if (!($meta_data['renderer'] instanceof Field_Interface)) continue;
            update_post_meta($post_id, $meta_data['name'], $meta_data['renderer']->save($_REQUEST[$meta_data['name']], $post));
        }
    }

    /**
     * If the module needs to be tested for before actually using it, this function will return true if a module will
     * be rendered, and false otherwise.
     *
     * @param $module_slug
     * @param array $settings
     *
     * @return bool true if template will be rendered, false if not
     *
     */
    function has_module($module_slug, $settings = array()) {
        @$page_id = $settings['page_id'] ?: 0;
        @$page_type = $settings['page_type'] ?: 'page';
        @$default_id = $settings['default_id'] ?: 0;
        @$module_id = $settings['module_id'] ?: 0;
        @$is_single = $settings['is_single'] ?: false;

        $module = array();

        foreach (self::$POST_TYPE_LIST as $item) {
            if ($item['slug'] == $module_slug) {
                $module = $item;
                break;
            }
        }

        if (!$module) return false;

        if (!$module['is_module']) return false;

        if (isset($settings['module_slug'])) {
            $module_post = get_posts(array(
                'post_type' => $module_slug,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'name' => $settings['module_slug'],
            ));
            if (count($module_post)) $module_id = $module_post[0]->ID;
        }

        if (!$module_id) {
            if (isset($settings['default_slug'])) {
                $default_post = get_posts(array(
                    'post_type' => $module_slug,
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'name' => $settings['default_slug'],
                ));
                if (count($default_post)) $default_id = $default_post[0]->ID;
            }

            if (!$page_id) {
                $page_id = get_queried_object_id();
                if (is_page()) $page_type = 'page';
                else if (is_category()) $page_type = 'category';
                else return false;
            }

            $meta_traverse = $module['meta_traverse'];

            if (!($meta_traverse instanceof Meta_Traverse)) return false;

            $module_id = $meta_traverse->get_meta_value($page_type, $page_id, $is_inherited, $is_single);
            if (!$module_id && $default_id) $module_id = $default_id;
            if (!$module_id || $module_id === 'none') return false;
        }

        $module_post = get_post($module_id);
        if (!$module_post) return false;

        return true;
    }

    /**
     * Gets module for current page if none is defined, or the given page if $page_id is defined. Uses algorithm in
     * Meta_Traverse class (inc/meta-traverse.php) to retrieve the correct hierarchical header. If the ID given is not
     * a page, or a module cannot be found, the function returns false. Otherwise returns true.
     *
     * @param $module_slug
     * @param array $settings
     *
     * @return bool true if template was rendered, false if not
     */
    function get_module($module_slug, $settings = array()) {
        @$page_id = $settings['page_id'] ?: 0;
        @$page_type = $settings['page_type'] ?: 'page';
        @$default_id = $settings['default_id'] ?: 0;
        @$module_id = $settings['module_id'] ?: 0;
        @$is_single = $settings['is_single'] ?: false;

        $is_inherited = false;

        $module = array();

        foreach (self::$POST_TYPE_LIST as $item) {
            if ($item['slug'] == $module_slug) {
                $module = $item;
                break;
            }
        }

        if (!$module) return false;

        if (!$module['is_module']) return false;

        if (isset($settings['module_slug'])) {
            $module_post = get_posts(array(
                'post_type' => $module_slug,
                'post_status' => 'publish',
                'posts_per_page' => 1,
                'name' => $settings['module_slug'],
            ));
            if (count($module_post)) $module_id = $module_post[0]->ID;
        }

        if (!$module_id) {
            if (isset($settings['default_slug'])) {
                $default_post = get_posts(array(
                    'post_type' => $module_slug,
                    'post_status' => 'publish',
                    'posts_per_page' => 1,
                    'name' => $settings['default_slug'],
                ));
                if (count($default_post)) $default_id = $default_post[0]->ID;
            }

            if (!$page_id) {
                $page_id = get_queried_object_id();
                if (is_page()) $page_type = 'page';
                else if (is_category()) $page_type = 'category';
                else return false;
            }

            $meta_traverse = $module['meta_traverse'];

            if (!($meta_traverse instanceof Meta_Traverse)) return false;

            $module_id = $meta_traverse->get_meta_value($page_type, $page_id, $is_inherited, $is_single);
            if (!$module_id && $default_id) $module_id = $default_id;
            if (!$module_id || $module_id === 'none') return false;
        }

        $module_post = get_post($module_id);
        if (!$module_post) return false;

        set_query_var('module_is_inherited', $is_inherited);
        set_query_var('module_title', $module_post->post_title);
        set_query_var('module_slug', $module_post->post_name);
        set_query_var('module_id', $module_id);

        foreach ($module['meta'] as $meta_data) {
            if (!$meta_data['name']) continue;
            if (!($meta_data['renderer'] instanceof Field_Interface)) continue;

            $meta = get_post_meta($module_id, $meta_data['name'], true);
            set_query_var($meta_data['name'], $meta_data['renderer']->retrieve($meta, $module_post));
        }

        get_template_part($module['template_path']);

        return true;
    }

    static function is_on_edit_screen() {
        foreach (self::$POST_TYPE_LIST as $module) {
            if ($module['slug'] == get_current_screen()->post_type) return true;
        }

        return false;
    }
}
