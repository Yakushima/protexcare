<html>

<head>
  <title>countries</title>
</head>

<body>
<?php
// (C) Michael Turner. All rights reserved.

require_once("dbux.php");

$IMGEuropeInclusions = "Albania, Andorra, Armenia, Austria, Azerbaijan, Belarus, Belgium, Bosnia and Herzegovina, Bulgaria, Canary Islands, Channel Islands, Croatia, Cyprus, Czech Republic, Canary Islands, Channel Islands, Croatia, Cyprus, Czech Republic, Denmark, Estonia, Finland, France, Georgia, Germany, Gibraltar, Greece, Greenland, Holland, Hungary, Iceland, Ireland, Italy, Jersey, Kazakhstan, Kosovo, Kyrgyzstan, Latvia, Liechtenstein, Lithuania, Luxembourg, Macedonia, Madeira, Malta, Moldova, Monaco, Montenegro, Netherlands, Norway, Poland, Portugal, Romania, Russian Federation, San Marino, Serbia, Slovak Republic, Slovenia, Spain, Sweden, Switzerland, Tajikistan, Turkey, Turkmenistan, Ukraine, United Kingdom, Uzbekistan,Vatican";

$raw_list = explode(",",$IMGEuropeInclusions);
// print_r($raw_list,false);

$list = [];

foreach($raw_list as $raw_c){
	$c = trim($raw_c);
//	p($c);
	$list[] = $c;
}

print_r($list,false);


p("start");
$Exclusions = [];
$q = "SELECT * FROM country";
$s = doPDOquery($q,[]);
while ($r = $s->fetch()) {
	$c = $r["NAME"];
	if (in_array($c,$list))
		continue;
	$Exclusions[] = $c;
	p("Excluding ".$c);
}
p("end");

p(implode(",",$Exclusions));

?>
</body>
</html>
