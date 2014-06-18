<?php
// YahooFinanceStockHistoryLoader.class.php
// Copyright (c) 2011-2014 Ronald B. Cemer
// All rights reserved.
// This software is released under the BSD license.
// Please see the accompanying LICENSE.txt for details.

/**
 * <p>Loads historical stock quotes from the Yahoo Finance CSV web service, or from a CSV file.</p>
 *
 * <p>CSV columns are: <tt>Date,Open,High,Low,Close,Volume,Adj Close</tt></p>
 */
class YahooFinanceStockHistoryLoader {
	/**
	 * Load historical stock quotes from the Yahoo Finance CSV web service.
	 * @param string $symbol The stock symbol.
	 * @param string $beginDate The begin date, in any format supported by strtotime().
	 * @param string $endDate The end date, in any format supported by strtotime().
	 * @param boolean $reverseOrder By default, the quotes come back from the Yahoo web service
	 * in reverse chronological order (newest first). If this is set to true, they will be
	 * reversed so that they will be returned in oldest-first order.
	 * Optional.  Defaults to false.
	 */
	public static function loadFromWebService
		($symbol, $beginDate, $endDate, $reverseOrder = false) {

		if (($bt = strtotime($beginDate)) === false) {
			throw new Exception(sprintf('Unable to parse begin date: %s', $beginDate));
		}
		$bt = getdate($bt);
		if (($et = strtotime($endDate)) === false) {
			throw new Exception(sprintf('Unable to parse end date: %s', $endDate));
		}
		$et = getdate($et);
		return self::loadFromFileOrURL(
			sprintf(
				'http://ichart.finance.yahoo.com/table.csv?s=%s&a=%02d&b=%02d&c=%04d&d=%02d&e=%02d&f=%04d&g=d&ignore=.csv',
				urlencode($symbol),
				$bt['mon'],
				$bt['mday'],
				$bt['year'],
				$et['mon'],
				$et['mday'],
				$et['year']
			),
			$reverseOrder
		);
	}

	/**
	 * Load historical stock quotes from a file or URL.
	 * @param string $filenameOrURL The filename or URL for the CSV historical stock quotes data.
	 * @param boolean $reverseOrder By default, the quotes come back from the Yahoo web service
	 * in reverse chronological order (newest first). If this is set to true, they will be
	 * reversed so that they will be returned in oldest-first order.
	 * Optional.  Defaults to false.
	 */
	public static function loadFromFileOrURL($filenameOrURL, $reverseOrder = false) {
		// Load quotes into an array of data objects.
		$quotes = array();
		$fp = fopen($filenameOrURL, 'r');
		$header = fgetcsv($fp);
		while (!feof($fp)) {
			$row = fgetcsv($fp);
			$quote = new stdClass();
			$quote->date = date('Y-m-d', strtotime($row[0]));
			$quote->open = (double)$row[1];
			$quote->high = (double)$row[2];
			$quote->low = (double)$row[3];
			$quote->close = (double)$row[4];
			$quote->volume = (double)$row[5];
			$quote->adjClose = (double)$row[6];
			$quotes[] = $quote;
		}
		fclose($fp);
		if ($reverseOrder) {
			$quotes = array_reverse($quotes);
		}
		return $quotes;
	}
}
