<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';

class Table extends Field_Base {
    var $rows;

    var $options;

    function __construct($rows, $options = array()) {
        $this->rows = $rows;

        $this->options = $options;
    }

    function display($prefix, $initial_value, $options = array()) {
        $table_identifier = substr(md5($prefix), 0, 10);
        $options['table_identifier'] = $table_identifier;
        $show_none = isset($initial_value['type'])
            ? $initial_value['type'] == 'none'
            : true;
        ?>
        <table
            <?php if ($this->options['has_types'] && !$this->options['show_hide_pre_contained']) { ?>
                class="form-table show-hide-container<?php if ($show_none) { echo ' show-none'; } ?>" data-identifier="<?php echo $table_identifier; ?>"
            <?php } else { ?>
                class="form-table"
            <?php } ?>>
            <tbody>
            <?php
            foreach ($this->rows as $row) {
                $field_name = $row['name'];
                $row_prefix = $field_name ? $prefix . '[' . $field_name . ']' : $prefix;
                $hidden = true;
                if (!$row['options']['types']) $hidden = false;
                else if (isset($initial_value['type'])) $hidden = !in_array($initial_value['type'], $row['options']['types']);

                if (!($row['renderer'] instanceof Field_Interface)) continue;
                ?>
                <tr
                    <?php if ($this->options['has_types']) { ?>
                        class="show-hide-element<?php if ($hidden) { echo ' hide'; } ?>" data-types="<?php echo join(' ', $row['options']['types'] ?: array()) ?: ''; ?>"
                        data-identifier="<?php echo $table_identifier; ?>"
                    <?php } ?>>
                    <?php if ($row['title']) { ?>
                        <th><?php if ($field_name) { ?>
                                <label for="<?php echo $row_prefix; ?>"><?php echo $row['title']; ?></label><?php } else echo $row['title']; ?>
                        </th>
                    <?php } ?>
                    <td>
                        <?php
                        $field_value = isset($initial_value[$field_name]) ? $initial_value[$field_name] : null;
                        $row['renderer']->display($row_prefix, $field_value, $options);
                        ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php
    }

    function save($input, $post) {
        foreach ($this->rows as $row) {
            if (!($row['renderer'] instanceof Field_Interface)) continue;
            if (!isset($input[$row['name']])) continue;

            $input[$row['name']] = $row['renderer']->save($input[$row['name']], $post);
        }

        return $input;
    }

    function retrieve($input, $post) {
        foreach ($this->rows as $row) {
            if (!($row['renderer'] instanceof Field_Interface)) continue;
            if (!isset($input[$row['name']])) continue;

            $input[$row['name']] = $row['renderer']->retrieve($input[$row['name']], $post);
        }

        return $input;
    }
}