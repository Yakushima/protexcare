<?php
// (C) 2020 Michael Turner. All rights reserved.

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";

// These should probably be generic and database-resident, with options
// in the web interface for downloaded sheets from other spreadsheet software
// besides Google Sheets.

$Google_doc_name = "Insurance-Premiums";
$doc_download_qualifier = ".xlsx - ";
$sheet_download_ext = "csv";

function dump_table($a,$stmt) {
   echo PHP_EOL . "dump_table, skipping...." . PHP_EOL;
}

// update the product table

function update_product($sheetname,
			$product,
			$Effective_date,$Revision_date,
			$Input_countries) {

	echo "Old entries for ".$sheetname." in product table" . PHP_EOL;
	$q = "SELECT * FROM product where sheetname='$sheetname'";
	$stmt = doPDOquery($q,[]);
	dump_table(["ID", "PROVIDER", "NAME", "sheetname", "Excluded_countries"],$stmt);

// (re-)populate the excluded countries table

	$country_inclusion_list = false;
	if ($Input_countries[0] == '~') {
		echo "Inverted exclusion list found" . PHP_EOL;
	  // assume master country list already populated
		$Input_countries = substr($Input_countries,1);
		$country_inclusion_list = true;
	}

	$country_list = explode(",", $Input_countries);

// note that this doesn't trim whitespace from names;
// this is done below

// A workaround, until we can get rid of the Excluded_countries
// field in the product table and do it all relationally

	$q = "SELECT ID FROM product WHERE sheetname='$sheetname'";
	$stmt = doPDOquery($q,[]); // TBD: go to ? form for param
	$row = $stmt->fetch();
	$prod_id =$row["ID"];

	$q = "DELETE FROM prod_exc WHERE product=?";

	echo "With prod_id=".$prod_id." q=".$q . PHP_EOL;

	$stmt = doPDOquery($q,[$prod_id]);

	echo "After ".$q." prod_exc:" . PHP_EOL;

	$q = "SELECT * FROM prod_exc";
	$stmt = doPDOquery($q,[]);
	dump_table(["ID", "product", "country"],$stmt);

	if ($country_inclusion_list) {
		$Exclusions = [];
		$q = "SELECT * FROM country";
		$s = doPDOquery($q,[]);
		while ($r = $s->fetch()) {
			$c = $r["NAME"];
			if (in_array($c,$country_list))
				continue;
			$Exclusions[] = $c;
			echo "Excluding ".$c . PHP_EOL;
		}
		$country_list = $Exclusions;
	}

	$country_string = implode(",", $country_list);

	$q = "UPDATE product"
	   . " SET NAME=\"$product\","
	   . "     `Effective_date`=\"$Effective_date\","
	   . "     `Revision_date`='$Revision_date',"
	   . "     `Excluded_countries`='$country_string'"
	   . " WHERE product.sheetname='$sheetname'"
	   ;
	echo "q=".$q . PHP_EOL;
	$stmt = doPDOquery($q,[]);
	echo "Exclusions - per product" . PHP_EOL;

	foreach ($country_list as $country) {
	   $n = trim($country);
	   echo $n . PHP_EOL;
	   $q = "INSERT"
	      . "  INTO prod_exc (product,country)"
	      . "  VALUES ($prod_id, '$n')";
	   $stmt = doPDOquery($q,[]);

	   if ($stmt) { echo "Success for q=".$q . PHP_EOL; }
	   else { echo "Failure for q=".$q . PHP_EOL; }

	   $q = "SELECT * FROM prod_exc";
	   $stmt = doPDOquery($q,[]);
	}

	return $prod_id;
}

function read_header_into_buffer($sheet_download_file)
{
	$q = "TRUNCATE header_buffer";
	$stmt = doPDOquery($q,[]);

	$q = "LOAD DATA INFILE '$sheet_download_file'"
	   . " INTO TABLE header_buffer"
	   . " FIELDS"
	   . "   TERMINATED BY ','"
	   . "   OPTIONALLY ENCLOSED BY '\"'"
	   . " LINES"
	   . "   STARTING BY '>'"	// in the leftmost cell, by itself
	   . "   TERMINATED BY '\r\n'"
	   . " IGNORE 1 LINES"		// the column header labels
	   . " (dummy,provider,product,Effective_date,Revision_date,Excluded_countries)"
	   ;

	$stmt = doPDOquery($q,[]);
}


// Sheetnames could potentially be read from the directory where the
// sheets were downloaded, possibly by getting a file list and
// parsing names using the above parameters. For now, they are
// stable, but if the names are edited in the spreadsheet, this
// list will need to be updated in tandem.

$sheetnames = [
	"HCI WW",
	"HCI Exc USA",
	"IMGE WW",
	"IMGE WW Exc",
	"IMGE Europe",
	"VUMI Gold EX USA",
	"Azimuth WW",
	"Azimuth Exc USA",
	"GBG WW"
];

// Similarly, we could detect the sheets with gender-distinct plans
// by reading the spreadsheet, but will just hack this for now.

$MF_distinct = ["VUMI Gold EX USA", "Azimuth WW", "Azimuth Exc USA"];

$types = ["INPATIENT", "OUTPATIENT"];

echo "Populate database" . PHP_EOL;

// Go through each sheet and (re-) initialize the database

$plan_id = 1;

