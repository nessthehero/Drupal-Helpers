<?php

/**
 * Returns aliased node url
 * @param  int $nid  	Node ID
 * @return string    	Aliased URL
 */
function nu($nid) {
	return drupal_get_path_alias('node/' . $nid);
}

/**
 * Returns key of array or default.
 * Mostly fed drupal_get_query_parameters() to check if query string
 * exists.
 *
 * @param  array  $q       [description]
 * @param  string $i       [description]
 * @param  string $default [description]
 *
 * @return [type]          [description]
 */
function qs($q = array(), $i = '', $default = '') {

    if (!empty($q) && !empty($i)) {

        return strtolower(nvl(nvl($q, $i), $default, ''));

    } else {

        return $default;

    }

}

/**
 * Returns field value from node. Can be an array or single value.
 * Returns different values depending on type of field.
 *
 * @param  Drupal Node $node  Node entity we are grabbing field from
 * @param  string $field Name of field ID
 * @param  string $mode  view mode (optional)
 *
 * @return array        Array of fields or field array
 */
function nv($node, $field, $mode = 'full') {

    $lang = nvl($node->language, "und");

    $items = field_get_items("node", $node, $field, $lang);

    // Get field type
    $f = field_info_field($field);
    $type = $f['type'];

    $output = array();

    if (is_array($items) && count($items) > 0) {

        foreach ($items as $value) {

            // Determine output based on field type
            switch ($type) {

                // For booleans, we only need the value, since there is no reason to render anything
                case 'list_boolean':
                    $output[] = $value['value'];
                    break;

                case 'text':
                    $output[] = nvl(
                        nvl($value, 'safe_value'),
                        nvl($value, 'value')
                    );
                    break;

                case 'link_field': // From Link module
                    $output[] = $value;
                    break;

                case 'image':
                    $output[] = array(
        /* object */    'o' => $value,
        /* URL */       'u' => file_create_url($value['uri']),
        /* Markup */    'm' => removehw(image($value['uri'], $value['alt'], $value['title']))
                    );
                    break;

                case 'list_text':
                    $output[] = $value['value'];
                    break;

                case 'node_reference': // From node reference module
                    if (isset($value['node'])) {
                        $output[] = node_view($value['node'], $mode);
                    }
                    break;

                case 'revisionreference': // From revisionreference module
                    if (isset($value['vid'])) {
                        $node = node_load(NULL, $value['vid']);
                        if (isset($node->nid)) {
                            $output[] = node_view($node, $mode);
                        }
                    }
                    break;

                case 'taxonomy_term_reference':
                    $output[] = $value;
                    break;

                default: // Get node_view renderable value of field.
                    $output[] = nvl(field_view_value("node", $node, $field, $value, $mode, $lang), array());
                    break;

            }

        }

    }

    // Returns array of all items or first item if there is only one.
    if (count($output) == 1) {
        return array_pop($output);
    } else {
        return $output;
    }

}

/**
 * returns a nav as markup
 *
 * @param  string $items   list item for nav
 * @param  array  $navAttr optional array of attributes for nav tag
 * @param  array  $ulAttr  optional array of attributes for ul tag
 *
 * @return string          markup
 */
function nav($items, $navAttr = array(), $ulAttr = array()) {

    $output = "";
    $li = array();

    if (count($items) > 0) {

        foreach ($items as $i => $item) {

            $pAttr = array_merge(array(), $item["link"]["options"]["attributes"], $item["link"]["localized_options"]["attributes"]);

            $li[] = "<li>" . l($item["link"]["link_title"], $item["link"]["link_path"], array('attributes' => $pAttr)) . "</li>";

        }

        $output .= "<nav " . drupal_attributes($navAttr) . ">";
        $output .= ul($li, $ulAttr);
        $output .= "</nav>";

    }

    return $output;

}

/**
 * returns an unordered list as markup
 *
 * @param  string $items  list item as string
 * @param  array  $ulAttr optional array of attributes for ul tag
 *
 * @return string         markup
 */
function ul($items, $ulAttr = array()) {

    $output = "";

    if (count($items) > 0) {

        $output .= "<ul " . drupal_attributes($ulAttr) . ">";

        foreach ($items as $i => $item) {

            $output .= $item;

        }

        $output .= "</ul>";

    }

    return $output;

}

/**
 * Converts local csv file to array
 * @param  string $filename  - local file
 * @param  string $delimiter - CSV delimiter
 * @return array             - CSV array
 */
