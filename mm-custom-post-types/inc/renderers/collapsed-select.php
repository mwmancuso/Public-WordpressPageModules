<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Collapsed_Select extends Field_Base {
    function display($prefix, $initial_value, $options = array()) {
        ?>
        <select name="<?php echo $prefix; ?>" id="<?php echo $prefix; ?>">
            <?php if (!$options['display_default']) { ?>
                <option <?php self::option_value('', $initial_value); ?>>Default</option>
            <?php } ?>
            <option <?php self::option_value('never', $initial_value); ?>>Never</option>
            <option <?php self::option_value('inherited', $initial_value); ?>>Inherited Only</option>
            <option <?php self::option_value('always', $initial_value); ?>>Always</option>
        </select>
        <?php
    }
}