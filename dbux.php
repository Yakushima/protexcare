<?php

// (C) 2020 Michael Turner. All rights reserved.

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";

// dbux - DataBase User eXperience: PHP classes that define how each
//	input form behave. I use PHP class hierarchy to mirror the composition
//	hierarchy of one-to-many relations: several products per provider
//	several plans per product, etc. This approach wouldn't work for
//	less strict hierarchies, and in fact the country exclusions in
//	this database are a departure from the basic pattern. If country
//	exclusions should be entered field-by-field, this code may
//	require a revisit, to pluralize the "subpart" variable. Better
//	yet: some less tricky way to do all this.
//
//	Since MySQL (and probably other relational DBMSes) expose the
//	structure of a database, a more concise and consistent version
//	may be possible with less of such mapping, and perhaps I could
//	dispense with using the class inheritance chain in this tricky way.
//
//	"add_" + part + ".php":		form for adding a new record
//	"insert_" + part + ".php":	adds a new record by INSERT INTO
//
//	"edit_" + part + ".php":	form for editing existing record
//	"update_ + part + ".php":	update after editing
//
//	"delete_" + part + ".php":	delete the record. This isn't
//					implemented yet because there are
//					still database integrity constraints
//					to be worked out.

class dbux {

    static public $subpart;	// singular for now, this is the name
    				// of the table for subcomponents of
				// the database schema.
    function action_filename($action,$object) {
	return $action."_".$object.".php";
    }

    function gen_header($f) {
	html__("");
	  head__();
	    title($this->basenm()." record ".$f);
	    style();
	  __head();

	  body__();

} function finish_up() {

          __body();
	__html();
    }

    public function basenm() {
    	return basename(get_class($this),".php");
    }

    public function parent_field() {
    	return strtoupper(get_parent_class($this));
    }

    // show_all_records - each record has edit button
    //	next to it. (Maybe a delete button too?)

    public function show_all_records($fields,$stmt,$tablename) {

// At the very least, for update, this query needs a WHERE clause that
// limits the results to records with the same parent in the composition
// hierarchy.  It can be done locally by looking at the ID of the record
// to be edited, and the field name in the parent type that matches
// this "part". The match should be case-insensitive, since I've used
// all caps for the relevant field names, all lower-case for the
// class names in this code and for the names of the tables.

	$omit = ["ID",strtoupper(get_class($this))];
	table__('style="width=100%"');
	tr__();
	  foreach ($fields as $field)
		if (!in_array($field,$omit))
		    th($field);
	__tr();
	while ($row = $stmt->fetch())
	{
	   tr__();
	     foreach ($fields as $field)
		if (!in_array($field,$omit))
			td($row[$field]);
	     $action = $this->action_filename("edit",$tablename);
	     td__();
	       postform__($action);
		input('type="hidden" name="ID" value="'.$row["ID"].'"');
		input('type="hidden" name="tablename" value="'.$tablename.'"');
		input('type="submit" value="edit"');
	       __form();
	     __td();
           __tr();
	}
	__table();
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
    }

    public function show_path($parent_ID,$current_class) {
	if ($parent_ID == 0)
		return;
	$parent = get_parent_class($current_class);
	if ($parent == "dbux")
		return;
    	$q = "SELECT ID,NAME FROM ".$parent
	   . " WHERE ID=?"
	   ;
	$s = doPDOquery($q,[$parent_ID]);
	$r = $s->fetch();
	emit("... of ".$parent." ".$r["NAME"]);
	$this->show_path($r["ID"],$parent);
    }

