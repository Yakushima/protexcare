<html>
<head>
<title>?lookup</title>
<style>
table, th, td {
  border: 1px solid black;
}
</style>
</head>

<body>

<?php
// (C) Michael Turner. All rights reserved.

require_once "htmlhelpers.php";
require_once "pdohelpers.php";
require_once "config.php";

// TBD: "Despite a widespread delusion, you should never catch errors to report them.
// A module (like a database layer) should not report its errors.....
// do not catch PDO exceptions to report them. Instead, configure your server properly ...
// [See that section about php.ini settings]
// -- https://phpdelusions.net/pdo#comments

try {
  $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    p("Error!: " . $e->getMessage());       // <-- insecure?
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

p(ini_get('upload_tmp_dir'));

h3("Plans");

$q = "SELECT p.ID, provider.NAME as Provider_name, product.NAME as Product_name, p.GENDER, p.TYPE"
   . " FROM plan p"
   . "  INNER JOIN provider ON p.provider=provider.ID"
   . "  INNER JOIN product ON p.prod_id=product.ID"
   ;

p($q);		// trace

$stmt = doPDOquery($q,[]);
if ($stmt === NULL) {
   p("Couldn't execute query");
}
else
   dumptable(["ID","Provider_name", "Product_name", "GENDER", "TYPE"],$stmt);

$pdo = null;

?>

<form action="populate.php" method="post">
<p>Plan table to populate from the scratchpad: <input type="number" name="name"><br>
<input type="submit">
</form>


<form action="upload.php" method="post" enctype="multipart/form-data">
  Select CSV file for database population: 
  <input type="file" name="fileToUpload" id="fileToUpload">
  <input type="submit" value="Upload Image" name="submit">
</form>

</body>
</html>
