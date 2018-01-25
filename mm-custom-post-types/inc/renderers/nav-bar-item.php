<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Nav_Bar_Item extends Field_Base {
    static $DONE_AUTO_POPULATE_INIT = false;

    static $MAX_ITEMS;

    var $type;

    var $settings;

    function __construct($type, $settings = array()) {
        $this->type = $type;
        $this->settings = $settings;

        if (!self::$DONE_AUTO_POPULATE_INIT) {
            self::$MAX_ITEMS = $settings['max_items'];

            add_action('wp_ajax_mm_nav_bar_auto_populate', array($this, 'handle_auto_populate_ajax'));
            self::$DONE_AUTO_POPULATE_INIT = true;
        }
    }

    function display($prefix, $initial_value, $options = array()) {
        if ($this->type == 'auto-populate') {
            $level = $this->settings['level'];
            $max_levels = $this->settings['max_levels'];
            ?>
            <input type="text"
                class="regular-text auto-populate-id" placeholder="Parent URL or Page ID"
                data-level="<?php echo $level; ?>"/>
            <?php if ($level < $max_levels - 1) { ?>
                <select class="auto-populate-max" data-level="<?php echo $level; ?>" style="margin-top: -2px;">
                    <?php for ($i = $level; $i < $max_levels; $i++) { ?>
                        <option <?php self::option_value($i, $max_levels - 1); ?>>
                            Level <?php echo $i + 1; ?></option>
                    <?php } ?>
                </select>
            <?php } else { ?>
                <input type="hidden" class="auto-populate-max" value="<?php echo $level; ?>">
            <?php } ?>
            <?php if ($level == 0) { ?>
                <input type="button" value="Use as Home Page" class="button auto-populate-action"
                    data-use-as-home="true" data-level="<?php echo $level; ?>"/>
            <?php } else { ?>
                <input type="button" value="Use as Container" class="button auto-populate-action"
                    data-level="<?php echo $level; ?>"/>
            <?php } ?><br/>
            <span class="auto-populate-status description" style="display: none;"
                data-level="<?php echo $level; ?>"></span>
            <?php
        } else if ($this->type == 'reset') {
            $level = $this->settings['level'];
            ?>
            <?php if ($level === '') { ?>
                <input type="button" value="Reset All" class="reset-item button reset-all"/>
            <?php } else { ?>
                <input type="button" value="Reset" class="reset-item button"/>
            <?php } ?>
            <?php
        } else if ($this->type == 'hidden-type') {
            ?>
            <input type="hidden" name="<?php echo $prefix; ?>" id="<?php echo $prefix; ?>" class="show-hide-selector"
                data-identifier="<?php echo $options['table_identifier']; ?>" value="<?php echo $initial_value ?: 'none'; ?>">
            <?php
        }
    }

    function handle_auto_populate_ajax() {
        $ajax_request = new AJAX_Request();
        $ajax_request->do_json_headers();

        $page_id = $_POST['page-id'];
        $level = $_POST['level'];
        $use_as_home = $_POST['use-as-home'];
        $max = $_POST['max-level'];

        if ($level >= count(self::$MAX_ITEMS) || $max >= count(self::$MAX_ITEMS)) {
            echo json_encode(array(
                'error' => 'Level too deep.',
            ));
            exit();
        }

        if (!is_numeric($page_id)) {
            $page_id = url_to_postid($page_id);
        }

        if (!$page_id) {
            echo json_encode(array(
                'error' => 'Cannot find original page.',
            ));
            exit();
        }

        $ret = array();

        self::populate_json_sub_levels($ret, $page_id, $level, $max, $use_as_home);

        if ($use_as_home) {
            array_unshift($ret, array(
                'title' => 'Home',
                'url' => $page_id,
            ));
        }

        echo json_encode($ret);
        exit();
    }

    private static function populate_json_sub_levels(&$items, $page_id, $level, $max, $use_as_home) {
        if ($level >= count(self::$MAX_ITEMS) || $max >= count(self::$MAX_ITEMS)) return;

        $args = array(
            'post_type' => 'page',
            'posts_per_page' => $use_as_home ? self::$MAX_ITEMS[$level] - 1 : self::$MAX_ITEMS[$level],
            'order_by' => 'menu_order title',
            'order' => 'ASC',
        );

        if ($page_id) {
            $args['post_parent'] = $page_id;
        }

        $posts = get_posts($args);

        foreach ($posts as $post) {
            if ($level < (count(self::$MAX_ITEMS) - 1) && $level < $max) {
                $sub_items = array();
                self::populate_json_sub_levels($sub_items, $post->ID, $level + 1, $max, false);
                if (count($sub_items)) {
                    $items[] = array(
                        'title' => $post->post_title,
                        'items' => $sub_items,
                    );
                } else {
                    $items[] = array(
                        'title' => $post->post_title,
                        'url' => $post->ID,
                    );
                }
            } else {
                $items[] = array(
                    'title' => $post->post_title,
                    'url' => $post->ID,
                );
            }
        }
    }
}