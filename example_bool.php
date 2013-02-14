<?php

require_once("console.php");

$query = $argv[1];
echo Colorize::cyan("'") . $query . Colorize::cyan("'=''") . "\n";

$url = "http://example.com";

$url .= '?' . http_build_query([
	'bar' => $query,
	'static_arg' => 'foo'
]);

$res = file_get_contents($url);
file_put_contents("res.html", $res);

$error = [];
if (preg_match("~mysql_fetch_array\(\): (?P<msg>.*?) in <b>~ms", $res, $error)) {
	echo Colorize::red("Error: ");
	echo $error['msg'] . "\n";
	die(2);

} else if (preg_match("~Another status~", $res)) {
	echo Colorize::magenta("Empty\n");
	die(1);

} else {
	echo Colorize::green("Result\n");
	die(0);
}
