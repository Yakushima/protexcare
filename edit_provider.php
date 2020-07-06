<?php

// (C) Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new provider();
$o->gen_edit_form($_POST["ID"]);

?>
