<?php
// (C) Michael Turner. All rights reserved.
require_once "htmlhelpers.php";

function doPDOquery($q,$parms) {   // takes SQL query q and parms array
   global $pdo;
   $stmt = $pdo->prepare($q);	   // TBD: error result, exception handling
   $stmt->execute($parms);	   // TBD: ditto
   return $stmt;
}

function dumptable($fields, $stmt) {
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
    __tr();
  }
  __table();
}

function dumptable_with_edit_buttons($fields, $stmt, $tablename) {
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
}

// get_col_names - get the names of all of the columns from a table
//   compatiblity: note discussion at
//	https://stackoverflow.com/questions/5428262/php-pdo-get-the-columns-name-of-a-table
//   security: not calling doPDOquery

function get_col_names($my_table) {
	$q = "SELECT * FROM $my_table LIMIT 0";;
	$rs = doPDOquery($q,[]);
	for ($i = 0; $i < $rs->columnCount(); $i++) {
 	   $col = $rs->getColumnMeta($i);
 	   $columns[] = $col['name'];
	}
	return $columns;
}

function o_($s,$m) {
  $t = gettype($m[$s]);
  if ($t == gettype([]))	$v = "Array";
  else 			$v = $m[$s];
  return "$s=".$v;
}
	

function peek_at($t) {
        $metas = [
    	  "native_type","pdo_type","flags","table","name","len","precision"
 	];

	$select = doPDOquery('SELECT * FROM '.$t.' LIMIT 1',[]);
	$columns = get_col_names($t);

	for ($i = 0; $i < count($columns); ++$i) {
	   $m = $select->getColumnMeta($i);
	   $s = "Fields: ";
	   for ($j=0; $j<count($metas); ++$j)
		$s = $s." ".o_($metas[$j],$m);
	   p($s);
	   // var_dump($m);  ////// trace ///////
	   p("");
	}
}


?>