    public function gen_all_input_fields($args,$parent_ID) {

	// column metas: "native_type","pdo_type","flags","table","name",
	///		"len","precision"

	$class = get_class($this);
	$select = doPDOquery('SELECT * FROM '.$class.' LIMIT 1',[]);
	$columns = get_col_names($class);

p("args=".implode(",",$args));
	$parent = $this->parent_field();

	for ($i = 0; $i < count($columns); ++$i) {
	   $m = $select->getColumnMeta($i);
	   $name = $m["name"];
	   if($name == "ID") {
		if ($args != []) {
		  input('type="hidden" name="ID" value="'.$args["ID"].'"');
		  p("hidden name ID=".$args["ID"]);
		}
	   } else
	   if ($name == $parent) { // can't edit hierarchy here
			// for insert, where do we get parent ID?
			// supply as parameter?
		h3("Editing ".$class);
		$this->show_path($parent_ID,$class);
		input('type="hidden" name="'.$parent.'" value="'.$parent_ID.'"');
		br(); br();
	   } else {
		   if($args == [])
			$init = "";
		   else
			$init = $args[$name];
		   $this->gen_input_field($m,$name,$init);
		   br();
	   }
	}

 	input('type="submit"');
    }

// gen_insert_form - generate html to add a new record of this part
//
    public function gen_insert_form($parent_ID) {

	if (get_parent_class($this) == "dbux") {
		$parent_field = "";	// for now
//		$parent_ID = 0;
		$cond="";
	} else {
		$parent_field = $this->parent_field();
p("gen_insert_form: parent_field=".$parent_field);
//		$parent_ID = $_POST[$parent_field];
		$cond=" WHERE ".$parent_field."=".$parent_ID;
	}
	$q = "SELECT *"
	   . "  FROM ".get_class($this)
	   . $cond
	   ;
	// From this stmt, we can get all records of this type
	// that have the same parent record as this record.
	$fields = get_col_names(get_class($this));
	$stmt = doPDOquery($q,[]);
	$tablename = get_class($this);

	$this->gen_header("input");

	$action = $this->action_filename("insert",$tablename);

	h2(get_class($this));
	$this->show_all_records($fields,$stmt,$tablename);
	postform__($action);
	  h3("Enter new ".get_class($this));
	  $this->gen_all_input_fields([],$parent_ID);
	__form();

	$this->finish_up();
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

     // get existing values
	$q = "SELECT * FROM ".$b." WHERE ID=?";
	$stmt = doPDOquery($q,[$id]);
	$row = $stmt->fetch();
//p("q=".$q);
//p("id=".$id);
//p("row=".implode(",",$row));

	$this->gen_header("editing");

	$action = $this->action_filename("update",$b);
	$parent_field = $this->parent_field();
//p("parent_field=".$parent_field);
	if ($parent_field != "DBUX")
		$parent_ID = $row[$parent_field];
	else	$parent_ID = 0;
//p("parent_ID=".$parent_ID);

     // generate form with fields initialized to current values

	form__($action);
	  $this->gen_all_input_fields($row, $parent_ID);
	__form();

     // If not the bottom-most in the composition hierarchy, list
     // all of the children of this record. And/or a button to
     // go back up.

	if ($parent_field != "DBUX") {
		// make a return button
	// problem here for going to
	// the edit record is we have to read the record into
	// hidden input. Back button sort of solves this, but then
	// there's no regeneration of database state in the
	// table subset displayed. Need a "go back up" button.
		$action = $this->action_filename("edit",get_parent_class($this));
		$parent_ID = $row[$this->parent_field()];

		postform__($action);
		  input('type="hidden" name="ID" value="'.$parent_ID.'"');
		  button("Edit this ".$b."'s ".strtolower($parent_field));
		__form();
	}

	// make a button to add a product of this kind
	$action = $this->action_filename("add",$b);

	postform__($action);
	  $lbl = "Add new ".$b;
	  if ($parent_field != "DBUX")
		  $lbl = $lbl." for this ".strtolower($parent_field);
	  input('type="hidden" name="parent_ID" value="'.$parent_ID.'"');
	  button($lbl);
	__form();

	$super = strtoupper($b); // relevant field names are all caps
	if (static::$subpart != null) {
		$tablename = static::$subpart;
		$action = $this->action_filename("add", $tablename);
		postform__($action);
		__form();
		$fields = get_col_names($tablename);
		$q = "SELECT *"
		   . "  FROM ".$tablename
		   . "  WHERE ".$super."=".$id
		   ;
// p($q);
		$stmt = doPDOquery($q,[]);
		p("The ".$tablename." table for this ".$b.":");
		$this->show_all_records($fields,$stmt,$tablename);
	}

	$this->finish_up();
    }

// update - uses REPLACE INTO rather than UPDATE. This function could
//	probably be used instead of insert(), because if the record
//	doesn't exist yet for our ID key, it will create one. Maybe
//	use a hidden input with ID vaue=0 to indicate insertion of new
//	record rather than replacement of an existing one.
//
//	Downside: REPLACE may be specific to MySQL.

    public function update($id) {
	$b = get_class($this);
     // establish the column names:
	$c = get_col_names($b);

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
	$r = doPDOquery($q,$p);

p("Update of ".$b." ID=".$id." complete.");
p("q=".$q);
p("p=".$p);

	$this->gen_edit_form($id);
    }
}

//////////////////////////////////////////////
//
// From here onward it's application-specific.
//

class provider extends dbux {
    public function __construct() {
	parent::$subpart = "product";
    }
}

class product extends provider {

    public function __construct() {
	parent::$subpart = "plan";
    }

   // Update the excluded countries table. The code was
   // brought in and trimmed from the version in populate.php, but 
   // with the country inclusion option ("~" prefix trigger)
   // stripped; this feature may come back if there are more
   // products like IMGE "Europe Only" defined by inclusion
   // rather than exclusion.
   //
   // The cleaner way to do this might be to allow multiple
   // child record classes in "subpart".
   
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

   public function update($id) {
   	parent::update($id);
	$this->prod_exc_update($id);
   }
}

// We need to override insert/update to skip one or more inheritance
// levels. We can't inherit directly from dbux/provider because this
// breaks the constructor chain that identifies sub- and super-part.

class plan extends product {
    public function __construct() {
	parent::$subpart = "quote";
    }
    public function insert()    { dbux::insert(); }
    public function update($id) { dbux::update($id); }
}

class quote extends plan {
    public function __construct() {
	parent::$subpart = null;
    }
}

?>

