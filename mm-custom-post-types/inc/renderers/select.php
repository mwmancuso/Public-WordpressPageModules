<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Select extends Field_Base {
    var $options;

    function __construct($options) {
        $this->options = $options;
    }

    function display($prefix, $initial_value, $options = array()) {
        ?>
        <select name="<?php echo $prefix; ?>" id="<?php echo $prefix; ?>">
            <?php foreach ($this->options as $value => $title) { ?>
                <option <?php self::option_value($value, $initial_value); ?>><?php echo $title; ?></option>
            <?php } ?>
        </select>
        <?php
    }
}