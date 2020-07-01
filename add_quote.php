<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new quote();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?></title>
</head>

<body>

<?php

$o->add();

?>

</body>
</html>
