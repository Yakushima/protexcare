<html>
<head>
<title>?populate</title>
<style>
table, th, td {
  border: 1px solid black;
}
</style>
</head>

<body>

<?php
// (C) Michael Turner. All rights reserved.

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    p("Error!: " . $e->getMessage());       // TBD: <-- insecure?
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

h1("Populate database");

$plan_id = $_POST["name"];   // TBD: want "number"
				// Now the number entered on previous page

p("Table to populate: #" . $plan_id);

h3("Plan");

$q = "SELECT * FROM plan WHERE ID=?";
$stmt = doPDOquery($q,[$plan_id]);
if ($stmt === NULL) {
   p("Couldn't execute query: ".$q);
} else
   dumptable(["ID","sheetname","PROVIDER", "prod_id", "GENDER","TYPE"],$stmt);

// Just to reset the cursor; TBD: must be a better way
$stmt = doPDOquery($q,[$plan_id]);

$rows = $stmt->fetchAll();	// should be just one
$row = $rows[0];
$id = $row["ID"];
$gender = $row["GENDER"];
$provider = $row["PROVIDER"];
$prod_id = $row["prod_id"];	// want to update this from header if needed
$type = $row["TYPE"];
$sheetname = $row["sheetname"];

p("gender spec = \"".$gender."\"". " provider=".$provider." prod_id = ".$prod_id." sheetname=".$sheetname);

// TBD: refactor this to one function and two calls. Or something
// 	less smelly, anyway. Note the weird dependency on $gender . "IN"/"OUT"
//	Some cleaner way of handling gender distinction is needed here

// TBD: with the gender distinction associated with plan as we have it now
//      the web dialogue treats M and F as separate cases. This needs
//	to be untangled. Ideally, it gets detected in the sheet header,
//	which means a new field to read in: gender (M,F,E), or detect
//	from the column headers.

if ($gender === "E") {
    $scratch_table  = "rate_scratchpad";
    $src_inpatient  = "INPATIENT";
    $src_outpatient = "OUTPATIENT";
} else {
    $scratch_table  = "mf_rate_scratchpad";
    $src_inpatient  = $gender . "IN";
    $src_outpatient = $gender . "OUT";
}

// This is hard-coded, but shouldn't be. I need to describe how to enter
// it; better might be to parse the filename to extract the new table name,
// for adding new premium tables. Would need file picker for this. Smoother:
// specify a directory of download (for manager's computer) or upload
// (on the website server), from a management interface page.

$Google_doc_name = "Insurance-Premiums";
$doc_download_qualifier = ".xlsx - ";
$sheet_download_ext = "csv";
$sheet_download_file = $Google_doc_name		
		     . $doc_download_qualifier
		     . $sheetname		// manually entered now
		     . "."
		     . $sheet_download_ext
		     ;

// Get the spreadsheet header data into the header buffer record

$q = "TRUNCATE header_buffer";
$stmt = doPDOquery($q,[]);

$q = "LOAD DATA INFILE '$sheet_download_file'"
   . " INTO TABLE header_buffer"
   . " FIELDS"
   . "   TERMINATED BY ','"
   . "   OPTIONALLY ENCLOSED BY '\"'"
   . " LINES"
   . "   STARTING BY '>'"		// in the leftmost cell, by itself
   . "   TERMINATED BY '\r\n'"
   . " IGNORE 1 LINES"			// the column header labels
   . " (dummy,provider,product,Effective_date,Revision_date,Excluded_countries)"
   ;

$stmt = doPDOquery($q,[]);

$q = "SELECT * FROM header_buffer";

$stmt = doPDOquery($q,[]);
$row = $stmt->fetch();

$product = $row["product"];

p("product name in header of sheet imported = ".$product);

$Effective_date = date("Y-m-d", strtotime($row['Effective_date']));
$Revision_date = date("Y-m-d", strtotime($row['Revision_date']));
$Input_countries = $row['Excluded_countries'];

// p("Effective date=".$Effective_date);
// p("Revision date=".$Revision_date);

// update the product table

p("Old entries for ".$sheetname." in product table");
$q = "SELECT * FROM product where sheetname='$sheetname'";
$stmt = doPDOquery($q,[]);
dumptable(["ID", "PROVIDER", "NAME", "sheetname", "Excluded_countries"],$stmt);

// (re-)populate the excluded countries table

$country_inclusion_list = false;
if ($Input_countries[0] == '~') {
	p("Inverted exclusion list found");
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

p("With prod_id=".$prod_id." q=".$q);

$stmt = doPDOquery($q,[$prod_id]);

p("After ".$q." prod_exc:");

$q = "SELECT * FROM prod_exc";
$stmt = doPDOquery($q,[]);
dumptable(["ID", "product", "country"],$stmt);

if ($country_inclusion_list) {
	// 
	$Exclusions = [];
	$q = "SELECT * FROM country";
	$s = doPDOquery($q,[]);
	while ($r = $s->fetch()) {
	        $c = $r["NAME"];
		if (in_array($c,$country_list))
			continue;
		$Exclusions[] = $c;
		p("Excluding ".$c);
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
p($q);
$stmt = doPDOquery($q,[]);
p("Exclusions - per product");

foreach ($country_list as $country) {
   $n = trim($country);
   p($n);
   $q = "INSERT INTO prod_exc (product,country) VALUES ($prod_id, '$n')";
   $stmt = doPDOquery($q,[]);
   if ($stmt) { p ("Success for q=".$q); } else { p("Failure for q=".$q); }
   $q = "SELECT * FROM prod_exc";
   $stmt = doPDOquery($q,[]);
   dumptable(["ID", "product", "country"],$stmt);
}


  $q = "REPLACE INTO plan"
     . " VALUES (?,?,?,?,?,?)"
     ;

  p("type = " . $type);
  p("product = " . $product);
  p("prod_id = " . $prod_id);

  $stmt = doPDOquery($q,[$id,$sheetname,$provider,$prod_id,$gender,$type]);

  p("Deleting plan #$plan_id from quote in database $db.");

  $del = doPDOquery("DELETE FROM quote WHERE PLAN=?",[$plan_id]);

  $found = 0;
 
  p("Preparing scratchpad table ".$scratch_table);

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

  p("Loading $scratch_table from $sheet_download_file with query $q");

  $stmt = doPDOquery($q,[]);

  p("Starting insertion attempt...");

  p ("src_inpatient=".$src_inpatient." src_outpatient=".$src_outpatient." scratch_table=".$scratch_table);

  if ($type == "INPATIENT")
	   $price_col = $src_inpatient;
  else if ($type == "OUTPATIENT")
           $price_col = $src_outpatient;

  $q = "SELECT AGE, $price_col FROM $scratch_table";
  $stmt = doPDOquery($q,[]);
  dumptable(["AGE",$price_col],$stmt); // looks OK

   $q = "SELECT $plan_id, AGE, $price_col FROM $scratch_table";
   $stmt = doPDOquery ($q, []);

   dumptable(["AGE",$price_col],$stmt);

   $q = "INSERT INTO quote (PLAN,AGE,PRICE)"
      . " SELECT ?, AGE, $price_col"
      . "    FROM $scratch_table"
      ;

   p($q);
   p("plan_id=".$plan_id." price_col=".$price_col);

   try {
       $stmt = doPDOquery($q,[$plan_id]);
   } catch (PDOException $e) {
    		p("Error!: " . $e->getMessage());       // TBD: <-- insecure?
    		throw new \PDOException($e->getMessage(), (int)$e->getCode());
   }

p("... done.");

$pdo = null;  // make sure PDO gets released
?>

</body>
</html>
