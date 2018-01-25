<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Plain_Textarea extends Field_Base {
    var $placeholder;

    function __construct($placeholder) {
        $this->placeholder = $placeholder;
    }

    function display($prefix, $initial_value, $options = array()) {
        ?>
        <textarea type="text" name="<?php echo $prefix; ?>"
            id="<?php echo $prefix; ?>"
            style="width: 100%;" rows="4"
            placeholder="<?php echo htmlentities($this->placeholder); ?>"><?php echo htmlentities($initial_value); ?></textarea>
        <?php
    }
}