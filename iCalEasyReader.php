<?php

/**
 * iCalEasyReader is an easy to understood class, loads a "ics" format string and returns an array with the traditional iCal fields
 *
 * @category	Parser
 * @author		Matias Perrone <matias.perrone at gmail dot com>
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 * @version		2.0.1
 * @param	string	$data	ics file string content
 * @param	array|false	$data $makeItEasy	the idea is to convert this "keys" into the "values", converting the DATE and DATE-TIME values to the respective DateTime type of PHP, also all the keys are lowercased
 * @return	array|false
 */
class iCalEasyReader
{
	protected $ical = null;

	public function &load(string $data, bool $ignoreNonStandardFields = true)
	{
		$this->ical = false;
		$regex_opt = 'mid';

		$this->concatLineContinuations($data);
		$lines = $this->splitLines($data);

		// Delete empty ones
		$lines = array_values(array_filter($lines));
		$last = count($lines);

		// First and last items
		$first = 0;
		$last = count($lines) - 1;

		$beginExists = mb_ereg_match('^BEGIN:VCALENDAR', $lines[$first] ?? '', $regex_opt);
		$endExists = mb_ereg_match('^END:VCALENDAR', $lines[$last] ?? '', $regex_opt);

		// If the first line is not the begin or the last is the end of calendar, look for the end and/or the beginning.
		if (!$beginExists or !$endExists) {
			$first = $beginExists ? $first : null;
			$last = $endExists ? $last : null;
			foreach ($lines as $i => &$line) {
				if (is_null($first) and mb_ereg_match('^BEGIN:VCALENDAR', $line, $regex_opt)) {
					$first = $i;
				}

				if (is_null($last) and mb_ereg_match('^END:VCALENDAR', $line, $regex_opt)) {
					$last = $i;
					break;
				}
			}
		}

		// If not malformed => process
		if (!is_null($first) and !is_null($last)) {
			$lines = array_values(array_slice($lines, $first + 1, ($last - $first - 1), true));
			$this->processCalendar($lines, $ignoreNonStandardFields);
		}

		return $this->ical;
	}

	protected function &concatLineContinuations(string &$data)
	{
		$data = preg_replace("/\r\n( |\t)/m", '', $data);
		return $data;
	}

	protected function convertCaracters(string &$data)
	{
		$chars = mb_str_split($data);
		for ($ipos = 1; $ipos < count($chars); $ipos++) {
			$clean = false;
			switch ($chars[$ipos - 1]) {
				case '^':
					switch ($chars[$ipos]) {
						case 'n':
							$chars[$ipos - 1] = "\n";
							$clean = true;
							break;
						case '\'':
							$chars[$ipos - 1] = '"';
							$clean = true;
							break;
						case '^':
							break;
					}
					break;
				case '\\':
					switch ($chars[$ipos]) {
						case 'n':
							$chars[$ipos - 1] = "\n";
							$clean = true;
							break;
						case 't':
							$chars[$ipos - 1] = "\t";
							$clean = true;
							break;
						case ',':
						case ';':
							$chars[$ipos - 1] = $chars[$ipos];
							$clean = true;
							break;
					}
					break;
			}
			if ($clean) {
				$chars[$ipos] = '';
				$ipos++;
			}
		}
		$data = implode($chars);
		return $data;
	}

	protected function splitLines(string &$data)
	{
		return mb_split('\r\n', $data);
	}

	protected function addType(&$value, $item)
	{
		$type = explode('=', $item);

		if (count($type) > 1 and $type[0] == 'VALUE')
			$value['TYPE'] = $type[1];
		else
			$value[$type[0]] = $type[1];

		return $value;
	}

	protected function addItem(array &$current, string &$line)
	{
		$item = explode(':', $line, 2);
		array_walk($item, [$this, 'convertCaracters']);

		if (!array_key_exists(1, $item)) {
			trigger_error("Unexpected Line error. Possible Corruption. Line " . strlen($line) . ":" . PHP_EOL . $line . PHP_EOL, E_USER_NOTICE);
			return;
		}

		$key = $item[0];
		$value = $item[1] ?? null;

		// return ['key' => $key, 'value' => $value];

		$subitem = explode(';', $key, 2);
		if (count($subitem) > 1) {
			$key = $subitem[0];
			$value = ['VALUE' => $value];
			if (strpos($subitem[1], ";") !== false)
				$value += $this->processMultivalue($subitem[1]);
			else
				$this->addType($value, $subitem[1]);
		}

		// Multi value
		if (is_string($value)) {
			$this->processMultivalue($value);
		}

		$current[$key] = $value;
	}

	protected function processMultivalue(&$value)
	{
		$z = explode(';', $value);
		if (count($z) > 1) {
			$value = [];
			foreach ($z as &$v) {
				$t = explode('=', $v);
				$value[$t[0]] = $t[count($t) - 1];
			}
		}
		unset($z);
		return $value;
	}

	protected function ignoreLine($line, bool $ignoreNonStandardField)
	{
		$isNonStandard = substr($line, 0, 2) == 'X-';
		$ignore = ($isNonStandard and $ignoreNonStandardField) or trim($line) == '';
		return $ignore;
	}

	protected function processCalendar(array &$lines, bool $ignoreNonStandardFields)
	{
		$regex_opt = 'mid';
		$this->ical = [];
		$level = 0;
		$current = [&$this->ical];

		// Join line continuations first
		foreach ($lines as $line) {
			// There are cases like "ATTENDEE" that may take several lines.
			if ($this->ignoreLine($line, $ignoreNonStandardFields)) {
				continue;
			}

			$pattern = '^(BEGIN|END)\:(.+)$'; // (VALARM|VTODO|VJOURNAL|VEVENT|VFREEBUSY|VCALENDAR|DAYLIGHT|VTIMEZONE|STANDARD|VAVAILABILITY)
			mb_ereg_search_init($line);
			// $section
			// 0 => BEGIN:VEVENT
			// 1 => BEGIN
			// 2 => VEVENT
			$section = mb_ereg_search_regs($pattern, $regex_opt);
			if (!$section) {
				$this->addItem($current[$level], $line);
			} else {
				// BEGIN
				if ($section[1] === 'BEGIN') {
					$name = $section[2];

					// If section not exists => Create
					if (!isset($current[$level][$name])) {
						$current[$level][$name] = [];
					}

					// Get index of the new item
					$last = count($current[$level][$name]);

					// Initialize new item
					$current[$level][$name][$last] = [];

					// Set the new current section
					$current[$level + 1] = &$current[$level][$name][$last];

					// Increase current level
					$level++;
				}
				// END
				else {
					$level--;
				}
			}
		}
	}
}