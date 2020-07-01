<?php
// (C) Michael Turner. All rights reserved.
require_once('dbux.php');

$o = new product();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record update</title>
</head>

<body>

<?php

$o->update($_POST["ID"]);

?>

</body>
</html>
