<?php
namespace MM\Custom_Post_Types;

require_once 'field-base.php';
require_once 'url-field.php';

class Dropdown_URL_Field extends Field_Base {
    function display($prefix, $initial_value, $options = array()) {
        ?>
        <textarea name="<?php echo $prefix; ?>"
            style="width: 100%;" rows="4"
            placeholder="Home: http://www.example.edu/"><?php
            if ($initial_value) {
                foreach ($initial_value as $dd_url) {
                    echo $dd_url['title'] . ': ' . $dd_url['url'] . "\n";
                }
            }
            ?></textarea><br/>
        <span class="description">Enter one URL per line in form "Name: URL" or "Name: [Page ID]". Example:<br/>
                            Home: http://www.example.edu/<br/>
                            About Us: 6</span>
        <?php
    }

    function save($input, $post) {
        $input = self::text_urls_to_array($input);

        $url_field = new URL_Field();

        for ($i=0; $i<count($input); $i++) {
            $input[$i]['url'] = $url_field->save($input[$i]['url'], $post);
        }

        return $input;
    }

    function retrieve($input, $post) {
        $url_field = new URL_Field();

        for ($i=0; $i<count($input); $i++) {
            $input[$i]['url'] = $url_field->retrieve($input[$i]['url'], $post);
        }

        return $input;
    }

    /**
     * Takes dropdown URL text and converts it to an array.
     *
     * Input format of $button_list[$i]['dropdown-urls'] should be as follows:
     * Home: http://www.example.edu/
     * Google: http://www.google.com/
     *
     * Output will be:
     * array(
     *      array('title' => 'Home', 'url' => 'http://www.example.edu/'),
     *      array('title' => 'Google', 'url' => 'http://www.google.edu/'),
     * )
     *
     * @param $text
     *
     * @return array
     */
    private static function text_urls_to_array($text) {
        $urls = array();

        if (!$text) return $urls;

        $lines = explode("\n", trim($text));

        foreach ($lines as $line) {
            $elements = explode(':', trim($line), 2);
            $url = trim($elements[1]);

            $urls[] = array(
                'title' => trim($elements[0]),
                'url' => $url,
            );
        }

        return $urls;
    }
}