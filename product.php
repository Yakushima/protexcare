<?php
require_once('dbux.php');

$o = new product();
?>
<html>

<head>
  <title><?php echo $o->basenm(); ?> record insertion</title>
</head>

<body>

<?php

$o->gen_insert();
// peek_at("product");

?>

</body>
</html>