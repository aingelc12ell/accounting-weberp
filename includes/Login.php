<?php
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
/* $Id: Login.php 5785 2012-12-29 04:47:42Z daintree $*/

// Display demo user name and password within login form if $AllowDemoMode is true
//include ('LanguageSetup.php');

if ($AllowDemoMode == True and !isset($demo_text)) {
	$demo_text = _('login as user') .': <i>' . _('admin') . '</i><br />' ._('with password') . ': <i>' . _('weberp') . '</i>';
} elseif (!isset($demo_text)) {
	$demo_text = _('Please login here');
}
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
			"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
?>

<html>
<head>
	<title>webERP Login screen</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="css/<?php echo $Theme;?>/login.css" type="text/css" />
</head>
<body>

<?php
if (get_magic_quotes_gpc()){
	echo '<p style="background:white">';
	echo _('Your webserver is configured to enable Magic Quotes. This may cause problems if you use punctuation (such as quotes) when doing data entry. You should contact your webmaster to disable Magic Quotes');
	echo '</p>';
}
?>

<div id="container">
	<div id="login_logo"></div>
	<div id="login_box">
	<form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');?>" method="post">
    <div>
	<input type="hidden" name="FormID" value="<?php echo $_SESSION['FormID']; ?>" />
	<span><?php echo _('Company'); ?>:</span>

	<?php
		if ($AllowCompanySelectionBox == true){
			echo '<select name="CompanyNameField">';

			$Companies = scandir('companies/', 0);
			foreach ($Companies as $CompanyEntry){
				if (is_dir('companies/' . $CompanyEntry) AND $CompanyEntry != '..' AND $CompanyEntry != '' AND $CompanyEntry!='.svn' AND $CompanyEntry!='.'){
					if ($CompanyEntry==$DefaultCompany) {
						echo '<option selected="selected" label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">'.$CompanyEntry.'</option>';
					} else {
						echo '<option label="'.$CompanyEntry.'" value="'.$CompanyEntry.'">'.$CompanyEntry.'</option>';
					}
				}
			}
			echo '</select>';
		} else {
			echo '<input type="text" name="CompanyNameField"  value="' . $DefaultCompany . '" />';
		}
	?>

	<br />
	<span><?php echo _('User name'); ?>:</span><br />
	<input type="text" name="UserNameEntryField" maxlength="20" /><br />
	<span><?php echo _('Password'); ?>:</span><br />
	<input type="password" name="Password" /><br />
	<div id="demo_text"><?php echo $demo_text;?></div>
	<input class="button" type="submit" value="<?php echo _('Login'); ?>" name="SubmitUser" />
	    </div>
	</form>
	</div>
	<br />
	<div style="text-align:center"><a href="https://sourceforge.net/projects/web-erp"><img src="https://sflogo.sourceforge.net/sflogo.php?group_id=70949&amp;type=8" width="80" height="15" alt="Get webERP Accounting &amp; Business Management at SourceForge.net. Fast, secure and Free Open Source software downloads" /></a></div>
</div>
	<script type="text/javascript">
			<!--
				  document.forms[0].UserNameEntryField.focus();
			//-->
	</script>
</body>
</html>
<?php

}