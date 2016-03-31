<?php

/* $Id: footer.inc 6594 2014-03-02 23:15:39Z turbopt $*/

echo '</div>'; // BodyWrapDiv
echo '</div>'; // BodyDiv

echo '<div id="FooterDiv">';
echo '<div id="FooterWrapDiv">';

echo '<div id="FooterLogoDiv">';
	echo '<img src="'. $RootPath . '/' . $_SESSION['LogoFile'] . '" width="120" alt="webERP" title="webERP ' . _('Copyright') . ' &copy; weberp.org - ' . date('Y') . '" />';
echo '</div>';

echo '<div id="FooterTimeDiv">';
	echo DisplayDateTime();
echo '</div>';

echo '<div id="FooterVersionDiv">';
echo '</div>';

echo '</div>'; // FooterWrapDiv
echo '</div>'; // FooterDiv
echo '</div>'; // Canvas

echo '<!-- delayed script -->';
####################### JAVASCRIPTS
if(isset($EndScript)){
    echo $EndScript;
}
if(isset($JScript) || isset($JSFunctions)){
    echo '<script language="javascript" type="text/javascript">'
            .(isset($JSFunctions) ? $JSFunctions : '')
            ."$().ready(function(){"
                .(isset($JScript) ? $JScript : '')
            ."});"
        .'</script>'
        ;
}

echo '</body>';
echo '</html>';

