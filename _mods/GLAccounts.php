<?php
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'config.php');
/* $Id: GLAccounts.php 6941 2014-10-26 23:18:08Z daintree $*/

include(ROOT_DIR.'includes/session.inc');
$Title = _('Chart of Accounts Maintenance');
/* Manual links before header.inc */
$ViewTopic= 'GeneralLedger';// Filename in ManualContents.php's TOC.
$BookMark = 'GLAccounts';// Anchor's id in the manual's html document.
include(ROOT_DIR.'includes/header.inc');

if (isset($_POST['SelectedAccount'])){
	$SelectedAccount = $_POST['SelectedAccount'];
} elseif (isset($_GET['SelectedAccount'])){
	$SelectedAccount = $_GET['SelectedAccount'];
}

echo '<p class="page_title_text"><img alt="" src="'.$RootPath.'/css/'.$Theme.'/images/transactions.png" title="' .
		_('General Ledger Accounts') . '" />' . ' ' . $Title . '</p>';

if (isset($_POST['submit'])) {

	//initialise no input errors assumed initially before we test
	$InputError = 0;

	/* actions to take once the user has clicked the submit button
	ie the page has called itself with some user input */

	//first off validate inputs sensible

	if (mb_strlen($_POST['AccountName']) >50) {
		$InputError = 1;
		prnMsg( _('The account name must be fifty characters or less long'),'warn');
	}

	if (isset($SelectedAccount) AND $InputError !=1) {
        # clean
        $_POST['AccountOrder'] = numberonly($_POST['AccountOrder']);
        
		$sql = "UPDATE chartmaster SET accountname='" . DB_escape_string($_POST['AccountName']) . "',
						group_='" . DB_escape_string($_POST['Group']) . "',
                        `sequence` = '". $_POST['AccountOrder']."',
                        `parentcode` = '".$_POST['ParentCode']."'
				WHERE accountcode ='" . $SelectedAccount . "'";

		$ErrMsg = _('Could not update the account because');
		$result = DB_query($sql,$ErrMsg);
		prnMsg (_('The general ledger account has been updated'),'success');
	} elseif ($InputError !=1) {

	/*SelectedAccount is null cos no item selected on first time round so must be adding a	record must be submitting new entries */

		$ErrMsg = _('Could not add the new account code');
		$sql = "INSERT INTO chartmaster (accountcode,
						accountname,
						group_,`sequence`,`parentcode`)
					VALUES ('" . $_POST['AccountCode'] . "',
							'" . $_POST['AccountName'] . "',
							'" . $_POST['Group'] . "',
                            '" . $_POST['AccountOrder'] ."',
                            '".$_POST['ParentCode']."')";
		$result = DB_query($sql,$ErrMsg);

		prnMsg(_('The new general ledger account has been added'),'success');
	}

	unset ($_POST['Group']);
	unset ($_POST['AccountCode']);
	unset ($_POST['AccountName']);
    unset ($_POST['AccountOrder']);
    unset ($_POST['ParentCode']);
	unset($SelectedAccount);

} elseif (isset($_GET['delete'])) {
//the link to delete a selected record was clicked instead of the submit button

// PREVENT DELETES IF DEPENDENT RECORDS IN 'ChartDetails'

	$sql= "SELECT COUNT(*)
			FROM chartdetails
			WHERE chartdetails.accountcode ='" . $SelectedAccount . "'
			AND chartdetails.actual <>0";
	$result = DB_query($sql);
	$myrow = DB_fetch_row($result);
	if ($myrow[0]>0) {
		$CancelDelete = 1;
		prnMsg(_('Cannot delete this account because chart details have been created using this account and at least one period has postings to it'),'warn');
		echo '<br />' . _('There are') . ' ' . $myrow[0] . ' ' . _('chart details that require this account code');

	} else {
// PREVENT DELETES IF DEPENDENT RECORDS IN 'GLTrans'
		$sql= "SELECT COUNT(*)
				FROM gltrans
				WHERE gltrans.account ='" . $SelectedAccount . "'";

		$ErrMsg = _('Could not test for existing transactions because');

		$result = DB_query($sql,$ErrMsg);

		$myrow = DB_fetch_row($result);
		if ($myrow[0]>0) {
			$CancelDelete = 1;
			prnMsg( _('Cannot delete this account because transactions have been created using this account'),'warn');
			echo '<br />' . _('There are') . ' ' . $myrow[0] . ' ' . _('transactions that require this account code');

		} else {
			//PREVENT DELETES IF Company default accounts set up to this account
			$sql= "SELECT COUNT(*) FROM companies
					WHERE debtorsact='" . $SelectedAccount ."'
					OR pytdiscountact='" . $SelectedAccount ."'
					OR creditorsact='" . $SelectedAccount ."'
					OR payrollact='" . $SelectedAccount ."'
					OR grnact='" . $SelectedAccount ."'
					OR exchangediffact='" . $SelectedAccount ."'
					OR purchasesexchangediffact='" . $SelectedAccount ."'
					OR retainedearnings='" . $SelectedAccount ."'";


			$ErrMsg = _('Could not test for default company GL codes because');

			$result = DB_query($sql,$ErrMsg);

			$myrow = DB_fetch_row($result);
			if ($myrow[0]>0) {
				$CancelDelete = 1;
				prnMsg( _('Cannot delete this account because it is used as one of the company default accounts'),'warn');

			} else  {
				//PREVENT DELETES IF Company default accounts set up to this account
				$sql= "SELECT COUNT(*) FROM taxauthorities
					WHERE taxglcode='" . $SelectedAccount ."'
					OR purchtaxglaccount ='" . $SelectedAccount ."'";

				$ErrMsg = _('Could not test for tax authority GL codes because');
				$result = DB_query($sql,$ErrMsg);

				$myrow = DB_fetch_row($result);
				if ($myrow[0]>0) {
					$CancelDelete = 1;
					prnMsg( _('Cannot delete this account because it is used as one of the tax authority accounts'),'warn');
				} else {
//PREVENT DELETES IF SALES POSTINGS USE THE GL ACCOUNT
					$sql= "SELECT COUNT(*) FROM salesglpostings
						WHERE salesglcode='" . $SelectedAccount ."'
						OR discountglcode='" . $SelectedAccount ."'";

					$ErrMsg = _('Could not test for existing sales interface GL codes because');

					$result = DB_query($sql,$ErrMsg);

					$myrow = DB_fetch_row($result);
					if ($myrow[0]>0) {
						$CancelDelete = 1;
						prnMsg( _('Cannot delete this account because it is used by one of the sales GL posting interface records'),'warn');
					} else {
//PREVENT DELETES IF COGS POSTINGS USE THE GL ACCOUNT
						$sql= "SELECT COUNT(*)
								FROM cogsglpostings
								WHERE glcode='" . $SelectedAccount ."'";

						$ErrMsg = _('Could not test for existing cost of sales interface codes because');

						$result = DB_query($sql,$ErrMsg);

						$myrow = DB_fetch_row($result);
						if ($myrow[0]>0) {
							$CancelDelete = 1;
							prnMsg(_('Cannot delete this account because it is used by one of the cost of sales GL posting interface records'),'warn');

						} else {
//PREVENT DELETES IF STOCK POSTINGS USE THE GL ACCOUNT
							$sql= "SELECT COUNT(*) FROM stockcategory
									WHERE stockact='" . $SelectedAccount ."'
									OR adjglact='" . $SelectedAccount ."'
									OR purchpricevaract='" . $SelectedAccount ."'
									OR materialuseagevarac='" . $SelectedAccount ."'
									OR wipact='" . $SelectedAccount ."'";

							$Errmsg = _('Could not test for existing stock GL codes because');

							$result = DB_query($sql,$ErrMsg);

							$myrow = DB_fetch_row($result);
							if ($myrow[0]>0) {
								$CancelDelete = 1;
								prnMsg( _('Cannot delete this account because it is used by one of the stock GL posting interface records'),'warn');
							} else {
//PREVENT DELETES IF STOCK POSTINGS USE THE GL ACCOUNT
								$sql= "SELECT COUNT(*) FROM bankaccounts
								WHERE accountcode='" . $SelectedAccount ."'";
								$ErrMsg = _('Could not test for existing bank account GL codes because');

								$result = DB_query($sql,$ErrMsg);

								$myrow = DB_fetch_row($result);
								if ($myrow[0]>0) {
									$CancelDelete = 1;
									prnMsg( _('Cannot delete this account because it is used by one the defined bank accounts'),'warn');
								} else {

									$sql = "DELETE FROM chartdetails WHERE accountcode='" . $SelectedAccount ."'";
									$result = DB_query($sql);
									$sql="DELETE FROM chartmaster WHERE accountcode= '" . $SelectedAccount ."'";
									$result = DB_query($sql);
									prnMsg( _('Account') . ' ' . $SelectedAccount . ' ' . _('has been deleted'),'succes');
								}
							}
						}
					}
				}
			}
		}
	}
}

