<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Date_Picker extends Field_Base {
    private $start_date;
    private $end_date;

    function __construct($start_date = '-10 years', $end_date = '+10 years') {
        $this->start_date = $start_date;
        $this->end_date = $end_date;

        wp_enqueue_script('jquery-ui-datepicker', null, null, null, true);
    }

    function display($prefix, $initial_value, $options = array()) {
        $id = substr(md5($prefix), 0, 8);
        ?>
        <input type="text" class="datepicker" name="<?php echo $prefix; ?>" id="<?php echo $id; ?>" value="<?php if (!empty($initial_value)) echo date('F j, Y', $initial_value); ?>">
        <script type="text/javascript">
            jQuery(function($) {
                $('#<?php echo $id; ?>').datepicker({
                    minDate: new Date('<? echo date('D M d Y H:i:s O', strtotime($this->start_date)); ?>'),
                    maxDate: new Date('<? echo date('D M d Y H:i:s O', strtotime($this->end_date)); ?>'),
                    changeMonth: true,
                    changeYear: true
                });
            });
        </script>
        <?php
    }

    function save($input, $post) {
        return strtotime($input);
    }
}