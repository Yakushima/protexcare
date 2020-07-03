<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new provider();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record input</title>
<style>
table, th, td {
  border: 1px solid black;
}
</style>
</head>

<body>

<?php

$o->gen_insert_form();

?>

</body>
</html>
