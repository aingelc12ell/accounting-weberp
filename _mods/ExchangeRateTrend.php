<?php
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.php');
/* $Id: ExchangeRateTrend.php 7143 2015-02-09 00:07:40Z rchacon $*/
/* This script shows the trend in exchange rates as retrieved from ECB. */

include(ROOT_DIR.'includes/session.inc');
$Title = _('View Currency Trends');// Screen identification.
$ViewTopic= 'Currencies';// Filename's id in ManualContents.php's TOC.
$BookMark = 'ExchangeRateTrend';// Anchor's id in the manual's html document.
include(ROOT_DIR.'includes/header.inc');

$FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];

if ( isset($_GET['CurrencyToShow']) ){
	$CurrencyToShow = $_GET['CurrencyToShow'];
} elseif ( isset($_POST['CurrencyToShow']) ) {
	$CurrencyToShow = $_POST['CurrencyToShow'];
}

// ************************
// SHOW OUR MAIN INPUT FORM
// ************************

	echo '<form method="post" id="update" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<div class="centre">';
	echo '<p class="page_title_text"><img alt="" src="'.$RootPath.'/css/'.$Theme.
		'/images/currency.png" title="' .// Icon image.
		_('View Currency Trend') . '" /> ' .// Icon title.
		_('View Currency Trend') . '</p>';// Page title.
	echo '</div><table>'; // First column

	$SQL = "SELECT currabrev FROM currencies";
	$result=DB_query($SQL);
	include(ROOT_DIR.'includes/CurrenciesArray.php'); // To get the currency name from the currency code.

	// CurrencyToShow Currency Picker
	echo '<tr>
			<td><select name="CurrencyToShow" onchange="ReloadForm(update.submit)">';

	DB_data_seek($result, 0);
	while ($myrow=DB_fetch_array($result)) {
		if ($myrow['currabrev']!=$_SESSION['CompanyRecord']['currencydefault']){
			echo '<option';
			if ( $CurrencyToShow==$myrow['currabrev'] )	{
				echo ' selected="selected"';
			}
			echo ' value="' . $myrow['currabrev'] . '">' . $CurrencyName[$myrow['currabrev']] . ' (' . $myrow['currabrev'] . ')</option>';
		}
	}
	echo '</select></td>
		</tr>
		</table>
		<br />
		<div class="centre">
			<input type="submit" name="submit" value="' . _('Accept') . '" />
		</div>
	</div>
	</form>';

// **************
// SHOW OUR GRAPH
// **************
	$images = array();
    foreach(array(
        '1M','3M','6M','1Y','5Y','40Y'
    ) as $p){
        $images[] = $p.'<img src="https://www.google.com/finance/getchart?q=' . $FunctionalCurrency . $CurrencyToShow . '&amp;x=CURRENCY&amp;p='.$p.'&amp;i=86400" alt="' ._('Trend Currently Unavailable') . '" />';
    }

	echo '<br />
		<table class="selection">
		<tr>
			<th>
				<div class="centre">
					<b>' . $FunctionalCurrency . ' / ' . $CurrencyToShow . '</b>
				</div>
			</th>
		</tr>
		<tr>
			<td>' . implode('<br>',$images) . '</td>
		</tr>
		</table>';

include(ROOT_DIR.'includes/footer.inc');

