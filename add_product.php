<?php

// (C) 2020 Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new product();
$o->gen_insert_form($_POST["parent_ID"]);

?>