foreach ($sheetnames as $sheetname) {
    echo "Column to populate: #" . $plan_id . PHP_EOL;

    $q = "SELECT * FROM product WHERE sheetname=?";
    $stmt = doPDOquery($q,[$sheetname]);

    if ($stmt === NULL) {
       exit("Couldn't execute query: ".$q);
    } else
       dump_table(
         ["ID","sheetname","PROVIDER", "PRODUCT", "GENDER","TYPE"],
	 $stmt
       );

// Just to reset the cursor; TBD: must be a better way

    $stmt = doPDOquery($q,[$plan_id]);

    $result = $stmt->fetchAll();	// should be just one

    if ($result == NULL)
	// Not in database yet. Since IDs are auto-increment, there
	// may be some danger here.
	$new_plan = true;
    else
    	$new_plan = false;

// TBD: with the gender distinction associated with plan as we have it now
//      the web dialogue treats M and F as separate cases. This needs
//	to be untangled. Ideally, it gets detected in the sheet header,
//	which means a new field to read in: gender (M,F,E), or detect
//	from the column headers.
    if (in_array($sheetname, $MF_distinct)) {
	    $gender_list = ["M", "F"];
	    $scratch_table  = "mf_rate_scratchpad";
    } else {
	    $gender_list = ["E"];
	    $scratch_table  = "rate_scratchpad";
    }
    foreach ($types as $type)
      foreach ($gender_list as $gender) {

	if (in_array($sheetname, $MF_distinct)) {
	    $src_inpatient  = $gender . "IN";
	    $src_outpatient = $gender . "OUT";
	} else {
	    $src_inpatient  = "INPATIENT";
	    $src_outpatient = "OUTPATIENT";
	}

	$sheet_download_file = $Google_doc_name		
			     . $doc_download_qualifier
			     . $sheetname
			     . "."
			     . $sheet_download_ext
			     ;

// Get the spreadsheet header data into the header buffer record

	read_header_into_buffer($sheet_download_file);

	$q = "SELECT * FROM header_buffer";

	$stmt = doPDOquery($q,[]);
	$row = $stmt->fetch();

	$product = $row["product"];
	$provider = $row["provider"];

	echo "product name in header of sheet imported = ".$product . PHP_EOL;
	echo "provider name in header of sheet imported = '".$provider . "'" . PHP_EOL;

	$Effective_date = date("Y-m-d", strtotime($row['Effective_date']));
	$Revision_date = date("Y-m-d", strtotime($row['Revision_date']));
	$Input_countries = $row['Excluded_countries'];

echo "Effective date=".$Effective_date . PHP_EOL;
echo "Revision date=".$Revision_date . PHP_EOL;
echo "Input_countries =".$Input_countries . PHP_EOL;

	$prod_id = update_product(
			$sheetname,
			$product,
			$Effective_date,$Revision_date,
			$Input_countries
	);

	$q = "SELECT ID FROM provider WHERE NAME=?";
	$stmt = doPDOquery($q,[$provider]);
	$r = $stmt->fetch();
	if ($r == false)
		exit("Couldn't find $provider in provider table");
	$prov_id = $r["ID"];

	$q = "REPLACE INTO plan"
	   . " VALUES (?,?,?,?,?,?,?)"
	   ;

	echo "type = " . $type . PHP_EOL;
	echo "product = " . $product . PHP_EOL;
	echo "prod_id = " . $prod_id . PHP_EOL;
	echo "prov_id = " . $prov_id . PHP_EOL; // shd get rid of

	$plan_name = $gender . " " . $type;

	$stmt = doPDOquery($q,
		  [$plan_id,$plan_name,$sheetname,
		   $prov_id,$prod_id,$gender,$type]);

	echo "Deleting plan #$plan_id from quote in database $db." . PHP_EOL;

	$del = doPDOquery("DELETE FROM quote WHERE PLAN=?",[$plan_id]);

	$found = 0;
	 
	echo "Preparing scratchpad table ".$scratch_table . PHP_EOL;

	$stmt = doPDOquery("TRUNCATE ".$scratch_table,[]);

	if ($gender == "E") $values = " (AGE,$src_inpatient,$src_outpatient)";
	else                $values = " (AGE,MIN,FIN,MOUT,FOUT)";

	// TBD: loading multiple times will slow things down a lot
	//	so better to do it only once per sheet

	$q = "LOAD DATA INFILE '$sheet_download_file'"
	   . " INTO TABLE $scratch_table"
	   . " FIELDS TERMINATED BY ',' ENCLOSED BY '\"'"
	   . " IGNORE 5 LINES"
	   . $values
	   ;

	echo "Loading $scratch_table from $sheet_download_file with query $q" . PHP_EOL;

	$stmt = doPDOquery($q,[]);

	echo "Starting insertion attempt..." . PHP_EOL;

	echo "src_inpatient=".$src_inpatient
	    ." src_outpatient=".$src_outpatient
	    ." scratch_table=".$scratch_table . PHP_EOL;

	if ($type == "INPATIENT")
		   $price_col = $src_inpatient;
	else if ($type == "OUTPATIENT")
		   $price_col = $src_outpatient;

	$q = "SELECT AGE, $price_col FROM $scratch_table";
	$stmt = doPDOquery($q,[]);
	dump_table(["AGE",$price_col],$stmt); // looks OK

	$q = "SELECT $plan_id, AGE, $price_col FROM $scratch_table";
	$stmt = doPDOquery ($q, []);

	dump_table(["AGE",$price_col],$stmt);

	$q =  "INSERT INTO quote (PLAN,AGE,PRICE)"
	    . " SELECT ?, AGE, $price_col"
	    . "    FROM $scratch_table"
	    ;

	echo "q=".$q . PHP_EOL;
	echo "plan_id=".$plan_id." price_col=".$price_col . PHP_EOL;

	try {
		$stmt = doPDOquery($q,[$plan_id]);
	} catch (PDOException $e) {
		echo "Error!: " . $e->getMessage() . PHP_EOL;       // TBD: <-- insecure?
		throw new \PDOException($e->getMessage(), (int)$e->getCode());
	}
     ++$plan_id;
     }
}

echo "... done." . PHP_EOL;

$pdo = null;  // make sure PDO gets released
?>
