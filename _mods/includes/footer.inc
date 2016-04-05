<?php

/* $Id: footer.inc 6594 2014-03-02 23:15:39Z turbopt $*/

echo '</div>'; // BodyWrapDiv
echo '</div>'; // BodyDiv

echo '<hr noshade />';

echo '<div id="FooterDiv">';
echo '<div id="FooterWrapDiv">';

echo '<div id="FooterLogoDiv">';
	echo '<img src="'. $RootPath . '/' . $_SESSION['LogoFile'] . '" width="120" alt="webERP" title="webERP ' . _('Copyright') . ' &copy; weberp.org - ' . date('Y') . '" />';
echo '</div>';

echo '<div id="FooterTimeDiv">';
	echo DisplayDateTime();
echo '</div>';

echo '<div id="FooterVersionDiv">';
echo 'WebERP v' . $_SESSION['VersionNumber'] . ' &copy; 2004 - ' . Date('Y'). ' <a target="_blank" href="http://www.weberp.org/weberp/doc/Manual/ManualContributors.html">weberp.org</a>'
    .'<br />'._('Forked') .' <a target="_blank" title="History Log" href="' . $RootPath.'/!history/">v'.SYSTEM_VERSION.'</a> for '.$CompanyList[0]['company']
    ;
echo '</div>';

echo '</div>'; // FooterWrapDiv
echo '</div>'; // FooterDiv
echo '</div>'; // Canvas

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