if (!isset($_GET['delete'])) {

	echo '<form method="post" id="GLAccounts" action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

	if (isset($SelectedAccount)) {
		//editing an existing account

		$sql = "SELECT accountcode, accountname, group_, sequence, parentcode FROM chartmaster WHERE accountcode='" . $SelectedAccount ."'";

		$result = DB_query($sql);
		$myrow = DB_fetch_array($result);

		$_POST['AccountCode'] = $myrow['accountcode'];
		$_POST['AccountName']	= $myrow['accountname'];
        $_POST['AccountOrder'] = $myrow['sequence'];
        $_POST['ParentCode'] = $myrow['parentcode'];
		$_POST['Group'] = $myrow['group_'];

		echo '<input type="hidden" name="SelectedAccount" value="' . $SelectedAccount . '" />';
		echo '<input type="hidden" name="AccountCode" value="' . $_POST['AccountCode'] .'" />';
		echo '<table class="selection">
				<tr><td>' . _('Account Code') . ':</td>
					<td>' . $_POST['AccountCode'] . '</td></tr>';
	} else {
		echo '<table class="selection">';
		echo '<tr>
				<td>' . _('Account Code') . ':</td>
				<td><input type="text" name="AccountCode" required="required" autofocus="autofocus" data-type="no-illegal-chars" title="' . _('Enter up to 20 alpha-numeric characters for the general ledger account code') . '" size="20" maxlength="20" /></td>
			</tr>';
	}

	if (!isset($_POST['AccountName'])) {
		$_POST['AccountName']='';
	}
	echo '<tr>
			<td>' . _('Account Name') . ':</td>
			<td><input type="text" size="51" required="required" ' . (isset($_POST['AccountCode']) ? 'autofocus="autofocus"':'') . ' title="' . _('Enter up to 50 alpha-numeric characters for the general ledger account name') . '" maxlength="50" name="AccountName" value="' . $_POST['AccountName'] . '" /></td></tr>';

    echo '<tr>
            <td>' . _('Display Order') . ':</td>
            <td><input type="text" size="4" maxlength="5" ' . (isset($_POST['AccountCode']) ? 'autofocus="autofocus"':'') . ' title="' . _('Enter sequence order') . '" name="AccountOrder" value="' . $_POST['AccountOrder'] . '" /></td></tr>';

	$sql = "SELECT groupname FROM accountgroups ORDER BY sequenceintb";
	$result = DB_query($sql);

	echo '<tr>
			<td>' . _('Account Group') . ':</td>
			<td><select required="required" name="Group">';

	while ($myrow = DB_fetch_array($result)){
		if (isset($_POST['Group']) and $myrow[0]==$_POST['Group']){
			echo '<option selected="selected" value="';
		} else {
			echo '<option value="';
		}
		echo $myrow[0] . '">' . $myrow[0] . '</option>';
	}
    echo '</select></td>
		</tr>';
    
    $sql = "SELECT accountcode,accountname FROM chartmaster WHERE parentcode IS NULL OR parentcode='' ORDER BY `accountcode`";
    $result = DB_query($sql);
    echo '<tr>
        <td>' . _('Parent Account') .':</td>
        <td><select name="ParentCode" size="1">';
    while ($myrow = DB_fetch_assoc($result)){
        echo '<option value="',$myrow['accountcode'],'"',($_POST['ParentCode'] == $myrow['accountcode'] ? ' selected="selected"' : ''),'>'
            ,$myrow['accountcode']
            ,' | '
            ,$myrow['accountname']
            ,'</option>'
            ;
    }
    echo '</select></td>
        </tr>';
        
	echo '</table>
		<br />
		<div class="centre">
			<input type="submit" name="submit" value="'. _('Enter Information') . '" />
		</div>
		</div>
		</form>';

} //end if record deleted no point displaying form to add record


