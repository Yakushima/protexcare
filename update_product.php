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

// A complication here: the Excluded_countries field is updated, but
// we also need to update prod_exc. Same for insert_product.php.
// Subclass in dbux.php?

?>

</body>
</html>
