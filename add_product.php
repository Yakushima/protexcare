<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new product();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record input</title>
</head>

<body>

<?php

$o->gen_insert_form();

?>

</body>
</html>
