<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Type_Select extends Field_Base {
    var $options;

    function __construct($options) {
        $this->options = $options;
    }

    function display($prefix, $initial_value, $options = array()) {
        ?>
        <select name="<?php echo $prefix; ?>" id="<?php echo $prefix; ?>" class="show-hide-selector"
            data-identifier="<?php echo $options['table_identifier']; ?>">
            <option <?php self::option_value('none', $initial_value); ?>>None</option>
            <?php foreach ($this->options as $value => $title) { ?>
                <option <?php self::option_value($value, $initial_value); ?>><?php echo $title; ?></option>
            <?php } ?>
        </select>
        <?php
    }
}