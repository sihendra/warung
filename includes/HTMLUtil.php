<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of HTMLUtil
 *
 * @author hendra
 */
class HTMLUtil {

    /**
     * Return <select> element
     * @param int|string $id DOM id
     * @param int|string $name field name
     * @param array $data assoc array, k=>v , k will be set as value
     * @param int|string $default default value
     * @param int|string $class css class
     * @return string
     */
    public static function select($id, $name, $data, $default=null, $class=null) {
        $ret = '<select id="'.$id.'" name="'.$name.'"';
        if ($class) {
            $ret .= ' class="'.$class.'"';
        }
        $ret .= ">";
        foreach($data as $key=>$val) {
            if ($default != null && $key == $default) {
                $ret .= '<option value="'.$key.'" selected="selected">'.$val.'</option>';
            } else {
                $ret .= '<option value="'.$key.'">'.$val.'</option>';
            }
        }
        $ret .= "</select>";

        return $ret;
    }
}
?>
