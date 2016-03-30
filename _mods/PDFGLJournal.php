<?php
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.php');

/* $Id$*/

/* $Revision: 1.5 $ */

include(ROOT_DIR.'includes/session.inc');

if (isset($_POST['JournalNo'])) {
    $JournalNo=$_POST['JournalNo'];
} else if (isset($_GET['JournalNo'])) {
    $JournalNo=$_GET['JournalNo'];
} else {
    $JournalNo='';
}

if ($JournalNo=='Preview') {
    $FormDesign = simplexml_load_file(sys_get_temp_dir().'/Journal.xml');
} else {
    $FormDesign = simplexml_load_file($PathPrefix.'companies/'.$_SESSION['DatabaseName'].'/FormDesigns/Journal.xml');
}

// Set the paper size/orintation
$PaperSize = $FormDesign->PaperSize;
$PageNumber=1;
$line_height=$FormDesign->LineHeight;
include(ROOT_DIR.'includes/PDFStarter.php');
$pdf->addInfo('Title', _('General Ledger Journal') );

if ($JournalNo=='Preview') {
    $LineCount = 2; // UldisN
} else {
    $sql="SELECT gltrans.typeno,
                gltrans.trandate,
                gltrans.account,
                chartmaster.accountname,
                gltrans.narrative,
                gltrans.amount,
                gltrans.tag,
                tags.tagdescription,
                gltrans.jobref
            FROM gltrans
            INNER JOIN chartmaster
                ON gltrans.account=chartmaster.accountcode
            INNER JOIN accountgroups
                ON chartmaster.group_ = accountgroups.groupname
            LEFT JOIN tags
                ON gltrans.tag=tags.tagref
            WHERE gltrans.type='0'
                AND gltrans.typeno='" . $JournalNo . "'
            ORDER BY accountgroups.sequenceintb,chartmaster.sequence";
    $result=DB_query($sql);
    $LineCount = DB_num_rows($result); // UldisN
    $myrow=DB_fetch_array($result);
    $JournalDate=$myrow['trandate'];
    DB_data_seek($result, 0);
    include(ROOT_DIR.'includes/PDFGLJournalHeader.inc');
}
$counter=1;
$YPos=$FormDesign->Data->y;
while ($counter<=$LineCount) {
    if ($JournalNo=='Preview') {
        $AccountCode=str_pad('',10,'x');
        $Date=date('m/d/Y');
        $Description=str_pad('',30,'x');
        $Narrative=str_pad('',30,'x');
        $Amount='XXXX.XX';
        $Tag=str_pad('',25,'x');
        $JobRef=str_pad('',25,'x');
    } else {
        $myrow=DB_fetch_array($result);
        if ($myrow['tag']==0) {
            $myrow['tagdescription']='None';
        }
        $AccountCode = $myrow['account'];
        $Description = $myrow['accountname'];
        $Date = $myrow['trandate'];
        $Narrative = $myrow['narrative'];
        $Amount = $myrow['amount'];
        $Tag = $myrow['tag'].' - '.$myrow['tagdescription'];
        $JobRef = $myrow['jobref'];
    }
    $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column1->x,$Page_Height-$YPos,$FormDesign->Data->Column1->Length,$FormDesign->Data->Column1->FontSize, $AccountCode);
    $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column2->x,$Page_Height-$YPos,$FormDesign->Data->Column2->Length,$FormDesign->Data->Column2->FontSize, $Description);
    $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column3->x,$Page_Height-$YPos,$FormDesign->Data->Column3->Length,$FormDesign->Data->Column3->FontSize, $Narrative);
    #$LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column4->x,$Page_Height-$YPos,$FormDesign->Data->Column4->Length,$FormDesign->Data->Column4->FontSize, locale_number_format($Amount,$_SESSION['CompanyRecord']['decimalplaces']), 'right');
    #fix@20160320 - amount fixed at length
    $amt = locale_number_format($Amount,$_SESSION['CompanyRecord']['decimalplaces']);
    $x = ($FormDesign->Data->Column4->x + $FormDesign->Data->Column4->Length) - $pdf->GetStringWidth($amt, '', '', $FormDesign->Data->Column5->FontSize, false);
    $y = ($Page_Height-$YPos) + $FormDesign->Data->Column4->FontSize;
    $LeftOvers = $pdf->addText($x,$y,$FormDesign->Data->Column4->FontSize, $amt);
    $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column5->x,$Page_Height-$YPos,$FormDesign->Data->Column5->Length,$FormDesign->Data->Column5->FontSize, $Tag);
    $LeftOvers = $pdf->addTextWrap($FormDesign->Data->Column6->x,$Page_Height-$YPos,$FormDesign->Data->Column6->Length,$FormDesign->Data->Column6->FontSize, $JobRef, 'left');
    $YPos += $line_height;
    $counter++;
    if ($YPos >= $FormDesign->LineAboveFooter->starty){
        /* We reached the end of the page so finsih off the page and start a newy */
        #$pdf->newPage();
        #$PageNumber++;
        $YPos=$FormDesign->Data->y;
        include (ROOT_DIR.'includes/PDFGLJournalHeader.inc');
    } //end if need a new page headed up
}

if ($LineCount == 0) {   //UldisN
    $Title = _('Printing Error');
    include(ROOT_DIR.'includes/header.inc');
    prnMsg(_('There were no Journals to print'),'warn');
    echo '<br /><a href="'.$RootPath.'/index.php">' .  _('Back to the menu') . '</a>';
    include(ROOT_DIR.'includes/footer.inc');
    exit;
} else {
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Journal_' . date('Y-m-d').'.pdf');//UldisN
    $pdf->__destruct(); //UldisN
}
