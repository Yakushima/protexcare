<?php
require_once('dbux.php');

$o = new plan();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record input</title>
</head>

<body>

<?php

$o->edit(false);

?>

</body>
</html>