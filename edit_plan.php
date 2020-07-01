<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new plan();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record input</title>
</head>

<body>

<?php

$o->gen_edit_form($_POST["ID"]);

?>

</body>
</html>
