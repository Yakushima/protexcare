
<?php

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    p("Error!: " . $e->getMessage());       // TBD: <-- insecure?
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}



// dbux - DataBase User eXperience: PHP classes that define how each
//	input form behave. I use PHP class hierarchy to mirror the strict
//	hierarchy of one-to-many relations: several products per provider
//	several plans per product, etc. This approach wouldn't work for
//	less strict hierarchies, and in fact the country exclusions in
//	this database are a departure from the basic pattern. If country
//	exclusions should be entered field-by-field, this code may
//	require a revisit, to pluralize the "subkind" variable.
//
//	TBD: "kind" is a misnomer -- something like "part" would make more
//	sense.
//
//	Since MySQL (and probably other relational DBMSes) expose the
//	structure of a database, a more concise and consistent version
//	may be possible with less of such mapping, and perhaps I could
//	dispense with using the class inheritance chain is this tricky way.

class dbux {

    public $kind;
    public $superkind = NULL;
    public $subkind;
    public function show_columns() { return get_col_names(get_class($this)); }

    public function set_subkind($k) {
	$this->subkind = $k;
    }
 
    public function __construct() {
        $this->kind = get_class($this);
        $this->superkind = get_parent_class($this);
    }

    public function gen_input_field($m,$name,$value) {
	   $type = $m["native_type"];
	   $len = $m["len"];
//	   p($name.": ".$type." (len=".$len.")"); //////// trace ///////////////////
	   emit ($name.": ");
           if ($type == "VAR_STRING") $html_type="text";
	   if ($type == "LONG")       $html_type="number";
	   if ($type == "DATE")	      $html_type = "date";
	   input(' type="'.$html_type.'" id="'.$name.'" name="'.$name.'" value="'.$value.'"');
	   emitln("<br>");
    }

    public function form_input($new) {

	// column metas: "native_type","pdo_type","flags","table","name","len","precision"

	$class = get_class($this);
	$select = doPDOquery('SELECT * FROM '.$class.' LIMIT 1',[]);
	$columns = get_col_names($class);

	for ($i = 0; $i < count($columns); ++$i) {
	   $m = $select->getColumnMeta($i);
	   $name = $m["name"];
	   if($name == "ID")
		continue;		// need to have different form input for edit
	   $this->gen_input_field($m,$name,"");
	}
    }

    public function generate_all_input_fields() {
	  $this->form_input("");
 	  input("type=\"submit\"");
    }

    public function show_all_records() {
	$q = "SELECT * FROM ".$this->kind;
	$stmt = doPDOquery($q,[]);
	dumptable($this->show_columns(),$stmt);
    }

    public function basenm() {
	return basename(get_class($this),".php");
    }

    public function add() {
	h2($this->kind);
	$this->show_all_records();
	form__(' method="post" action="'.get_class($this).'.php"');
	  h3("Enter new ".$this->kind);
	  $this->generate_all_input_fields();
	__form();
    }

    public function insert() {
	$b = get_class($this);
	$o = new $b();
	$q = "SELECT COUNT(*) FROM $b LIMIT 0";
	$s = doPDOquery($q,[]);
	$c = get_col_names($b);
	$p = [];
	$qs = [];
	$i = [];
	foreach($c as $a) {
		if ($a == "ID")
			continue;	// works as long as key is named ID
		$p[] = $_POST["$a"];
		$qs[] = '?';
		$i[] = "`".$a."`";
	}
	$q = "INSERT INTO $b (".implode(",",$i).") VALUES (". implode(",",$qs) . ")";
	p($q.PHP_EOL." values: ".implode(",",$p));
	$r = doPDOquery($q,$p);
    }

    public function edit($id) {
	$b = get_class($this);

// Editing works better when you narrow the focus.
// provider: maybe an edit button next to the record table entries.
// product: edit buttons with provider already defined
// plan: edit buttons with product already defined
// quote: edit buttons with plan already defined.
//
// display:
//	form will get an ID for the record to edit
//	table name will be known from either the __FILE__ ("edit_x")
//		for editing a record from table x, or from
//		a passed or explicitly provided parameter
//		
//	display record to edit, filled out with current contents
//	but also New and Delete buttons there
//	below: a tabular list of subcomponents,
//		each with an edit button

		$q = "SELECT * FROM ".$b." WHERE ID=?";
		$stmt = doPDOquery($q,[$id]);
		$row = $stmt->fetch();
		form__(' method="post" action="insert_'.$b.'.php"');
		  $this->generate_all_input_fields(false);
		__form();
    }	
}

if(0){ /////////////

$c = "dbux";
$o = new $c();
p(get_class($o));
$o->gen_insert();

} //////////////////


class provider extends dbux {
    public function __construct() {
        $this->kind = get_class($this);
        $this->superkind = get_parent_class($this);
	parent::set_subkind($this->kind);
    }
}

// $o = new provider();
// $o->dbux_html();
// peek_at("provider");

class product extends provider {
}

// $o = new product();
// $o->dbux_html();
// peek_at("product");

class plan extends product {
}

// $o = new plan();
// $o->dbux_html();
// peek_at("plan");
// $o->form_input("plan");

class quote extends plan {
}

// $o = new quote();
// $o->dbux_html();
// peek_at("quote");

?>

