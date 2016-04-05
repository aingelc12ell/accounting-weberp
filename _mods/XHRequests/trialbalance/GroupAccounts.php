<?php
require(dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR.'header.inc');

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
            chartmaster.parentcode,
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
        
        'PeriodProfitLoss_d' => 0,
        'PeriodBudgetProfitLoss_d' => 0,
        'MonthProfitLoss_d' => 0,
        'MonthBudgetProfitLoss_d' => 0,
        'BFwdProfitLoss_d' => 0,
        'CheckMonth_d' => 0,
        'CheckBudgetMonth_d' => 0,
        'CheckPeriodActual_d' => 0,
        'CheckPeriodBudget_d' => 0,
        
        'PeriodProfitLoss_c' => 0,
        'PeriodBudgetProfitLoss_c' => 0,
        'MonthProfitLoss_c' => 0,
        'MonthBudgetProfitLoss_c' => 0,
        'BFwdProfitLoss_c' => 0,
        'CheckMonth_c' => 0,
        'CheckBudgetMonth_c' => 0,
        'CheckPeriodActual_c' => 0,
        'CheckPeriodBudget_c' => 0,
    );
    $AccountPeriodActual = 0;
    $AccountPeriodBudget = 0;
    $RetainedEarningsAct = $_SESSION['CompanyRecord']['retainedearnings'];
    $parents = array(); #code => $sumazation
    while ($myrow=DB_fetch_assoc($AccountsResult)) {
        
        if ($myrow['pandl']==1){
            $AccountPeriodActual = $myrow['lastprdcfwd'] - $myrow['firstprdbfwd'];
            $AccountPeriodBudget = $myrow['lastprdbudgetcfwd'] - $myrow['firstprdbudgetbfwd'];
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
        $totals['PeriodProfitLoss'] += $AccountPeriodActual;
        $totals['PeriodBudgetProfitLoss'] += $AccountPeriodBudget;
        $totals['MonthProfitLoss'] += $myrow['monthactual'];
        $totals['MonthBudgetProfitLoss'] += $myrow['monthbudget'];
        $totals['BFwdProfitLoss'] += $myrow['firstprdbfwd'];
        
        $totals['CheckMonth'] += $myrow['monthactual'];
        $totals['CheckBudgetMonth'] += $myrow['monthbudget'];
        $totals['CheckPeriodActual'] += $AccountPeriodActual;
        $totals['CheckPeriodBudget'] += $AccountPeriodBudget;
        
        $totals['PeriodProfitLoss_d'] += $AccountPeriodActual > 0 ? $AccountPeriodActual : 0;
        $totals['PeriodBudgetProfitLoss_d'] += $AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0;
        $totals['MonthProfitLoss_d'] += $myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0;
        $totals['MonthBudgetProfitLoss_d'] += $myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0;
        $totals['BFwdProfitLoss_d'] += $myrow['firstprdbfwd'] > 0 ? $myrow['firstprdbfwd'] : 0;
        $totals['CheckMonth_d'] += $myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0;
        $totals['CheckBudgetMonth_d'] += $myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0;
        $totals['CheckPeriodActual_d'] += $AccountPeriodActual > 0 ? $AccountPeriodActual : 0;
        $totals['CheckPeriodBudget_d'] += $AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0;
        
        $totals['PeriodProfitLoss_c'] += $AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0;
        $totals['PeriodBudgetProfitLoss_c'] += $AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0;
        $totals['MonthProfitLoss_c'] += $myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0;
        $totals['MonthBudgetProfitLoss_c'] += $myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0;
        $totals['BFwdProfitLoss_c'] += $myrow['firstprdbfwd'] < 0 ? abs($myrow['firstprdbfwd']) : 0;
        $totals['CheckMonth_c'] += $myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0;
        $totals['CheckBudgetMonth_c'] += $myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0;
        $totals['CheckPeriodActual_c'] += $AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0;
        $totals['CheckPeriodBudget_c'] += $AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0;
        
        if($myrow['parentcode']!=''){
            if(!array_key_exists($myrow['parentcode'],$parents)){
                $parents[$myrow['parentcode']] = array(
                    'mad' => 0,
                    'mac' => 0,
                    'mbd' => 0,
                    'mbc' => 0,
                    'pab' => 0,
                    'pac' => 0,
                    'pbd' => 0,
                    'pbc' => 0
                    );
            }
            $parents[$myrow['parentcode']]['mad'] += ($myrow['monthactual'] > 0 ? $myrow['monthactual'] : 0);
            $parents[$myrow['parentcode']]['mac'] += ($myrow['monthactual'] < 0 ? abs($myrow['monthactual']) : 0);
            $parents[$myrow['parentcode']]['mbd'] += ($myrow['monthbudget'] > 0 ? $myrow['monthbudget'] : 0);
            $parents[$myrow['parentcode']]['mbc'] += ($myrow['monthbudget'] < 0 ? abs($myrow['monthbudget']) : 0);
            $parents[$myrow['parentcode']]['pad'] += ($AccountPeriodActual > 0 ? $AccountPeriodActual : 0);
            $parents[$myrow['parentcode']]['pac'] += ($AccountPeriodActual < 0 ? abs($AccountPeriodActual) : 0);
            $parents[$myrow['parentcode']]['pbd'] += ($AccountPeriodBudget > 0 ? $AccountPeriodBudget : 0);
            $parents[$myrow['parentcode']]['pbc'] += ($AccountPeriodBudget < 0 ? abs($AccountPeriodBudget) : 0);
        }
        
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
    'total' => $totals,
    'parentcodes' => $parents
    ));