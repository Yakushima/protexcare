
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


// dbux - DataBase User eXperience: PHP classes that define how each
//	input form behave. I use PHP class hierarchy to mirror the composition
//	hierarchy of one-to-many relations: several products per provider
//	several plans per product, etc. This approach wouldn't work for
//	less strict hierarchies, and in fact the country exclusions in
//	this database are a departure from the basic pattern. If country
//	exclusions should be entered field-by-field, this code may
//	require a revisit, to pluralize the "subkind" variable.
//
//	TBD: "kind" is a misnomer -- "part" would make more sense.
//
//	Since MySQL (and probably other relational DBMSes) expose the
//	structure of a database, a more concise and consistent version
//	may be possible with less of such mapping, and perhaps I could
//	dispense with using the class inheritance chain in this tricky way.
//
//	"add_" + kind + ".php":		form for adding a new record
//	"insert_" + kind + ".php":	adds a new record by INSERT INTO
//
//	"edit_" + kind + ".php":	form for editing existing record
//	"update_ + kind + ".php":	update after editing
//
//	"delete_" + kind + ".php":	delete the record

class dbux {

    public $kind;
    public $superkind = NULL;
    public $subkind;

    public function show_columns() {
	return get_col_names(get_class($this));
    }

    public function set_subkind($k) {
	$this->subkind = $k;
    }

    public function basenm() {
    	return basename(get_class($this),".php");
    }
    
    public function ID_of_superkind_parent() {
    	$b = get_class($this); // there should be a field of this name
	$id = $_POST["ID"];
	$q = "SELECT ID"
	   . " FROM ".$this->superkind
	   . " WHERE ".$b."=".$id
	   ;
	$stmt = doPDOquery($q,[]);
	$row = $stmt->fetch();
	return $row["ID"];
    }

    // show_all_records - each record has edit button
    //	next to it. (Maybe a delete button too?)

    // For inserting a new record, it makes some sense to list all
    // "future sibling" records in the composition hierarchy.
    // For editing an existing record, however, show "child
    // records". This needs to be parameterized to allow a
    // path down the composition hierarchy, to limit what's
    // listed for possible child edits. This would provide
    // a navigation path down to the bottom (exclusive of the
    // special case of prod_exc.)

    public function show_all_records() {

	// At the very least, for update, this query needs a WHERE
	// clause that limits the results to records with the same
	// parent in the composition hierarchy.
	// It can be done locally by looking at the ID of
	// the record to be edited, and the field name in
	// the parent type that matches this "kind".
	// The match should be case-insensitive, since
	// I've used all caps for the relevant field names,
	// but all lower-case for the class names here and
	// for the names of the tables.

//	$par = ID_of_superkind_parent();

    	$q = "SELECT *"
	   . " FROM ".$this->kind;
//	   . " WHERE ".$this->superkind."=".$par
	   ;

	$stmt = doPDOquery($q,[]);
	$fields = get_col_names($this->kind);
	$tablename = $this->kind;

	table__("style=\"width=100%\"");
	tr__();
	  foreach ($fields as $field) {
	    td("<b>".$field."</b>");
	  }
	__tr();
	while ($row = $stmt->fetch()) {
	      tr__();
		foreach ($fields as $field) {
		   td($row[$field]);
		}
		emit("<td>");
		form__(' method="post" action="edit_'.$tablename.'.php"');
		input("type=\"hidden\"name=\"ID\" value=\"".$row["ID"]."\"");
		input("type=\"hidden\" name=\"tablename\" value=\"".$tablename."\"");
		input("type=\"submit\" value=\"edit\"");
		__form();
		emit("</td>");
	    __tr();
	}
	__table();
//	dumptable_with_edit_buttons($this->show_columns(),$stmt,$this->kind);
    }
 
    public function __construct() {
        $this->kind = get_class($this);
        $this->superkind = get_parent_class($this);
    }

    public function gen_input_field($m,$name,$value) {
	   $type = $m["native_type"];
	   $len = $m["len"];
//	   p($name.": ".$type." (len=".$len.")"); //////// trace ///////////
	   emit ($name.": ");
           if ($type == "VAR_STRING") $html_type = "text";
	   if ($type == "LONG")       $html_type = "number";
	   if ($type == "DATE")	      $html_type = "date";
// but ...
	   if ($name == "ID")	      $html_type = "hidden"; // special case

	   input(' type="'.$html_type.'" id="'.$name.'" name="'.$name.'" value="'.$value.'"');
	   emitln("<br>");
    }

