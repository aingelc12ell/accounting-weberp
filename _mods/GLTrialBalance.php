<?php
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.php');
/* $Id: GLTrialBalance.php 7268 2015-04-19 14:57:47Z rchacon $*/
/* Shows the trial balance for the month and the for the period selected together with the budgeted trial balances. */

/*Through deviousness AND cunning, this system allows trial balances for any date range that recalculates the P&L balances
and shows the balance sheets as at the end of the period selected - so first off need to show the input of criteria screen
while the user is selecting the criteria the system is posting any unposted transactions */

$SummaryOnly = isset($_GET['summary']);

include (ROOT_DIR.'includes/session.inc');
$Title = _('Trial Balance');// Screen identification.
$ViewTopic= 'GeneralLedger';// Filename's id in ManualContents.php's TOC.
$BookMark = 'TrialBalance';// Anchor's id in the manual's html document.

include(ROOT_DIR.'includes/SQL_CommonFunctions.inc');
include(ROOT_DIR.'includes/AccountSectionsDef.inc'); //this reads in the Accounts Sections array


if (isset($_POST['FromPeriod'])
	AND isset($_POST['ToPeriod'])
	AND $_POST['FromPeriod'] > $_POST['ToPeriod']){

	prnMsg(_('The selected period from is actually after the period to! Please re-select the reporting period'),'error');
	$_POST['SelectADifferentPeriod']=_('Select A Different Period');
}

if ((! isset($_POST['FromPeriod'])
	AND ! isset($_POST['ToPeriod']))
	OR isset($_POST['SelectADifferentPeriod'])){

	include  (ROOT_DIR.'includes/header.inc');
	echo '<p class="page_title_text"><img alt="" src="'.$RootPath.'/css/'.$Theme.
		'/images/printer.png" title="' .// Icon image.
		_('Print Trial Balance') . '" /> ' .// Icon title.
		_('Trial Balance') . '</p>';// Page title.

	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" id="GLTrialBalanceFrm">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (Date('m') > $_SESSION['YearEnd']){
		/*Dates in SQL format */
		$DefaultFromDate = Date ('Y-m-d', Mktime(0,0,0,$_SESSION['YearEnd'] + 2,0,Date('Y')));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,$_SESSION['YearEnd'] + 2,0,Date('Y')));
	} 
    else {
		$DefaultFromDate = Date ('Y-m-d', Mktime(0,0,0,$_SESSION['YearEnd'] + 2,0,Date('Y')-1));
		$FromDate = Date($_SESSION['DefaultDateFormat'], Mktime(0,0,0,$_SESSION['YearEnd'] + 2,0,Date('Y')-1));
	}
	/*GetPeriod function creates periods if need be the return value is not used */
	$NotUsedPeriodNo = GetPeriod($FromDate, $db);

	/*Show a form to allow input of criteria for TB to show */
	echo '<table class="selection">
			<tr>
				<td>' . _('Select Period From:') . '</td>
				<td><select name="FromPeriod">';
	$NextYear = date('Y-m-d',strtotime('+1 Year'));
	$sql = "SELECT periodno,
					lastdate_in_period
				FROM periods
				WHERE lastdate_in_period < '" . $NextYear . "'
				ORDER BY periodno DESC";
	$Periods = DB_query($sql);


	while ($myrow=DB_fetch_array($Periods,$db)){
		if(isset($_POST['FromPeriod']) AND $_POST['FromPeriod']!=''){
			if( $_POST['FromPeriod']== $myrow['periodno']){
				echo '<option selected="selected" value="' . $myrow['periodno'] . '">' .MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
			} else {
				echo '<option value="' . $myrow['periodno'] . '">' . MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
			}
		} else {
			if($myrow['lastdate_in_period']==$DefaultFromDate){
				echo '<option selected="selected" value="' . $myrow['periodno'] . '">' . MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
			} else {
				echo '<option value="' . $myrow['periodno'] . '">' . MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
			}
		}
	}

	echo '</select></td>
		</tr>';
	if (!isset($_POST['ToPeriod']) OR $_POST['ToPeriod']==''){
		$DefaultToPeriod = GetPeriod(date($_SESSION['DefaultDateFormat'],mktime(0,0,0,Date('m')+1,0,Date('Y'))),$db);
	} 
    else {
		$DefaultToPeriod = $_POST['ToPeriod'];
	}

	echo '<tr>
			<td>' . _('Select Period To:')  . '</td>
			<td><select name="ToPeriod">';

	$RetResult = DB_data_seek($Periods,0);

	while ($myrow=DB_fetch_array($Periods,$db)){

		if($myrow['periodno']==$DefaultToPeriod){
			echo '<option selected="selected" value="' . $myrow['periodno'] . '">' . MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
		} else {
			echo '<option value ="' . $myrow['periodno'] . '">' . MonthAndYearFromSQLDate($myrow['lastdate_in_period']) . '</option>';
		}
	}
	echo '</select></td>
		</tr>
		</table>
		<br />';

	echo '<div class="centre">
			<input type="submit" name="ShowTB" value="' . _('Show Trial Balance') .'" />
            <button id="ShowTBS">' . _('Show Summary') .'</button>
			<input type="submit" name="PrintPDF" value="'._('PrintPDF').'" />
		</div>';

    $JScript .= "
    $('#ShowTBS').unbind().bind('click',function(){
        $('#GLTrialBalanceFrm').attr('action','".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8')."?summary=true').submit();
    });
    ";
