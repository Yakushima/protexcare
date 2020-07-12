<?php

// (C) 2020 Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new provider();
p("ID=".$_POST["ID"]);
$o->update($_POST["ID"]);

?>
