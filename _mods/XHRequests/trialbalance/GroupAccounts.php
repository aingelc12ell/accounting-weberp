<?php
$retvalue = array();
if(isset($_POST['FromPeriod'])
    && isset($_POST['ToPeriod'])
    && isset($_POST['GroupName'])
){

    $SQL = "SELECT accountgroups.groupname,
            accountgroups.parentgroupname,
            accountgroups.pandl,
            chartdetails.accountcode ,
            chartmaster.accountname,
            Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwd ELSE 0 END) AS firstprdbfwd,
            Sum(CASE WHEN chartdetails.period='" . $_POST['FromPeriod'] . "' THEN chartdetails.bfwdbudget ELSE 0 END) AS firstprdbudgetbfwd,
            Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwd + chartdetails.actual ELSE 0 END) AS lastprdcfwd,
            Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.actual ELSE 0 END) AS monthactual,
            Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.budget ELSE 0 END) AS monthbudget,
            Sum(CASE WHEN chartdetails.period='" . $_POST['ToPeriod'] . "' THEN chartdetails.bfwdbudget + chartdetails.budget ELSE 0 END) AS lastprdbudgetcfwd
        FROM chartmaster INNER JOIN accountgroups ON chartmaster.group_ = accountgroups.groupname
            INNER JOIN chartdetails ON chartmaster.accountcode= chartdetails.accountcode
        WHERE accountgroups.groupname = '". $_POST['GroupName']."'
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
    $data = array();
    $totals = array(
        'PeriodProfitLoss' => 0,
        'PeriodBudgetProfitLoss' => 0,
        'MonthProfitLoss' => 0,
        'MonthBudgetProfitLoss' => 0,
        'BFwdProfitLoss' => 0,
        'CheckMonth' => 0,
        'CheckBudgetMonth' => 0,
        'CheckPeriodActual' => 0,
        'CheckPeriodBudget' => 0,
    );
    $AccountPeriodActual = 0;
    $AccountPeriodBudget = 0;
    while ($myrow=DB_fetch_array($AccountsResult)) {
        
        if ($myrow['pandl']==1){
            $AccountPeriodActual = $myrow['lastprdcfwd'] - $myrow['firstprdbfwd'];
            $AccountPeriodBudget = $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
            $totals['PeriodProfitLoss'] += $AccountPeriodActual;
            $totals['PeriodBudgetProfitLoss'] += $AccountPeriodBudget;
            $totals['MonthProfitLoss'] += $myrow['monthactual'];
            $totals['MonthBudgetProfitLoss'] += $myrow['monthbudget'];
            $totals['BFwdProfitLoss'] += $myrow['firstprdbfwd'];
        } 
        else { /*PandL ==0 its a balance sheet account */
            if ($myrow['accountcode']==$RetainedEarningsAct){
                $AccountPeriodActual = $totals['BFwdProfitLoss'] + $myrow['lastprdcfwd'];
                $AccountPeriodBudget = $totals['BFwdProfitLoss'] + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
            } else {
                $AccountPeriodActual = $myrow['lastprdcfwd'];
                $AccountPeriodBudget = $myrow['firstprdbfwd'] + $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
            }
        }
        $totals['CheckMonth'] += $myrow['monthactual'];
        $totals['CheckBudgetMonth'] += $myrow['monthbudget'];
        $totals['CheckPeriodActual'] += $AccountPeriodActual;
        $totals['CheckPeriodBudget'] += $AccountPeriodBudget;
        
        $data[] = array_merge($myrow,array(
            'accounturl' => '<a href="'. $RootPath . '/GLAccountInquiry.php?FromPeriod=' . $_POST['FromPeriod'] . '&amp;ToPeriod=' . $_POST['ToPeriod'] . '&amp;Account=' . $myrow['accountcode'] . '&amp;Show=Yes">' . $myrow['accountcode'] . '</a>',
            'periodactual' => $AccountPeriodActual,
            'periodbudget' => $AccountPeriodBudget
            ));
    }
    //end of while loop
} #ring

echo json_output(array(
    'data' => $data,
    'total' => $totals
    ));