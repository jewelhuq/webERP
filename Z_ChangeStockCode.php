<?php

/* $Id: Z_ChangeStockCode.php 5784 2012-12-29 04:00:43Z daintree $*/

/*Script to Delete all sales transactions*/

include ('includes/session.inc');
$Title = _('UTILITY PAGE Change A Stock Code');
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');

if (isset($_POST['ProcessStockChange'])){

	$InputError =0;

	$_POST['NewStockID'] = mb_strtoupper($_POST['NewStockID']);

/*First check the stock code exists */
	$result=DB_query("SELECT stockid FROM stockmaster WHERE stockid='" . $_POST['OldStockID'] . "'",$db);
	if (DB_num_rows($result)==0){
		prnMsg(_('The stock code') . ': ' . $_POST['OldStockID'] . ' ' . _('does not currently exist as a stock code in the system'),'error');
		$InputError =1;
	}

	if (ContainsIllegalCharacters($_POST['NewStockID'])){
		prnMsg(_('The new stock code to change the old code to contains illegal characters - no changes will be made'),'error');
		$InputError =1;
	}

	if ($_POST['NewStockID']==''){
		prnMsg(_('The new stock code to change the old code to must be entered as well'),'error');
		$InputError =1;
	}


/*Now check that the new code doesn't already exist */
	$result=DB_query("SELECT stockid FROM stockmaster WHERE stockid='" . $_POST['NewStockID'] . "'",$db);
	if (DB_num_rows($result)!=0){
		echo '<br /><br />';
		prnMsg(_('The replacement stock code') . ': ' . $_POST['NewStockID'] . ' ' . _('already exists as a stock code in the system') . ' - ' . _('a unique stock code must be entered for the new code'),'error');
		$InputError =1;
	}


	if ($InputError ==0){ // no input errors
		$result = DB_Txn_Begin($db);

		echo '<br />' . _('Adding the new stock master record');
		$sql = "INSERT INTO stockmaster (stockid,
										categoryid,
										description,
										longdescription,
										units,
										mbflag,
										actualcost,
										lastcost,
										materialcost,
										labourcost,
										overheadcost,
										lowestlevel,
										discontinued,
										controlled,
										eoq,
										volume,
										kgs,
										barcode,
										discountcategory,
										taxcatid,
										decimalplaces,
										shrinkfactor,
										pansize,
										netweight,
										perishable,
										nextserialno)
				SELECT '" . $_POST['NewStockID'] . "',
					categoryid,
					description,
					longdescription,
					units,
					mbflag,
					actualcost,
					lastcost,
					materialcost,
					labourcost,
					overheadcost,
					lowestlevel,
					discontinued,
					controlled,
					eoq,
					volume,
					kgs,
					barcode,
					discountcategory,
					taxcatid,
					decimalplaces,
					shrinkfactor,
					pansize,
					netweight,
					perishable,
					nextserialno
				FROM stockmaster
				WHERE stockid='" . $_POST['OldStockID'] . "'";

		$DbgMsg = _('The SQL statement that failed was');
		$ErrMsg =_('The SQL to insert the new stock master record failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing stock location records');
		$sql = "UPDATE locstock SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update stock location records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing stock movement records');
		$sql = "UPDATE stockmoves SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update stock movement transaction records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing location transfer information');

		$sql = "UPDATE loctransfers SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the loctransfers records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing MRP demands information');
		$sql = "UPDATE mrpdemands SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the mrpdemands records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		//check if MRP tables exist before assuming

		$result = DB_query("SELECT COUNT(*) FROM mrpplannedorders",$db,'','',false,false);
		if (DB_error_no($db)==0) {
			echo '<br />' . _('Changing MRP planned orders information');
			$sql = "UPDATE mrpplannedorders SET part='" . $_POST['NewStockID'] . "' WHERE part='" . $_POST['OldStockID'] . "'";
			$ErrMsg = _('The SQL to update the mrpplannedorders records failed');
			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
			echo ' ... ' . _('completed');
		}

		$result = DB_query("SELECT * FROM mrprequirements" , $db,'','',false,false);
		if (DB_error_no($db)==0){
			echo '<br />' . _('Changing MRP requirements information');
			$sql = "UPDATE mrprequirements SET part='" . $_POST['NewStockID'] . "' WHERE part='" . $_POST['OldStockID'] . "'";
			$ErrMsg = _('The SQL to update the mrprequirements records failed');
			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
			echo ' ... ' . _('completed');
		}
		$result = DB_query("SELECT * FROM mrpsupplies" , $db,'','',false,false);
		if (DB_error_no($db)==0){
			echo '<br />' . _('Changing MRP supplies information');
			$sql = "UPDATE mrpsupplies SET part='" . $_POST['NewStockID'] . "' WHERE part='" . $_POST['OldStockID'] . "'";
			$ErrMsg = _('The SQL to update the mrpsupplies records failed');
			$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
			echo ' ... ' . _('completed');
		}

		echo '<br />' . _('Changing sales analysis records');
		$sql = "UPDATE salesanalysis SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update Sales Analysis records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing order delivery differences records');
		$sql = "UPDATE orderdeliverydifferenceslog SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update order delivery differences records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing pricing records');
		$sql = "UPDATE prices SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg =  _('The SQL to update the pricing records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing sales orders detail records');
		$sql = "UPDATE salesorderdetails SET stkcode='" . $_POST['NewStockID'] . "' WHERE stkcode='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the sales order header records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing purchase order details records');
		$sql = "UPDATE purchorderdetails SET itemcode='" . $_POST['NewStockID'] . "' WHERE itemcode='" . $_POST['OldStockID'] . "'";
		$ErrMsg =  _('The SQL to update the purchase order detail records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing purchasing data records');
		$sql = "UPDATE purchdata SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the purchasing data records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing the stock code in shipment charges records');
		$sql = "UPDATE shipmentcharges SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update Shipment Charges records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing the stock check freeze file records');
		$sql = "UPDATE stockcheckfreeze SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update stock check freeze records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing the stock counts table records');
		$sql = "UPDATE stockcounts SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg =  _('The SQL to update stock counts records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing the GRNs table records');
		$sql = "UPDATE grns SET itemcode='" . $_POST['NewStockID'] . "' WHERE itemcode='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update GRN records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing the contract BOM table records');
		$sql = "UPDATE contractbom SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the contract BOM records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing the BOM table records') . ' - ' . _('components');
		$sql = "UPDATE bom SET component='" . $_POST['NewStockID'] . "' WHERE component='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the BOM records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		DB_IgnoreForeignKeys($db);

		echo '<br />' . _('Changing the BOM table records') . ' - ' . _('parents');
		$sql = "UPDATE bom SET parent='" . $_POST['NewStockID'] . "' WHERE parent='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the BOM parent records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing any image files');

		if (rename($_SESSION['part_pics_dir'] . '/' .$_POST['OldStockID'].'.jpg',
			$_SESSION['part_pics_dir'] . '/' .$_POST['NewStockID'].'.jpg')) {
			echo ' ... ' . _('completed');
 		} else {
			echo ' ... ' . _('failed');
		}

		echo '<br />' . _('Changing the item properties table records') . ' - ' . _('parents');
		$sql = "UPDATE stockitemproperties SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the item properties records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing work order requirements information');

		$sql = "UPDATE worequirements SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the stockid worequirements records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		$sql = "UPDATE worequirements SET parentstockid='" . $_POST['NewStockID'] . "' WHERE parentstockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the parent stockid worequirements records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing work order information');

		$sql = "UPDATE woitems SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the woitem records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing sales category information');
		$sql = "UPDATE salescatprod SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the sales category records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<br />' . _('Changing any serialised item information');

		$sql = "UPDATE stockserialitems SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the stockserialitem records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		$sql = "UPDATE stockserialmoves SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the stockserialitem records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing offers table');
		$sql = "UPDATE offers SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the offer records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		echo '<br />' . _('Changing tender items table');
		$sql = "UPDATE tenderitems SET stockid='" . $_POST['NewStockID'] . "' WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to update the tender records failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');

		DB_ReinstateForeignKeys($db);

		$result = DB_Txn_Commit($db);

		echo '<br />' . _('Deleting the old stock master record');
		$sql = "DELETE FROM stockmaster WHERE stockid='" . $_POST['OldStockID'] . "'";
		$ErrMsg = _('The SQL to delete the old stock master record failed');
		$result = DB_query($sql,$db,$ErrMsg,$DbgMsg,true);
		echo ' ... ' . _('completed');


		echo '<p>' . _('Stock Code') . ': ' . $_POST['OldStockID'] . ' ' . _('was successfully changed to') . ' : ' . $_POST['NewStockID'];
	} //only do the stuff above if  $InputError==0

}

echo '<form action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .  '" method="post">';
echo '<div class="centre">';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';

echo '<br />
    <table>
	<tr>
		<td>' . _('Existing Inventory Code') . ':</td>
		<td><input type="text" name="OldStockID" size="20" maxlength="20" /></td>
	</tr>
	<tr>
		<td>' . _('New Inventory Code') . ':</td>
		<td><input type="text" name="NewStockID" size="20" maxlength="20" /></td>
	</tr>
	</table>

		<input type="submit" name="ProcessStockChange" value="' . _('Process') . '" />
	</div>
	</form>';

include('includes/footer.inc');
?>