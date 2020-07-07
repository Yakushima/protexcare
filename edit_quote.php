<?php
// (C) 2020 Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new quote();
$o->gen_edit_form($_POST["ID"]);

?>
