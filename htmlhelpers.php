
<?php
// (C) Michael Turner. All rights reserved.

$ilevel = 0; // indentation level for the generated HTML lines

// TBD: echo with multiple params faster than string concat with "."
// TBD: probably some faster way to indent as well
// TBD: PHP_EOL everywhere for "\n"
// TBD: input() seems to leave a trailing ">"

function eol()		{ echo PHP_EOL; }
function tagopts($t,$o)	{ echo "<" . $t . " " . $o . ">"; eol(); }
function enclose($t,$x) { echo "<" . $t . ">" . $x. "</" . $t .">"; }
function blanks($n)     { for ($i = 0; $i < $n; ++$i) echo " "; }
function indent()	{ global $ilevel; ++$ilevel; }
function tabout()       { global $ilevel; blanks($ilevel); }
function dedent()	{ global $ilevel; --$ilevel; }

function table__($opts) { tabout(); tagopts("table",$opts); indent(); }
function __table()      { dedent(); tabout(); echo "</table>"; eol(); }
function td($d)         { tabout(); enclose("td", $d); }
function th($d)         { tabout(); enclose("th", $d); }
function tdopts__($o)	{ tabout(); tagopts("td",$o); }
function __td()		{ echo "</td>"; }
function tr__()         { tabout(); echo "<tr>"; eol(); indent(); }
function __tr()         { dedent(); tabout();  echo "</tr>"; eol(); }
function p($s)		{ tabout(); enclose("p",$s); eol(); }

function h1($s)		{ tabout(); enclose("h1",$s); eol(); }
function h2($s)		{ tabout(); enclose("h2",$s); eol(); }
function h3($s)		{ tabout(); enclose("h3",$s); eol(); }

function form__($a)	{ tabout(); tagopts("form", $a); indent(); }
function __form()	{ dedent(); tabout(); echo "</form>"; eol(); }

function input($a)	{ tabout(); tagopts("input", $a); }

function emit($s)	{ echo $s; }
function emitln($s)	{ tabout(); emit($s); eol(); }

?>

