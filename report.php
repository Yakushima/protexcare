<html>
<head>
<title>?lookup</title>
<style>
table, th, td {
  border: 1px solid black;
}
</style>
</head>

<body>

<?php

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";


// TBD: "Despite a widespread delusion, you should never catch errors to report them.
// A module (like a database layer) should not report its errors.....
// do not catch PDO exceptions to report them. Instead, configure your server properly ...
// [See that section about php.ini settings]
// -- https://phpdelusions.net/pdo#comments

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    p("Error!: " . $e->getMessage());       // <-- insecure?
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$dob = $_POST["dob"];
$bday = new DateTime($dob); // Your date of birth
$today = new Datetime(date('m.d.y'));
$age_y_m_d = $today->diff($bday);
$gender = $_POST["gender"];
$evac_country = $_POST["evac_country"];
$age = $age_y_m_d->y;
$ages = [$age];
$genders = [$gender];
$n_members = 1;

function add_family_member($m_age,$m_gender) {
	global $ages, $genders,$n_members;

	if ($m_age > -1) {
		$ages[] = $m_age;
		$genders[] = $m_gender;
		++$n_members;
	}
}

// heterosexism here we go ....
if ($gender == "M")	$spouse_gender = "F";
else			$spouse_gender = "M";

add_family_member($_POST["spouse_age"], $spouse_gender);

add_family_member($_POST["child1_age"], "E");
add_family_member($_POST["child2_age"], "E");
add_family_member($_POST["child3_age"], "E");

// p("genders = ".implode(",",$genders) . "");
// p("ages=".implode(",", $ages)."");
// p("n_members=".$n_members);

$q = "INSERT INTO customer"
   . " (NAME, GENDER, EMAIL, PHONECC, PHONE, DOB,"
   . "  NATIONALITY, RESIDENCE, EVAC_COUNTRY,"
   . "  spouse_age, child1_age, child2_age, child3_age)"
   . " VALUES"
   . "  (?,?,?,?,?,?,?,?,?,?,?,?,?)"
   ;
$stmt = doPDOquery($q, [
       $_POST["name"], $gender, $_POST["email"], $_POST["phonecc"], $_POST["phone"], $dob,
       $_POST["nationality"], $_POST["residence"], $evac_country,
       $_POST["spouse_age"], $_POST["child1_age"], $_POST["child2_age"], $_POST["child3_age"]
       ]
);	

function list_family_premiums($header, $ages, $genders, $n_members, $type, $evac_country) {

	doPDOquery("TRUNCATE report",[]);

	h3($header);

	$excluded_products = [];

	$stmt = doPDOquery("SELECT * FROM product",[]);
	while ($row = $stmt->fetch()) {
		$prod_id = $row["ID"];

	   	$q = "SELECT product,country"
	       	    . " FROM prod_exc"
                    . " WHERE product=? AND country=?"
	       	    ;
	        $s = doPDOquery($q,[$prod_id,$evac_country]);

		p("Product ".$row["NAME"]." ID=".$prod_id." Provider ".$row["PROVIDER"]." evac country=".$evac_country);

		$any_excluded = 0;
	        while ($r = $s->fetch()) {
			++$any_excluded;
			p(" country excluded=".$r["country"]);
		}
		$s->closeCursor();
		if ($any_excluded > 0) {		// product excludes evac country
			$excluded_products[] = $prod_id;
			continue;
		}

		$quote_total = 0;

		for ($i = 0; $i < $n_members; ++$i) {
			$q = "SELECT"
			   . "   plan.ID, plan.PROVIDER, plan.prod_id, plan.TYPE, plan.GENDER,"
			   . "   quote.AGE, quote.PRICE"
			   . " FROM quote"
			   . " INNER JOIN plan"
			   . "   ON plan.ID=quote.PLAN"
			   . " WHERE quote.age=?"
			   . "   AND (plan.GENDER=? OR plan.GENDER = \"E\")"
			   . "   AND TYPE=? AND prod_id=?"
			   ;
			$s = doPDOquery($q,[$ages[$i],$genders[$i],$type,$prod_id]);
//			p("Trying ".$q);
//			p (" ... on age=".$ages[$i]." gender=".$genders[$i]." type=".$type." prod_id=".$prod_id);
			if ($r = $s->fetch()) {		// was while <<<<<<<<<<<<<<<<<<< "if" is iffy
				$quote_total += $r["PRICE"];
//				p("Adding price ".$r["PRICE"]);
			}
//			else p("Nothing to add");		
		}
		if ($quote_total == 0)
			continue;

		$q = "SELECT * FROM report WHERE PROVIDER=? AND PRICE <= ?";
		$s = doPDOquery($q,[$row["PROVIDER"],$quote_total]);

		if ($r = $s->fetch())
			continue;		// already some cheaper package

		$q = "INSERT INTO report (PROVIDER,PRODUCT,PRICE,Excluded_countries) VALUES (?,?,?,?)";
		doPDOquery ($q,[$row["PROVIDER"],$prod_id,$quote_total,$row["Excluded_countries"]);
	}

	p("Products excluded=".implode(",",$excluded_products));

	$q = "SELECT provider.NAME as Provider_name, product.NAME as Product_name, PRICE FROM report"
			. " INNER JOIN provider"
			. "  ON provider.ID=report.PROVIDER"
			. " INNER JOIN product"
			. "  ON product.ID=report.PRODUCT"
			. " ORDER BY PRICE"
			;
	try {    
		$stmt = doPDOquery($q,[]);                                                                                                                       $stmt = doPDOquery($q,[]);
        } catch ( PDOException $e ) { 
            p("Error!: " . $e->getMessage());       // TBD: <-- insecure?
//          throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }

	table__("");
	tr__();
		td("Provider"); td("Product"); td("Price"); td("Excluding");
	__tr();

	$providers_seen_already = [];

	while ($row = $stmt->fetch()) {

		$this_provider = $row["Provider_name"];

	   	if (in_array($this_provider, $providers_seen_already))
			continue;
	   	array_push ($providers_seen_already, $this_provider);

		tr__();
			td($this_provider);
			td($row["Product_name"]);
			tdopts__('style="text-align:right"');
			emit("$".$row["PRICE"]);
			td($row["Excluded_countries"]);
			__td();
		__tr();
	}
	__table();
}

list_family_premiums("Inpatient annual costs",   $ages, $genders, $n_members, "INPATIENT",   $evac_country);
list_family_premiums("Outpatient annual costs",  $ages, $genders, $n_members, "OUTPATIENT",  $evac_country);

$pdo = null;  // make sure PDO gets released
		// TBD: don't forget that connections can be passed
?>

</body>
</html>