function csv_to_array($filename='', $delimiter=',')
{
	if(!file_exists($filename) || !is_readable($filename))
		return FALSE;

		$csv = array();

		if (($handle = fopen($filename, "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

			$csv[] = $data;

		}
		fclose($handle);

		return $csv;
	}
}

/**
 * Grab CSV value and put into array to be rendered or manipulated.
 *
 * @param  string $csv CSV string
 *
 * @return array      array of CSV value
 */
function csv_to_table($csv) {

    if (!empty($csv)) {

        $lines = explode("\n", $csv);

        $table = array();
        foreach ($lines as $line) {

            $table[] = str_getcsv(html_entity_decode(trim($line)), ',', '"');

        }

        return array_filter($table);

    } else {
        return array();
    }

}

/**
 * Returns attributes array of classes, for quickly providing classes to elements
 *
 * @param  string [arg1, arg2, ...] one or more strings for classes
 *
 * @return array attributes array of classes
 */
function classes() {

    $count = func_num_args();
    $classes = array();

    for ($i = 0; $i < $count; $i++)
    {

        $arg = func_get_arg($i);

        if (is_string($arg)) {
            $classes[] = $arg;
        }

    }

    return array(
        "attributes" => classarray($classes)
    );

}

/**
 * Returns the class array for an attributes array
 *
 * @param  string or array $classes space delimited string of classes or array of classes
 *
 * @return array          array of classes
 */
function classarray($classes) {

    $arr = array();

    if (is_array($classes)) {
        $arr = array(
            "class" => $classes
        );
    } else {
        $arr = array(
            "class" => explode(" ", $classes)
        );
    }

    return $arr;

}

/**
 * Returns the first entry that passes an isset() test.
 *
 * Each entry can either be a single value: $value, or an array-key pair:
 * $array, $key.  If all entries fail isset(), or no entries are passed,
 * then nvl() will return null.
 *
 * $array must be an array that passes isset() on its own, or it will be
 * treated as a standalone $value.  $key must be a valid array key, or
 * both $array and $key will be treated as standalone $value entries. To
 * be considered a valid key, $key must pass:
 *
 *     is_null($key) || is_string($key) || is_int($key) || is_float($key)
 *         || is_bool($key)
 *
 * If $value is an array, it must be the last entry, the following entry
 * must be a valid array-key pair, or the following entry's $value must
 * not be a valid $key.  Otherwise, $value and the immediately following
 * $value will be treated as an array-key pair's $array and $key,
 * respectfully.  See above for $key validity tests.
 */
function nvl(/* [(array $array, $key) | $value]... */)
{
    $count = func_num_args();

    for ($i = 0; $i < $count - 1; $i++)
    {
        $arg = func_get_arg($i);

        if (!isset($arg))
        {
            continue;
        }

        if (is_array($arg))
        {

            $key = func_get_arg($i + 1);

            if (is_null($key) || is_string($key) || is_int($key) || is_float($key) || is_bool($key))
            {

                if (isset($arg[$key]))
                {
                    return $arg[$key];
                }

                $i++;
                continue;
            }
        }



        return $arg;
    }

    if ($i < $count)
    {
        return func_get_arg($i);
    }

    return null;
}

/**
 * Find a value in a multidimensional array
 * @param  string $elem needle
 * @param  array $array multidimensional haystack
 * @return bool         boolean if value was found or not
 */
function in_multiarray($elem, $array) {
    foreach ($array as $key => $value) {
        if ($value==$elem){
            return true;
        }
        elseif(is_array($value)){
            if($this->in_multiarray($elem, $value))
                    return true;
        }
    }

    return false;
}

/**
 * Returns markup for an image tag
 *
 * @param  string  $uri    Path of image
 * @param  string  $alt    Alternate Text
 * @param  string  $title  Title text
 * @param  integer $w      Width
 * @param  integer $h      Height
 * @param  string  $styles Inline styles
 *
 * @return string          Image tag
 */
function image($uri, $alt = '', $title = '', $w = 0, $h = 0, $styles = '') {

    $url = file_create_url($uri);
    $a = ($alt != '') ? 'alt="'.$alt.'"' : '';
    $t = ($title != '') ? 'title="'.$title.'"' : '';
    $w = ($w != '') ? 'width="'.$w.'"' : '';
    $h = ($h != '') ? 'height="'.$h.'"' : '';
    $s = ($styles != '') ? 'style="'.$styles.'"' : '';

    return sprintf("<img src='%s' %s %s %s %s %s />",
        $url,
        $a,
        $t,
        $w,
        $h,
        $s
    );

}

/**
 * Remove height and width attributes from tag. Useful for responsive images.
 *
 * @param  string $image Markup
 *
 * @return string        Markup
 */
function removehw($image) {

    $new_string = preg_replace('/\<(.*?)((?:width|height)="(.*?)")(.*?)((?:width|height)="(.*?)")(.*?)\>/i', '<$1$4$7>', $image);
    return $new_string;

}

/**
 * Get vid of a vocabulary based on the name.
 *
 * @param  string $name Name of vocabulary
 *
 * @return int       ID of vocabulary
 */
function get_vid($name) {
    $names = taxonomy_vocabulary_get_names();
    if (isset($names[$name])) {
        return $names[$name]->vid;
    } else {
        return 0;
    }

}

/**
 * Render a select element using taxonomy terms. Uses id of term as value.
 *
 * @param  string  $vname   name of vocabulary
 * @param  integer $default vid of default term
 *
 * @return string           markup
 */
function taxOptions($vname, $default = 0) {

    $vid = get_vid($vname);
    $output = "";

    if ($vid != 0) {
        $tree = taxonomy_get_tree($vid);

        foreach ($tree as $key => $tag) {
            $output .= '<option value="'.$tag->tid.'" data-tid="'.$tag->tid.'" data-default="'.$default.'"';

            if (strcmp($default,$tag->tid) == 0) {
                $output .= ' selected';
            }
            $output .= '>'.$tag->name.'</option>';
        }
    }

    return $output;

}

/**
 * Render a select element using taxonomy terms. Uses name of term as value.
 *
 * @param  string  $vname   name of vocabulary
 * @param  integer $default vid of default term
 *
 * @return string           markup
 */
function taxOptionsName($vname, $default = "") {

    $vid = get_vid($vname);
    $output = "";

    if ($vid != 0) {
        $tree = taxonomy_get_tree($vid);

        foreach ($tree as $key => $tag) {
            $output .= '<option value="'.$tag->name.'" data-tid="'.$tag->tid.'" data-default="'.$default.'"';

            if (strcmp($default,$tag->name) == 0) {
                $output .= ' selected';
            }
            $output .= '>'.$tag->name.'</option>';
        }
    }

    return $output;

}
