<?php
// (C) Michael Turner. All rights reserved.

$host = "localhost";  // TBD: development only
$user = "root";       // TBD: security issue here
$pass = "";           // TBD: serious security issue here
$charset = 'utf8mb4'; // TBD: see note on this in PHPdelusions tutorial

$db = "protexplan";  // TBD: Alex wants a different name; see FB chat

$dsn = "mysql:host=$host;dbname=$db;charset=$charset"; // TBD: charset important, see tutorial

// TBD: learn what these options mean!
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => true,       // trying https://stackoverflow.com/questions/17414556/load-data-infile-and-unbuffered-queries-error
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true  // see above
];


// TBD: https://www.php.net/manual/en/pdo.connections.php - making connections persistent
//   (Unfortunately, doesn't say how to get the handle when moving to another page)
?>
