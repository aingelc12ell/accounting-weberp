<?php

function isSetParam($param,$paramArray=array()){
    if(is_array($paramArray)){
        return in_array($param,$paramArray,true)
            || array_key_exists($param,$paramArray)
            #|| isset($paramArray[$param])
            ;
    }
    return $param == $paramArray;
}

function numberonly($string,$params=array()){
    $regex = isSetParam('digits',$params)
        ? "/[^0-9]/"
        : "/[^0-9\.\-]/"
        ;
    return preg_replace($regex,"",trim($string));
}