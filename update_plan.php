<?php

// (C) Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new plan();
$o->update($_POST["ID"]);

?>