/*Now do the posting while the user is thinking about the period to select */

	include (ROOT_DIR.'includes/GLPostings.inc');

} 
else if (isset($_POST['PrintPDF'])) {

	include(ROOT_DIR.'includes/PDFStarter.php');

	$pdf->addInfo('Title', _('Trial Balance') );
	$pdf->addInfo('Subject', _('Trial Balance') );
	$PageNumber = 0;
	$FontSize = 10;
	$line_height = 12;

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	$sql = "SELECT lastdate_in_period
			FROM periods
			WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($sql);
	$myrow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($myrow[0]);

	$RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];

	$SQL = "SELECT accountgroups.groupname,
			accountgroups.parentgroupname,
			accountgroups.pandl,
			chartdetails.accountcode ,
			chartmaster.accountname,
            chartmaster.parentcode,
			Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwd ELSE 0 END) AS firstprdbfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwdbudget ELSE 0 END) AS firstprdbudgetbfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lastprdcfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.actual ELSE 0 END) AS monthactual,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.budget ELSE 0 END) AS monthbudget,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwdbudget + chartdetails.budget ELSE 0 END) AS lastprdbudgetcfwd
		FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname
			INNER JOIN chartdetails ON chartmaster.accountcode= chartdetails.accountcode
		GROUP BY accountgroups.groupname,
				accountgroups.parentgroupname,
				accountgroups.pandl,
				accountgroups.sequenceintb,
				chartdetails.accountcode,
				chartmaster.accountname
		ORDER BY accountgroups.pandl desc,
			accountgroups.sequenceintb,
			accountgroups.groupname,
			chartdetails.accountcode";

	$AccountsResult = DB_query($SQL);
	if (DB_error_no() !=0) {
		$Title = _('Trial Balance') . ' - ' . _('Problem Report') . '....';
		include(ROOT_DIR.'includes/header.inc');
		prnMsg( _('No general ledger accounts were returned by the SQL because') . ' - ' . DB_error_msg() );
		echo '<br /><a href="' .$RootPath .'/index.php">' .  _('Back to the menu'). '</a>';
		if ($debug==1){
			echo '<br />' .  $SQL;
		}
		include(ROOT_DIR.'includes/footer.inc');
		exit;
	}
	if (DB_num_rows($AccountsResult)==0){
		$Title = _('Print Trial Balance Error');
		include(ROOT_DIR.'includes/header.inc');
		echo '<p>';
		prnMsg( _('There were no entries to print out for the selections specified') );
		echo '<br /><a href="'. $RootPath.'/index.php">' .  _('Back to the menu'). '</a>';
		include(ROOT_DIR.'includes/footer.inc');
		exit;
	}

	include(ROOT_DIR.'includes/PDFTrialBalancePageHeader.inc');

	$j = 1;
	$Level = 1;
	$ActGrp = '';
	$ParentGroups = array();
	$ParentGroups[$Level]='';
	$GrpActual =array(0);
	$GrpBudget = array(0);
	$GrpPrdActual = array(0);
	$GrpPrdBudget = array(0);
	$PeriodProfitLoss = 0;
	$PeriodBudgetProfitLoss = 0;
	$MonthProfitLoss = 0;
	$MonthBudgetProfitLoss= 0;
	$BFwdProfitLoss = 0;
    
	$CheckMonth = 0;
	$CheckBudgetMonth = 0;
	$CheckPeriodActual = 0;
	$CheckPeriodBudget = 0;
    
    $CheckMonth_d = 0;
    $CheckBudgetMonth_d = 0;
    $CheckPeriodActual_d = 0;
    $CheckPeriodBudget_d = 0;
    $CheckMonth_c = 0;
    $CheckBudgetMonth_c = 0;
    $CheckPeriodActual_c = 0;
    $CheckPeriodBudget_c = 0;

	while ($myrow=DB_fetch_array($AccountsResult)) {

		if ($myrow['groupname']!= $ActGrp){

			if ($ActGrp !=''){

				// Print heading if at end of page
				if ($YPos < ($Bottom_Margin+ (2 * $line_height))) {
					include(ROOT_DIR.'includes/PDFTrialBalancePageHeader.inc');
				}
				if ($myrow['parentgroupname']==$ActGrp){
					$Level++;
					$ParentGroups[$Level]=$myrow['groupname'];
				}elseif ($myrow['parentgroupname']==$ParentGroups[$Level]){
					$YPos -= (.5 * $line_height);
					$pdf->line($Left_Margin+250, $YPos+$line_height,$Left_Margin+500, $YPos+$line_height);
					$pdf->setFont('','B');
					$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Total'));
					$LeftOvers = $pdf->addTextWrap($Left_Margin+60,$YPos,190,$FontSize,$ParentGroups[$Level]);
					$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
					$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
					$pdf->line($Left_Margin+250, $YPos,$Left_Margin+500, $YPos);  /*Draw the bottom line */
					$YPos -= (2 * $line_height);
					$pdf->setFont('','');
					$ParentGroups[$Level]=$myrow['groupname'];
					$GrpActual[$Level] =0;
					$GrpBudget[$Level] =0;
					$GrpPrdActual[$Level] =0;
					$GrpPrdBduget[$Level] =0;

				} else {
					do {
						$YPos -= $line_height;
						$pdf->line($Left_Margin+250, $YPos+$line_height,$Left_Margin+500, $YPos+$line_height);
						$pdf->setFont('','B');
						$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Total'));
						$LeftOvers = $pdf->addTextWrap($Left_Margin+60,$YPos,190,$FontSize,$ParentGroups[$Level]);
						$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$pdf->line($Left_Margin+250, $YPos,$Left_Margin+500, $YPos);  /*Draw the bottom line */
						$YPos -= (2 * $line_height);
						$pdf->setFont('','');
						$ParentGroups[$Level]='';
						$GrpActual[$Level] =0;
						$GrpBudget[$Level] =0;
						$GrpPrdActual[$Level] =0;
						$GrpPrdBduget[$Level] =0;
						$Level--;
					} while ($Level>0 AND $myrow['parentgroupname']!=$ParentGroups[$Level]);

					if ($Level>0){
						$YPos -= $line_height;
						$pdf->line($Left_Margin+250, $YPos+$line_height,$Left_Margin+500, $YPos+$line_height);
						$pdf->setFont('','B');
						$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Total'));
						$LeftOvers = $pdf->addTextWrap($Left_Margin+60, $YPos, 190, $FontSize, $ParentGroups[$Level]);
						$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
						$pdf->line($Left_Margin+250, $YPos,$Left_Margin+500, $YPos);  /*Draw the bottom line */
						$YPos -= (2 * $line_height);
						$pdf->setFont('','');
						$GrpActual[$Level] =0;
						$GrpBudget[$Level] =0;
						$GrpPrdActual[$Level] =0;
						$GrpPrdBduget[$Level] =0;
					} else {
						$Level =1;
					}
				}
			}
			$YPos -= (2 * $line_height);
				// Print account group name
			$pdf->setFont('','B');
			$ActGrp = $myrow['groupname'];
			$ParentGroups[$Level]=$myrow['groupname'];
			$FontSize = 10;
			$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,200,$FontSize,$myrow['groupname']);
			$FontSize = 8;
			$pdf->setFont('','');
			$YPos -= (2 * $line_height);
		}

		if ($myrow['pandl']==1){

			$AccountPeriodActual = $myrow['lastprdcfwd'] - $myrow['firstprdbfwd'];
			$AccountPeriodBudget = $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];

			$PeriodProfitLoss += $AccountPeriodActual;
			$PeriodBudgetProfitLoss += $AccountPeriodBudget;
			$MonthProfitLoss += $myrow['monthactual'];
			$MonthBudgetProfitLoss += $myrow['monthbudget'];
			$BFwdProfitLoss += $myrow['firstprdbfwd'];
		} else { /*PandL ==0 its a balance sheet account */
			if ($myrow['accountcode']==$RetainedEarningsAct){
				$AccountPeriodActual = $BFwdProfitLoss + $myrow['lastprdcfwd'];
				$AccountPeriodBudget = $BFwdProfitLoss + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
			} else {
				$AccountPeriodActual = $myrow['lastprdcfwd'];
				$AccountPeriodBudget = $myrow['firstprdbfwd'] + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
			}

		}
		for ($i=0;$i<=$Level;$i++){
			if (!isset($GrpActual[$i])) {
				$GrpActual[$i]=0;
			}
			$GrpActual[$i] +=$myrow['monthactual'];
			if (!isset($GrpBudget[$i])) {
				$GrpBudget[$i]=0;
			}
			$GrpBudget[$i] +=$myrow['monthbudget'];
			if (!isset($GrpPrdActual[$i])) {
				$GrpPrdActual[$i]=0;
			}
			$GrpPrdActual[$i] +=$AccountPeriodActual;
			if (!isset($GrpPrdBudget[$i])) {
				$GrpPrdBudget[$i]=0;
			}
			$GrpPrdBudget[$i] +=$AccountPeriodBudget;
		}

		$CheckMonth += $myrow['monthactual'];
		$CheckBudgetMonth += $myrow['monthbudget'];
		$CheckPeriodActual += $AccountPeriodActual;
		$CheckPeriodBudget += $AccountPeriodBudget;

		// Print heading if at end of page
		if ($YPos < ($Bottom_Margin)){
			include(ROOT_DIR.'includes/PDFTrialBalancePageHeader.inc');
		}

		// Print total for each account
		$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,$myrow['accountcode']);
		$LeftOvers = $pdf->addTextWrap($Left_Margin+60,$YPos,190,$FontSize,$myrow['accountname']);
		$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($myrow['monthactual'],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($myrow['monthbudget'],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($AccountPeriodActual,$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($AccountPeriodBudget,$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$YPos -= $line_height;

	}  //end of while loop


	while ($Level>0 AND $myrow['parentgroupname']!=$ParentGroups[$Level]) {

		$YPos -= (.5 * $line_height);
		$pdf->line($Left_Margin+250, $YPos+$line_height,$Left_Margin+500, $YPos+$line_height);
		$pdf->setFont('','B');
		$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Total'));
		$LeftOvers = $pdf->addTextWrap($Left_Margin+60,$YPos,190,$FontSize,$ParentGroups[$Level]);
		$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),'right');
		$pdf->line($Left_Margin+250, $YPos,$Left_Margin+500, $YPos);  /*Draw the bottom line */
		$YPos -= (2 * $line_height);
		$ParentGroups[$Level]='';
		$GrpActual[$Level] =0;
		$GrpBudget[$Level] =0;
		$GrpPrdActual[$Level] =0;
		$GrpPrdBduget[$Level] =0;
		$Level--;
	}


	$YPos -= (2 * $line_height);
	$pdf->line($Left_Margin+250, $YPos+$line_height,$Left_Margin+500, $YPos+$line_height);
	$LeftOvers = $pdf->addTextWrap($Left_Margin,$YPos,60,$FontSize,_('Check Totals'));
	$LeftOvers = $pdf->addTextWrap($Left_Margin+250,$YPos,70,$FontSize,locale_number_format($CheckMonth,$_SESSION['CompanyRecord']['decimalplaces']),'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+310,$YPos,70,$FontSize,locale_number_format($CheckBudgetMonth,$_SESSION['CompanyRecord']['decimalplaces']),'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+370,$YPos,70,$FontSize,locale_number_format($CheckPeriodActual,$_SESSION['CompanyRecord']['decimalplaces']),'right');
	$LeftOvers = $pdf->addTextWrap($Left_Margin+430,$YPos,70,$FontSize,locale_number_format($CheckPeriodBudget,$_SESSION['CompanyRecord']['decimalplaces']),'right');
	$pdf->line($Left_Margin+250, $YPos,$Left_Margin+500, $YPos);

	$pdf->OutputD($_SESSION['DatabaseName'] . '_GL_Trial_Balance_' . Date('Y-m-d') . '.pdf');
	$pdf->__destruct();
	exit;
} 
else {

	include(ROOT_DIR.'includes/header.inc');
	echo '<form method="post" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">
		<div>
			<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />
			<input type="hidden" name="FromPeriod" value="' . $_POST['FromPeriod'] . '" />
			<input type="hidden" name="ToPeriod" value="' . $_POST['ToPeriod'] . '" />';

	$NumberOfMonths = $_POST['ToPeriod'] - $_POST['FromPeriod'] + 1;

	$sql = "SELECT lastdate_in_period
			FROM periods
			WHERE periodno='" . $_POST['ToPeriod'] . "'";
	$PrdResult = DB_query($sql);
	$myrow = DB_fetch_row($PrdResult);
	$PeriodToDate = MonthAndYearFromSQLDate($myrow[0]);

	$RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];

	$SQL = "SELECT accountgroups.groupname,
			accountgroups.parentgroupname,
			accountgroups.pandl,
			chartdetails.accountcode ,
			chartmaster.accountname,
            chartmaster.parentcode,
			Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwd ELSE 0 END) AS firstprdbfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwdbudget ELSE 0 END) AS firstprdbudgetbfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lastprdcfwd,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.actual ELSE 0 END) AS monthactual,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.budget ELSE 0 END) AS monthbudget,
			Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwdbudget + chartdetails.budget ELSE 0 END) AS lastprdbudgetcfwd
		FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname
			INNER JOIN chartdetails ON chartmaster.accountcode= chartdetails.accountcode
		GROUP BY accountgroups.groupname,
				accountgroups.pandl,
				accountgroups.sequenceintb,
				accountgroups.parentgroupname,
				chartdetails.accountcode,
				chartmaster.accountname
		ORDER BY accountgroups.pandl desc,
			accountgroups.sequenceintb,
			accountgroups.groupname,
			chartdetails.accountcode";


	$AccountsResult = DB_query($SQL, _('No general ledger accounts were returned by the SQL because'), _('The SQL that failed was:'));

	echo '<p class="page_title_text"><img alt="" src="'.$RootPath.'/css/'.$Theme.
		'/images/gl.png" title="' .// Icon image.
		_('Trial Balance') . '" /> ' .// Icon title.
		_('Trial Balance for the month of ') . $PeriodToDate . '<br />' .
		_(' AND for the ') . $NumberOfMonths . ' ' . _('months to') . ' ' . $PeriodToDate;// Page title.

	/*show a table of the accounts info returned by the SQL
	Account Code, Account Name, Month Actual, Month Budget, Period Actual, Period Budget */

	echo '<table cellpadding="2" class="selection">';

	$TableHeader = '<tr>'
						.($SummaryOnly
                            ? '<th rowspan="2" colspan="2">' . _('Account Group') . '</th>'
                            : '<th rowspan="2">' . _('Account') . '</th>'
                                .'<th rowspan="2">' . _('Account Name') . '</th>'
                        )
                        .'<th colspan="2">' . _('Month Actual') . '</th>
						<th colspan="2">' . _('Month Budget') . '</th>
						<th colspan="2">' . _('Period Actual') . '</th>
						<th colspan="2">' . _('Period Budget')  . '</th>
					</tr>'
                    .'<tr>
                        <th>' . _('Debit') . '</th>
                        <th>' . _('Credit') . '</th>
                        <th>' . _('Debit') . '</th>
                        <th>' . _('Credit') . '</th>
                        <th>' . _('Debit') . '</th>
                        <th>' . _('Credit') . '</th>
                        <th>' . _('Debit') . '</th>
                        <th>' . _('Credit') . '</th>
                    </tr>';
    if($SummaryOnly){
        echo $TableHeader;
    }
	$j = 1;
	$k=0; //row colour counter
	$ActGrp ='';
	$ParentGroups = array();
	$Level =1; //level of nested sub-groups
	$ParentGroups[$Level]='';
	$GrpActual =array(0);
	$GrpBudget =array(0);
	$GrpPrdActual =array(0);
	$GrpPrdBudget =array(0);

	$PeriodProfitLoss = 0;
	$PeriodBudgetProfitLoss = 0;
	$MonthProfitLoss = 0;
	$MonthBudgetProfitLoss = 0;
	$BFwdProfitLoss = 0;
	$CheckMonth = 0;
	$CheckBudgetMonth = 0;
	$CheckPeriodActual = 0;
	$CheckPeriodBudget = 0;
    
    $tmad = 0;
    $tmac = 0;
    $tmbd = 0;
    $tmbc = 0;
    $tpad = 0;
    $tpac = 0;
    $tpbd = 0;
    $tpbc = 0;

	while ($myrow=DB_fetch_array($AccountsResult)) {

		if ($myrow['groupname']!= $ActGrp ){
			if ($ActGrp !=''){ //so its not the first account group of the first account displayed
				if ($myrow['parentgroupname']==$ActGrp){
					$Level++;
					$ParentGroups[$Level]=$myrow['groupname'];
					$GrpActual[$Level] =0;
					$GrpBudget[$Level] =0;
					$GrpPrdActual[$Level] =0;
					$GrpPrdBudget[$Level] =0;
					$ParentGroups[$Level]='';
				} 
                elseif ($ParentGroups[$Level]==$myrow['parentgroupname']) {
                    $mactuald = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
                    $mactualc = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
                    
                    $mbudgetd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
                    $mbudgetc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
                    
                    $pactuald = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
                    $pactualc = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
                    
                    $pbudgetd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
                    $pbudgetc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
                    printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                        .($SummaryOnly
                            ? '<tdcolspan="2">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
                        $ParentGroups[$Level],
                        locale_number_format($mactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($mactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                        
                        locale_number_format($mbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($mbudgetc,$_SESSION['CompanyRecord']['decimalplaces']),
                        
                        locale_number_format($pactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                        
                        locale_number_format($pbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pbudgetc,$_SESSION['CompanyRecord']['decimalplaces'])
                        );
                        
					/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
						$ParentGroups[$Level],
                        locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/

					$GrpActual[$Level] =0;
					$GrpBudget[$Level] =0;
					$GrpPrdActual[$Level] =0;
					$GrpPrdBudget[$Level] =0;
					$ParentGroups[$Level]=$myrow['groupname'];
				} 
                else {
					do {
						/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
							$ParentGroups[$Level],
							locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
							locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
							locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
							locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/
                        $mactuald = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
                        $mactualc = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
                        
                        $mbudgetd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
                        $mbudgetc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
                        
                        $pactuald = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
                        $pactualc = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
                        
                        $pbudgetd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
                        $pbudgetc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
                        printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                            .($SummaryOnly
                                ? '<td colspan="2">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>'
                                : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>'
                            )
                            .'</tr>',
                            $ParentGroups[$Level],
                            locale_number_format($mactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($mactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($mbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($mbudgetc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($pactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($pactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($pbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($pbudgetc,$_SESSION['CompanyRecord']['decimalplaces'])
                            );
						$GrpActual[$Level] =0;
						$GrpBudget[$Level] =0;
						$GrpPrdActual[$Level] =0;
						$GrpPrdBudget[$Level] =0;
						$ParentGroups[$Level]='';
						$Level--;

						$j++;
					} while ($Level>0 AND $myrow['groupname']!=$ParentGroups[$Level]);

					if ($Level>0){
						/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
						$ParentGroups[$Level],
						locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/
                        $mactuald = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
                        $mactualc = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
                        
                        $mbudgetd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
                        $mbudgetc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
                        
                        $pactuald = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
                        $pactualc = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
                        
                        $pbudgetd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
                        $pbudgetc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
                        printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                            .($SummaryOnly
                                ? '<td colspan="2">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>'
                                : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>'
                            )
                            .'</tr>',
                            $ParentGroups[$Level],
                            locale_number_format($mactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($mactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($mbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($mbudgetc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($pactuald,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($pactualc,$_SESSION['CompanyRecord']['decimalplaces']),
                            
                            locale_number_format($pbudgetd,$_SESSION['CompanyRecord']['decimalplaces']),
                            locale_number_format($pbudgetc,$_SESSION['CompanyRecord']['decimalplaces'])
                            );
						$GrpActual[$Level] =0;
						$GrpBudget[$Level] =0;
						$GrpPrdActual[$Level] =0;
						$GrpPrdBudget[$Level] =0;
						$ParentGroups[$Level]='';
					} else {
						$Level=1;
					}
				}
			}
			$ParentGroups[$Level]=$myrow['groupname'];
			$ActGrp = $myrow['groupname'];
            if(!$SummaryOnly){
			    printf('<tr>
						    <td colspan="6"><h2>%s</h2></td>
					    </tr>',
					    $myrow['groupname']);
			    echo $TableHeader;
            }
			$j++;
		}

		if ($k==1){
			echo $SummaryOnly ? '' : '<tr class="EvenTableRows">';
			$k=0;
		} 
        else {
			echo $SummaryOnly ? '' : '<tr class="OddTableRows">';
			$k++;
		}
		/*MonthActual, MonthBudget, FirstPrdBFwd, FirstPrdBudgetBFwd, LastPrdBudgetCFwd, LastPrdCFwd */


		if ($myrow['pandl']==1){

			$AccountPeriodActual = $myrow['lastprdcfwd'] - $myrow['firstprdbfwd'];
			$AccountPeriodBudget = $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];

			$PeriodProfitLoss += $AccountPeriodActual;
			$PeriodBudgetProfitLoss += $AccountPeriodBudget;
			$MonthProfitLoss += $myrow['monthactual'];
			$MonthBudgetProfitLoss += $myrow['monthbudget'];
			$BFwdProfitLoss += $myrow['firstprdbfwd'];
		} 
        else { 
            /*PandL ==0 its a balance sheet account */
			if ($myrow['accountcode']==$RetainedEarningsAct){
				$AccountPeriodActual = $BFwdProfitLoss + $myrow['lastprdcfwd'];
				$AccountPeriodBudget = $BFwdProfitLoss + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
			} else {
				$AccountPeriodActual = $myrow['lastprdcfwd'];
				$AccountPeriodBudget = $myrow['firstprdbfwd'] + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
			}

		}

		if (!isset($GrpActual[$Level])) {
			$GrpActual[$Level]=0;
		}
		if (!isset($GrpBudget[$Level])) {
			$GrpBudget[$Level]=0;
		}
		if (!isset($GrpPrdActual[$Level])) {
			$GrpPrdActual[$Level]=0;
		}
		if (!isset($GrpPrdBudget[$Level])) {
			$GrpPrdBudget[$Level]=0;
		}
		$GrpActual[$Level] +=$myrow['monthactual'];
		$GrpBudget[$Level] +=$myrow['monthbudget'];
		$GrpPrdActual[$Level] +=$AccountPeriodActual;
		$GrpPrdBudget[$Level] +=$AccountPeriodBudget;

		$CheckMonth += $myrow['monthactual'];
		$CheckBudgetMonth += $myrow['monthbudget'];
		$CheckPeriodActual += $AccountPeriodActual;
		$CheckPeriodBudget += $AccountPeriodBudget;
        
        $CheckMonth_d += $myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0;
        $CheckBudgetMonth_d += $myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0;
        $CheckPeriodActual_d += $AccountPeriodActual > 0 ? $AccountPeriodActual : 0;
        $CheckPeriodBudget_d += $AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0;
        
        $CheckMonth_c += $myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0;
        $CheckBudgetMonth_c += $myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0;
        $CheckPeriodActual_c += $AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0;
        $CheckPeriodBudget_c += $AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0;

		$ActEnquiryURL = '<a href="'. $RootPath . '/GLAccountInquiry.php?FromPeriod=' . $_POST['FromPeriod'] . '&amp;ToPeriod=' . $_POST['ToPeriod'] . '&amp;Account=' . $myrow['accountcode'] . '&amp;Show=Yes">' . $myrow['accountcode'] . '</a>';
        if(!$SummaryOnly){
		    /*printf('<td>%s</td>
				    <td>%s</td>
				    <td class="number">%s</td>
				    <td class="number">%s</td>
				    <td class="number">%s</td>
				    <td class="number">%s</td>
				    </tr>',
				    $ActEnquiryURL,
				    htmlspecialchars($myrow['accountname'], ENT_QUOTES,'UTF-8', false),
				    locale_number_format($myrow['monthactual'],$_SESSION['CompanyRecord']['decimalplaces']),
				    locale_number_format($myrow['monthbudget'],$_SESSION['CompanyRecord']['decimalplaces']),
				    locale_number_format($AccountPeriodActual,$_SESSION['CompanyRecord']['decimalplaces']),
				    locale_number_format($AccountPeriodBudget,$_SESSION['CompanyRecord']['decimalplaces']));*/
            $mad = $myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0;
            $mac = $myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0;
            
            $mbd = $myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0;
            $mbc = $myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0;
            
            $pad = $AccountPeriodActual > 0 ? $AccountPeriodActual : 0;
            $pac = $AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0;
            
            $pbd = $AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0;
            $pbc = $AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0;
            printf('<td>%s</td>
                    <td>%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    <td class="number">%s</td>
                    </tr>',
                    $ActEnquiryURL,
                    htmlspecialchars($myrow['accountname'], ENT_QUOTES,'UTF-8', false),
                    locale_number_format($mad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mac,$_SESSION['CompanyRecord']['decimalplaces']),
                    
                    locale_number_format($mbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mbc,$_SESSION['CompanyRecord']['decimalplaces']),
                    
                    locale_number_format($pad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pac,$_SESSION['CompanyRecord']['decimalplaces']),
                    
                    locale_number_format($pbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pbc,$_SESSION['CompanyRecord']['decimalplaces'])
                    );
            $tmad += $myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0;
            $tmac += $myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0;
            
            $tmbd += $myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0;
            $tmbc += $myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0;
            
            $tpad += $AccountPeriodActual > 0 ? $AccountPeriodActual : 0;
            $tpac += $AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0;
            
            $tpbd += $AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0;
            $tpbc += $AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0;
            
        }
		$j++;
	}
	//end of while loop


	if ($ActGrp !=''){ //so its not the first account group of the first account displayed
		if ($myrow['parentgroupname']==$ActGrp){
			$Level++;
			$ParentGroups[$Level]=$myrow['groupname'];
		} 
        elseif ($ParentGroups[$Level]==$myrow['parentgroupname']) {
			/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
					$ParentGroups[$Level],
					locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
					locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
					locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
					locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/
            $mad = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
            $mac = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
            $mbd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
            $mbc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
            $pad = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
            $pac = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
            $pbd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
            $pbc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
            printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                        .($SummaryOnly
                            ? '<td colspan="2">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
                    $ParentGroups[$Level],
                    locale_number_format($mad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mac,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mbc,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pac,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pbc,$_SESSION['CompanyRecord']['decimalplaces'])
                    );

			$GrpActual[$Level] =0;
			$GrpBudget[$Level] =0;
			$GrpPrdActual[$Level] =0;
			$GrpPrdBudget[$Level] =0;
			$ParentGroups[$Level]=$myrow['groupname'];
		} 
        else {
			do {
				/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
						$ParentGroups[$Level],
						locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/
                $mad = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
            $mac = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
            $mbd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
            $mbc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
            $pad = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
            $pac = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
            $pbd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
            $pbc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
            printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                        .($SummaryOnly
                            ? '<td colspan="2">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
                    $ParentGroups[$Level],
                    locale_number_format($mad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mac,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($mbc,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pad,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pac,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pbd,$_SESSION['CompanyRecord']['decimalplaces']),
                    locale_number_format($pbc,$_SESSION['CompanyRecord']['decimalplaces'])
                    );

				$GrpActual[$Level] =0;
				$GrpBudget[$Level] =0;
				$GrpPrdActual[$Level] =0;
				$GrpPrdBudget[$Level] =0;
				$ParentGroups[$Level]='';
				$Level--;

				$j++;
			} while (isset($ParentGroups[$Level]) AND ($myrow['groupname']!=$ParentGroups[$Level] AND $Level>0));

			if ($Level >0){
				/*printf('<tr>'
                        .($SummaryOnly
                            ? '<td>%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>
                                <td class="number">%s</td>'
                            : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>
                                <td class="number"><i>%s</i></td>'
                        )
                        .'</tr>',
						$ParentGroups[$Level],
						locale_number_format($GrpActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdActual[$Level],$_SESSION['CompanyRecord']['decimalplaces']),
						locale_number_format($GrpPrdBudget[$Level],$_SESSION['CompanyRecord']['decimalplaces']));*/
                $mad = $GrpActual[$Level] > 0 ? $GrpActual[$Level] : 0;
                $mac = $GrpActual[$Level] < 0 ? abs($GrpActual[$Level]) : 0;
                
                $mbd = $GrpBudget[$Level] > 0 ? $GrpBudget[$Level] : 0;
                $mbc = $GrpBudget[$Level] < 0 ? abs($GrpBudget[$Level]) : 0;
                
                $pad = $GrpPrdActual[$Level] > 0 ? $GrpPrdActual[$Level] : 0;
                $pac = $GrpPrdActual[$Level] < 0 ? abs($GrpPrdActual[$Level]) : 0;
                
                $pbd = $GrpPrdBudget[$Level] > 0 ? $GrpPrdBudget[$Level] : 0;
                $pbc = $GrpPrdBudget[$Level] < 0 ? abs($GrpPrdBudget[$Level]) : 0;
                printf('<tr class="accountgrp" data-group="'.$ParentGroups[$Level].'">'
                            .($SummaryOnly
                                ? '<td colspan="2">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>
                                    <td class="number">%s</td>'
                                : '<td colspan="2"><i>%s ' . _('Total') . ' </i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>
                                    <td class="number"><i>%s</i></td>'
                            )
                            .'</tr>',
                        $ParentGroups[$Level],
                        locale_number_format($mad,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($mac,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($mbd,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($mbc,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pad,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pac,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pbd,$_SESSION['CompanyRecord']['decimalplaces']),
                        locale_number_format($pbc,$_SESSION['CompanyRecord']['decimalplaces'])
                        );

				$GrpActual[$Level] =0;
				$GrpBudget[$Level] =0;
				$GrpPrdActual[$Level] =0;
				$GrpPrdBudget[$Level] =0;
				$ParentGroups[$Level]='';
			} 
            else {
				$Level =1;
			}
		}
	}



	/*printf('<tr style="background-color:#ffffff">
				<td '.($SummaryOnly ? '' : ' colspan="2"').'><b>' . _('Check Totals') . '</b></td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
				<td class="number">%s</td>
			</tr>',
			locale_number_format($CheckMonth,$_SESSION['CompanyRecord']['decimalplaces']),
			locale_number_format($CheckBudgetMonth,$_SESSION['CompanyRecord']['decimalplaces']),
			locale_number_format($CheckPeriodActual,$_SESSION['CompanyRecord']['decimalplaces']),
			locale_number_format($CheckPeriodBudget,$_SESSION['CompanyRecord']['decimalplaces']));*/
    $mtd = $CheckMonth > 0 ? $CheckMonth : 0;
    $mtc = $CheckMonth < 0 ? abs($CheckMonth) : 0;
    
    $mbd = $CheckBudgetMonth > 0 ? $CheckBudgetMonth : 0;
    $mbc = $CheckBudgetMonth < 0 ? abs($CheckBudgetMonth) : 0;
    
    $pad = $CheckPeriodActual > 0 ? $CheckPeriodActual : 0;
    $pac = $CheckPeriodActual < 0 ? abs($CheckPeriodActual) : 0;
    
    $pbd = $CheckPeriodBudget > 0 ? $CheckPeriodBudget : 0;
    $pbc = $CheckPeriodBudget < 0 ? abs($CheckPeriodBudget) : 0;
    printf('<tr style="background-color:#ffffff" id="checktotal">
                <td colspan="2" rowspan="2"><b>' . _('Check Totals') . '</b></td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
            </tr>',
            locale_number_format($CheckMonth_d,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($CheckMonth_c,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($CheckBudgetMonth_d,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($CheckBudgetMonth_c,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($CheckPeriodActual_d,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($CheckPeriodActual_c,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($CheckPeriodBudget_d,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($CheckPeriodBudget_c,$_SESSION['CompanyRecord']['decimalplaces'])
            );
            
    printf('<tr style="background-color:#ffffff" id="checktotal">
                <!-- <td colspan="2"><b>' . _('Check Totals') . '</b></td> -->
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
                <td class="number">%s</td>
            </tr>',
            locale_number_format($mtd,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($mtc,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($mbd,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($mbc,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($pad,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($pac,$_SESSION['CompanyRecord']['decimalplaces']),
            
            locale_number_format($pbd,$_SESSION['CompanyRecord']['decimalplaces']),
            locale_number_format($pbc,$_SESSION['CompanyRecord']['decimalplaces'])
            );

	echo '</table><br />';

	echo '<div class="centre noprint">'.
			'<button onclick="javascript:window.print()" type="button"><img alt="" src="'.$RootPath.'/css/'.$Theme.
				'/images/printer.png" /> ' . _('Print This') . '</button>'.// "Print This" button.
			'<button name="SelectADifferentPeriod" type="submit" value="'. _('Select A Different Period') .'"><img alt="" src="'.$RootPath.'/css/'.$Theme.
				'/images/gl.png" /> ' . _('Select A Different Period') . '</button>'.// "Select A Different Period" button.
			'<button formaction="index.php" type="submit"><img alt="" src="'.$RootPath.'/css/'.$Theme.
				'/images/previous.png" /> ' . _('Return') . '</button>'.// "Return" button.
		'</div>';
    $JSFunctions .= "
    var displayAccount = function(v,p){
        var pk = Object.keys(p)
            ,ma = parseFloat(v.monthactual)
            ,mac = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].mac) : (ma > 0 ? ma : 0)
            ,mad = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].mad) : (ma < 0 ? Math.abs(ma) : 0)
            ,mb = parseFloat(v.monthbudget)
            ,mbd = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].mbd) : (mb > 0 ? mb : 0)
            ,mbc = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].mbc) : (mb < 0 ? Math.abs(mb) : 0)
            ,pa = parseFloat(v.periodactual)
            ,pad = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].pad) : (pa > 0 ? pa : 0)
            ,pac = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].pac) : (pa < 0 ? Math.abs(pa) : 0)
            ,pb = parseFloat(v.periodbudget)
            ,pbd = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].pbd) : (pb > 0 ? pb : 0)
            ,pbc = typeof(p[v.accountcode])=='object' ? parseFloat(p[v.accountcode].pbc) : (pb < 0 ? Math.abs(pb) : 0)
            ,h = '<tr' 
                + (v.parentcode != '' ? ' class=\"hidden par' + v.parentcode + '\"' 
                    : (typeof(p[v.accountcode])=='object'
                        ? ' class=\"bold pointer parcode\" data-account=\"' + v.accountcode + '\"' 
                        : '')
                    )
                + '>'
                + '<td>'
                    + (typeof(p[v.accountcode])=='object'
                        ? v.accountcode
                        : '<a href=\"". $RootPath . "/GLAccountInquiry.php'
                    + '?FromPeriod=" . $_POST['FromPeriod'] 
                        . "&amp;ToPeriod=" . $_POST['ToPeriod'] 
                        . "&amp;Account=' +  v.accountcode + '&amp;Show=Yes\">' 
                    + v.accountcode + '</a>'
                    )
                + '</td>'
                + '<td>' + v.accountname + '</td>'
                + '<td class=\"number\">' + (mad > 0 ? number_format(mad,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (mac > 0 ? number_format(mac,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (mbd > 0 ? number_format(mbd,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (mbc > 0 ? number_format(mbc,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (pad > 0 ? number_format(pad,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (pac > 0 ? number_format(pac,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (pbd > 0 ? number_format(pbd,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '<td class=\"number\">' + (pbc > 0 ? number_format(pbc,".$_SESSION['CompanyRecord']['decimalplaces'].") : '') + '</td>'
                + '</tr>'
            ;
        
        return h;
    };
    var toggleRow = function(pc){
        $.each(pc,function(){
            if($(this).hasClass('hidden')){
                $(this).removeClass('hidden').show();
            }else{
                $(this).addClass('hidden').hide();
            }
        });
    };
    var manageList = function(){
        $('tr.parcode').unbind().bind('click',function(){
            var accode = $(this).attr('data-account');
            toggleRow($('tr.par' + accode));
        });
    };
    var displayTotals = function(v){
        return '<tr class=\"bold\">'
            + '<td colspan=\"2\">Sub Totals</td>'
            + '<td class=\"number\">' 
                + (v.CheckMonth_d != 0 ? number_format(v.CheckMonth_d,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckMonth_c != 0 ? number_format(v.CheckMonth_c,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckBudgetMonth_d != 0 ? number_format(v.CheckBudgetMonth_d,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckBudgetMonth_c != 0 ? number_format(v.CheckBudgetMonth_c,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckPeriodActual_d != 0 ? number_format(v.CheckPeriodActual_d,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckPeriodActual_c != 0 ? number_format(v.CheckPeriodActual_c,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckPeriodBudget_d != 0 ? number_format(v.CheckPeriodBudget_d,".$_SESSION['CompanyRecord']['decimalplaces'].") : '-') + '</td>'
            + '<td class=\"number\">' 
                + (v.CheckPeriodBudget_c != 0 ? number_format(v.CheckPeriodBudget_c,".$_SESSION['CompanyRecord']['decimalplaces'].") :  '-') + '</td>'
            + '</tr>'
            ;
    };
    var getGroupAccounts = function(grp,tr){
        $.post(
            '".$RootPath."XHRequests/trialbalance/GroupAccounts.php'
            ,{
                FromPeriod : '".$_POST['FromPeriod']."',
                ToPeriod : '".$_POST['ToPeriod']."',
                GroupName : grp
            },function(d){
                if(typeof(d.data)=='object' && typeof(d.total)=='object'){
                    var h = '';
                    $.each(d.data,function(i,v){
                        h += displayAccount(v,d.parentcodes);
                    });
                    h += displayTotals(d.total);
                    tr.after(h);
                    manageList();
                }
            },'json'
        );
    };
    ";
    $JScript .= $SummaryOnly ? "
    $('tr#checktotal').addClass('bold OddTableRows');
    $('tr.accountgrp').addClass('bold OddTableRows').not('[displayed=\"true\"]').addClass('pointer').unbind().bind('click',function(){
        var tr = $(this)
            ,grp = tr.attr('data-group');
        tr.attr('displayed','true').removeClass('pointer');
        getGroupAccounts(grp,tr);
    });
    " : "";
}
echo '</div>
	</form>';
include(ROOT_DIR.'includes/footer.inc');


