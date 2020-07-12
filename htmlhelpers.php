
<?php

// (C) 2020 Michael Turner. All rights reserved.

$ilevel = 0; // indentation level for the generated HTML lines

// Generating all the HTML procedurally helps in two ways:
//
//  Clarity: interspersing HTML in PHP makes the code harder to read.
//	This way, everything can be written in PHP.
//
//  Portability: it may be easier to automatically translate the
//	the PHP scripting to a language more appropriate for
//	phone apps. Everything will be in PHP.
//
// Downside: a little slower. But right now, performance is bound
//	up in MySQL queries anyway. And the app language may
//	be one that supports macro expansion or inlining of its
//	HTML helpers -- so no loss of performance.
//
// TBD: it may make sense to give these functions an "H" namespace.
//	Giving these functions names corresponding to the HTML
//	tags could result in name clashes, eventually if not
//	sooner.

// TBD: echo with multiple params faster than string concat with "."
// TBD: probably some faster way to indent as well, e.g, substr of a
//	string of blanks.
// TBD: PHP_EOL everywhere for "\n"
// TBD: input() seems to leave a trailing ">"

function eol()		{ echo PHP_EOL; }
function open($t)	{ echo "<" . $t . ">"; }
function close($t)	{ echo "</". $t . ">"; }
function tagopts($t,$o)	{ echo "<" . $t . " " . $o . ">"; eol(); }
function enclose($t,$x) { echo "<" . $t . ">" . $x. "</" . $t .">"; }
function blanks($n)     { for ($i = 0; $i < $n; ++$i) echo " "; }
function indent()	{ global $ilevel; ++$ilevel; }
function tabout()       { global $ilevel; blanks($ilevel); }
function dedent()	{ global $ilevel; --$ilevel; }

function table__($opts) { tabout(); tagopts("table",$opts); eol(); indent(); }
function __table()      { dedent(); tabout(); close("table"); eol(); }
function td($d)         { tabout(); enclose("td", $d); eol(); }
function th($d)         { tabout(); enclose("th", $d); eol(); }
function tdopts__($o)	{ tabout(); tagopts("td",$o); eol(); indent(); }
function td__()		{ tabout(); open("td"); eol(); indent(); }
function __td()		{ dedent(); tabout(); close("td"); eol(); }
function tr__()         { tabout(); open("tr"); eol(); indent(); }
function __tr()         { dedent(); tabout(); close("tr"); eol(); }


function h1($s)		{ tabout(); enclose("h1",$s); eol(); }
function h2($s)		{ tabout(); enclose("h2",$s); eol(); }
function h3($s)		{ tabout(); enclose("h3",$s); eol(); }

function p($s)		{ tabout(); enclose("p",$s); eol(); }
function br()		{ open("br"); }

function bold($s)	{ enclose("b",$s); }
function italic($s)	{ enclose("i",$s); }

function form__($a)	{ tabout(); tagopts("form", $a); eol(); indent(); }
function __form()	{ dedent(); tabout(); close("form"); eol(); }
function postform__($a)	{ form__('method="post" action="'.$a.'"'); }
function button($l)	{ enclose("button",$l); }

function input($a)	{ tabout(); tagopts("input", $a); }

function emit($s)	{ echo $s; }
function emitln($s)	{ tabout(); emit($s); eol(); }

function html__($a)	{ tabout(); tagopts("html", $a); eol(); indent(); }
function head__()	{ tabout(); open("head"); eol(); indent(); }
function title($a)	{ tabout(); enclose("title",$a); eol(); }
function style()	{ require_once("style.php"); }
function __head()	{ tabout(); close("head"); eol(); }
function body__()	{ tabout(); open("body"); eol(); }
function __body()	{ dedent(); tabout(); close("body"); eol(); }
function __html()	{ dedent(); close("html"); eol(); }

?>

