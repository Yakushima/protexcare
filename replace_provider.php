<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new provider();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record input</title>
</head>

<body>

<?php

$o->replace(false);

?>

</body>
</html>
