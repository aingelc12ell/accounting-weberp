<?php
$dir = dirname(__FILE__);

if ($dh = opendir($dir)) {
    $fileOrder = array();
    while (($file = readdir($dh)) !== false) {
        if(!in_array($file,array('.','..','index.php'))
            && is_file($dir.DIRECTORY_SEPARATOR.$file)
            && substr($file,-4)=='.inc'
        ){
            $fileOrder[$file] = $dir.DIRECTORY_SEPARATOR.$file;
        }
    }
    closedir($dh);
    krsort($fileOrder);
    echo '<pre>';
    foreach($fileOrder as $f => $file){
        echo str_repeat('=',80)
            ,"\n",str_repeat(" ",10),'BATCH: ',substr($f,0,-4),"\n"
            ,str_repeat('-',80)
            ,"\n"
            ,file_get_contents($file)
            ,"\n\n"
            ;
    }
    echo str_repeat('-',80)
        ,'</pre>';
}