    public function generate_all_input_fields($args) {

// column metas: "native_type","pdo_type","flags","table","name",
///		"len","precision"

	$class = get_class($this);
	$select = doPDOquery('SELECT * FROM '.$class.' LIMIT 1',[]);
	$columns = get_col_names($class);

	for ($i = 0; $i < count($columns); ++$i) {
	   $m = $select->getColumnMeta($i);
	   $name = $m["name"];
//	   if($name == "ID")
//		continue;	// need to have different form input for edit
	   if($args == [])
		$init = "";
	   else
		$init = $args[$name];
	   $this->gen_input_field($m,$name,$init);
	}

 	input("type=\"submit\"");
    }

// gen_insert_form - generate html to add a new record of this kind
//
    public function gen_insert_form() {
	h2($this->kind);
	$this->show_all_records();
	form__(' method="post" action="insert_'.get_class($this).'.php"');
	  h3("Enter new ".$this->kind);
	  $this->generate_all_input_fields([]);
	__form();
    }

    public function insert() {
        global $pdo;

	$b = get_class($this);
     // establish the column names:
	$c = get_col_names($b);
     // build the insert query
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
	$q = "INSERT INTO $b (".implode(",",$i).")"
	   . "  VALUES (". implode(",",$qs) . ")";
     // execute insert query
//	p($q.PHP_EOL." values: ".implode(",",$p)); /// TRACE ////
	doPDOquery($q,$p);
	$id = $pdo->lastInsertId();

	p("Insert into ".$b." table complete.");

	return $id;
    }

// gen_edit_form - generate an input form for an existing record
//	with all fields filled in from what's fetched.
//
// Editing works better when you narrow the focus.
//
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

    public function gen_edit_form($id) {
	$b = get_class($this);

	$this->show_all_records();
	// get existing values
		$q = "SELECT * FROM ".$b." WHERE ID=?";
		$stmt = doPDOquery($q,[$id]);
		$row = $stmt->fetch();
	// generate form with fields initialized to current values
		form__(' method="post" action="update_'.$b.'.php"');
		  $this->generate_all_input_fields($row);
		__form();
    }

// update - uses REPLACE INTO rather than UPDATE. This function could
//	probably be used instead of insert(), because if the record
//	doesn't exist yet for our ID key, it will create one. Maybe
//	use a hidden input with ID vaue=0 to indicate insertion of new
//	record rather than replacement of an existing one.
//
//	Downside: REPLACE may be specific to MySQL.

    public function update() {
	$b = get_class($this);
     // establish the column names:
	$c = get_col_names($b);

     // build the replace query argument lists
	$p = [];
	$qs = [];
	$i = [];
	foreach($c as $a) {
		// unlike insert(), don't skip ID
		$p[] = $_POST["$a"];
		$qs[] = '?';
		$i[] = "`".$a."`";
	}
	$q = "REPLACE INTO $b (".implode(",",$i).")"
	   . "  VALUES (". implode(",",$qs) . ")";

     // execute replace query
	$r = doPDOquery($q,$p);
    }
}

//////////////////////////////////////////////
//
// From here onward it's application-specific.
//

class provider extends dbux {
    public function __construct() {
        $this->kind = get_class($this);
        $this->superkind = get_parent_class($this);
	parent::set_subkind($this->kind);
    }
}

class product extends provider {

   // Update the excluded countries table. The code was
   // brought in and trimmed from the version in populate.php, but 
   // with the country inclusion option ("~" prefix trigger)
   // stripped; this feature may come back if there are more
   // products like IMGE "Europe Only" defined by inclusion
   // rather than exclusion.
   //
   // The cleaner way to do this might be to allow multiple
   // child record classes in "subkind".
   
   private function prod_exc_update($prod_id) {

	   $q = "DELETE FROM prod_exc WHERE product=?";
	   $stmt = doPDOquery($q,[$prod_id]);

	   $country_list = explode(",", $_POST["Excluded_countries"]);

	   foreach ($country_list as $country) {
	      $q = "INSERT"
	         . " INTO prod_exc (product,country)"
		 . " VALUES (?,?)"
		 ;
	      $stmt = doPDOquery($q,[$prod_id, trim($country)]);
	   }
   }

   public function insert() {
   	$id = parent::insert();
	$this->prod_exc_update($id);
	return $id;
   }
   public function update() {
   	$this->parent::update();
	$this->prod_exc_update($_POST["ID"]);
   }
}

// We need to override insert/update to skip one or more inheritance
// levels. We can't inherit directly from dbux/provider because this
// breaks the constructor chain that identifies sub- and super-kinds.

class plan extends product {
	public function insert() { dbux::insert(); }
	public function update() { dbux::insert(); }
}

class quote extends plan {
}

?>

