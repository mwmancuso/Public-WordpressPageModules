<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Plain_Text extends Field_Base {
    var $placeholder;

    function __construct($placeholder) {
        $this->placeholder = $placeholder;
    }

    function display($prefix, $initial_value, $options = array()) {
        ?>
        <input type="text" name="<?php echo $prefix; ?>"
            id="<?php echo $prefix; ?>"
            value="<?php echo htmlentities($initial_value); ?>" class="regular-text"
            placeholder="<?php echo htmlentities($this->placeholder); ?>"/>
        <?php
    }
}