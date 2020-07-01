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

$o->add();

?>

</body>
</html>
