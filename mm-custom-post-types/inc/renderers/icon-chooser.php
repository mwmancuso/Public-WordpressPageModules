<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Icon_Chooser extends Field_Base {
    /**
     * When no icon is specified for buttons, this is used.
     */
    const PLACEHOLDER_ICON = 'square-o';

    function display($prefix, $initial_value, $options = array()) {
        ?>
        <div class="icon-example-container">
            <i class="fa fa-fw fa-border fa-4x icon-example-base"></i> <i
                class="fa fa-fw fa-border fa-4x icon-example fa-<?php echo $initial_value ?: self::PLACEHOLDER_ICON; ?>"></i><br/> fa-<input
                type="text" name="<?php echo $prefix; ?>" id="<?php echo $prefix; ?>"
                value="<?php echo htmlentities($initial_value); ?>"
                class="regular-text icon-example-text"
                placeholder="check-square-o"/><br/> <span class="description"><a
                    href="https://fortawesome.github.io/Font-Awesome/icons/">Click here</a> to find an icon.</span>
        </div>
        <?php
    }
}