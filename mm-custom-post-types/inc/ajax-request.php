<?php
namespace MM\Custom_Post_Types;
/**
 * Class for AJAX utility functions.
 *
 * May be packaged with any plugin, just make sure to change namespace.
 *
 * No plugin-specific code here--should be able to be moved between plugins by only changing namespace.
 */
class AJAX_Request {
    /**
     * Sends JSON headers to browser
     */
    public function do_json_headers() {
        status_header(200);
        header("Expires: 0");
        header("Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");
        header('Content-type: application/json');
    }

    /**
     * Checks to see if global Wordpress ajaxurl has been added to Javascript, and if not, adds it.
     *
     * Wordpress does not, by default, include ajaxurl for non-admin pages. This adds it in case a plugin needs it.
     */
    public function enqueue_global_ajaxurl() {
        global $global_ajax_url_enqueued;
        if (!$global_ajax_url_enqueued) {
            add_action('wp_head', array($this, 'inject_ajaxurl'));
        }
        $global_ajax_url_enqueued = true;
    }

    /**
     * Actually injects the ajaxurl into the head of a document. Do not call directly due to duplication.
     */
    public function inject_ajaxurl() {
        ?>
        <script type="text/javascript">
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script>
        <?php
    }
}