if (!isset($SelectedAccount)) {
/* It could still be the second time the page has been run and a record has been selected for modification - SelectedAccount will exist because it was sent with the new call. If its the first time the page has been displayed with no parameters
then none of the above are true and the list of ChartMaster will be displayed with
links to delete or edit each. These will call the same page again and allow update/input
or deletion of the records*/

	$sql = "SELECT c.accountcode,
			c.accountname,
			c.group_,
            c.sequence,
            c.parentcode,
            d.accountname as `parent`,
			CASE WHEN g.pandl=0 THEN '" . _('Balance Sheet') . "' ELSE '" . _('Profit/Loss') . "' END AS acttype
		FROM chartmaster c LEFT OUTER JOIN chartmaster d ON c.parentcode = d.accountcode
		INNER JOIN accountgroups g ON c.group_=g.groupname
		ORDER BY g.sequenceintb,c.sequence,c.accountcode";

	$ErrMsg = _('The chart accounts could not be retrieved because');

	$result = DB_query($sql,$ErrMsg);

	echo '<br /><table class="selection">';
	echo '<tr>
			<th class="ascending">' . _('Account Code') . '</th>
			<th class="ascending">' . _('Account Name') . '</th>
			<th class="ascending">' . _('Account Group') . '</th>
			<th class="ascending">' . _('P/L or B/S') . '</th>
            <th class="ascending">' . _('Display Order') . '</th>
            <th class="ascending">' . _('Parent Account') . '</th>
			<th colspan="2">&nbsp;</th>
		</tr>';

	$k=0; //row colour counter

	while ($myrow = DB_fetch_assoc($result)) {
		if ($k==1){
			echo '<tr class="EvenTableRows">';
			$k=0;
		} 
        else {
			echo '<tr class="OddTableRows">';
			$k=1;
		}


	printf("<td>%s</td>
		<td>%s</td>
		<td>%s</td>
		<td>%s</td>
        <td>%s</td>
        <td>%s</td>
		<td><a href=\"%s&amp;SelectedAccount=%s\">" . _('Edit') . "</a></td>
		<td><a href=\"%s&amp;SelectedAccount=%s&amp;delete=1\" onclick=\"return confirm('" . _('Are you sure you wish to delete this account? Additional checks will be performed in any event to ensure data integrity is not compromised.') . "');\">" . _('Delete') . "</a></td>
		</tr>",
		$myrow['accountcode'],
		htmlspecialchars($myrow['accountname'],ENT_QUOTES,'UTF-8'),
		$myrow['group_'],
		$myrow['acttype'],
        $myrow['sequence'],
        $myrow['parent'],
		htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?',
		$myrow['accountcode'],
		htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '?',
		$myrow['accountcode']);

	}
	//END WHILE LIST LOOP
	echo '</table>';
} //END IF selected ACCOUNT

//end of ifs and buts!

echo '<br />';

if (isset($SelectedAccount)) {
	echo '<div class="centre"><a href="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '">' .  _('Show All Accounts') . '</a></div>';
}

include(ROOT_DIR.'includes/footer.inc');
