<?php
// (C) 2020 Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new quote();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record insertion</title>
</head>

<body>

<?php

$o->insert();

?>

</body>
</html>
