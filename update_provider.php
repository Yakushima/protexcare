<?php

// (C) Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new provider();
$o->update($_POST["ID"]);

?>
