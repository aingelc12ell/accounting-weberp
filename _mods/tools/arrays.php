<?php
function array_map_deep($array, $callback) {
    $new = array();
    foreach ($array as $key => $val) {
        if (is_array($val)) {
            $new[$key] = array_map_deep($val, $callback);
        } 
        else {
            $new[$key] = call_user_func($callback, $val);
        }
    }
    return $new;
}
function json_output($array){
    return json_encode(array_map_deep($array,'utf8_encode'),JSON_FORCE_OBJECT);
}