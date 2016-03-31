<?php
/**
* Template and guide for modifications
* 
* All files should be referred to their original files/folders
* During upgrade of Core release, implement the overwrite scripts on all affected 
*
*/
####################################################################################################
#
#            ON ROOT FILES
#
###########-TOP-##############################
$modfile = implode(DIRECTORY_SEPARATOR,array(
    dirname(__FILE__),
    '_mods',
    str_replace(dirname(__FILE__),'',__FILE__)
));
if(file_exists($modfile)){
    include($modfile);
}
else{
    die($modfile);
###########-END-TOP-##############################
    ###
    ### all code in the file
    ###
###########-END-OF-THE-FILE-##############################
} #

####################################################################################################
#
#            ON INCLUDED FILES
#################################################
$modfile = implode(DIRECTORY_SEPARATOR,array(
    ROOT_DIR, #struc for includes
    '_mods',
    str_replace(ROOT_DIR,'',__FILE__)
));
if(file_exists($modfile)){
    include($modfile);
}
else{
    die($modfile);
    
###################################################################################################
# JAVASCRIPTS

/**
* Use $EndScript to include in the footer <script> tags
* Use $JSScript to include literal scripts to be executed on document.ready
* Use $JSFunctions to include literal JS functions
*/

###################################################################################################
#
##      FILES AND FOLDERS TO BE SET AS SYMBOLIC LINKS OR ALIASES
#           - can be duplicated on both locations for physical path resolutions
###################################################################################################
/javascripts/jquery         =>  /_mods/javascripts/jquery
/ajax                       =>  /_mods/ajax