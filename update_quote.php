<?php

// (C) 2020 Michael Turner. All rights reserved.

require_once('dbux.php');

$o = new quote();
$o->update($_POST["ID"]);

?>
