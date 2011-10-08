<?php
// linearRegressionDemo.php
// Copyright (c) 2011 Ronald B. Cemer
// All rights reserved.
// This software is released under the BSD license.
// Please see the accompanying LICENSE.txt for details.

include dirname(__FILE__).'/LinearRegression.class.php';
include dirname(__FILE__).'/YahooFinanceStockHistoryLoader.class.php';

$DEFAULT_END_DATE = date('Y-m-d', strtotime('yesterday'));
$DEFAULT_BEGIN_DATE = date('Y-m-d', strtotime($DEFAULT_END_DATE.' -5 years'));

function usage() {
	global $argv, $DEFAULT_BEGIN_DATE, $DEFAULT_END_DATE;

	fputs(
		STDERR,
		"Usage: php ".basename($argv[0])." [options] <symbol> [<symbol> ...]]\n".
		"    symbol  - Stock symbols(s) to process.\n".
		"Perform regression analysis on one or more stock symbol(s).\n".
		"Options:\n".
		"    -bd YYYY-MM-DD   Specify begin date.  Optional.  Defaults to $DEFAULT_BEGIN_DATE.\n".
		"    -ed YYYY-MM-DD   Specify end date.  Optional.  Defaults to yesterday ($DEFAULT_END_DATE).\n"
	);
}

// Load quotes into an array of data objects.
$beginDate = $DEFAULT_BEGIN_DATE;
$endDate = $DEFAULT_END_DATE;
$symbols = array();

for ($ai = 1; $ai < $argc; $ai++) {
	$arg = $argv[$ai];
	if ( (strlen($arg) > 0) && ($arg[0] == '-') ) {
		switch ($arg) {
		case '-bd':
			$ai++;
			if ($ai >= $argc) {
				usage();
				exit(1);
			}
			if (($tm = strtotime($argv[$ai])) === false) {
				fputs(STDERR, "Invalid begin date.\n");
				usage();
				exit(1);
			}
			$beginDate = date('Y-m-d', $tm);
			break;
		case '-ed':
			$ai++;
			if ($ai >= $argc) {
				usage();
				exit(1);
			}
			if (($tm = strtotime($argv[$ai])) === false) {
				fputs(STDERR, "Invalid end date.\n");
				usage();
				exit(1);
			}
			$endDate = date('Y-m-d', $tm);
			break;
		default:
			fprintf(STDERR, "Unrecognized command line switch: %s.\n", $arg);
			usage();
			exit(1);
		}
		continue;
	}	// if ( (strlen($arg) > 0) && ($arg[0] == '-') )
	$symbols[] = strtoupper(trim($arg));
}

sscanf($beginDate, '%04d-%02d-%02d', &$y, &$m, &$d);
$beginDateJul = gregoriantojd($m, $d, $y);
sscanf($endDate, '%04d-%02d-%02d', &$y, &$m, &$d);
$endDateJul = gregoriantojd($m, $d, $y);
$ndays = ($endDateJul-$beginDateJul)+1;
if ($ndays <= 0) {
	fputs(STDERR, "Begin date cannot be later than end date.\n");
	usage();
	exit(1);
}

if (empty($symbols)) {
	usage();
	exit(1);
}

foreach ($symbols as $symbol) {
	$quotes = YahooFinanceStockHistoryLoader::loadFromWebService($symbol, $beginDate, $endDate, true);

	$graphFilename = strtolower($symbol).'_'.$beginDate.'_'.$endDate.'_regression.png';
	$graphWidth = 800;
	$graphHeight = 400;

	// Convert to an array of x values and an array of y values.
	// $xarr is the day number, with day 0 being the oldest day.
	// $yarr is a price which occurred on that day. For each day, $yarr will contain one entry
	// for the low price, and one entry for the high price.
	// Calculate the minimum and maximum prices, and the number of days' data we have.
	$xarr = array();
	$yarr = array();
	$minPrice = false;
	$maxPrice = false;
	$npoints = 0;
	foreach ($quotes as $quote) {
		sscanf($quote->date, '%04d-%02d-%02d', &$y, &$m, &$d);
		$daysoffset = gregoriantojd($m, $d, $y)-$beginDateJul;

		$xarr[$npoints] = (double)$daysoffset;
		$yarr[$npoints] = (double)$quote->low;
		$npoints++;

		$xarr[$npoints] = (double)$daysoffset;
		$yarr[$npoints] = (double)$quote->high;
		$npoints++;

		$minPrice = floor(($minPrice === false) ? $quote->low : min($minPrice, $quote->low));
		$maxPrice = ceil(($maxPrice === false) ? $quote->high : max($maxPrice, $quote->high));
	}

	// Do the linear regression, calculating slope and intercept.
	$result = LinearRegression::calculate($xarr, $yarr, $npoints);
	$slope = $result[0];
	$intercept = $result[1];
	$iterations = $result[2];
	$mse = $result[3];
	$rmsError = sqrt($mse);

	// Update min and max prices so that the graph will scale to include the entire projected line.
	$minPrice = floor(min($minPrice, $intercept));
	$maxPrice = ceil(max($maxPrice, $intercept));
	$yn = $intercept+($slope*(double)($ndays-1));
	$minPrice = floor(min($minPrice, $yn));
	$maxPrice = ceil(max($maxPrice, $yn));

	// Draw the graph.
	$imgmaxy = $graphHeight-1;

	// Calculate scaling factors and y offset.
	$xscale = (double)$graphWidth/(double)$ndays;
	$yscale = (double)$graphHeight/(double)($maxPrice-$minPrice);
	$yoffset = -$minPrice*$yscale;

	// Create the image; allocate colors and locate font.
	$img = imagecreatetruecolor($graphWidth, $graphHeight);
	$backgroundColor = imagecolorallocate($img, 192, 224, 240);
	$quoteColor = imagecolorallocate($img, 255, 0, 0);
	$regressionColor = imagecolorallocate($img, 0, 0, 255);
	$labelColor = imagecolorallocatealpha($img, 0, 0, 0, 32);
	$labelFont = dirname(__FILE__).'/arial.ttf';

	// Fill to background color.
	imagefilledrectangle($img, 0, 0, $graphWidth, $graphHeight, $backgroundColor);

	// Draw lines for actual quotes.
	for ($i = 1, $ni = count($xarr); $i < $ni; $i++) {
		$x1 = (int)((double)$xarr[$i-1]*$xscale);
		$y1 = ($imgmaxy-$yoffset)-(int)((double)$yarr[$i-1]*$yscale);
		$x2 = (int)((double)$xarr[$i]*$xscale);
		$y2 = ($imgmaxy-$yoffset)-(int)((double)$yarr[$i]*$yscale);
		imageline($img, $x1, $y1, $x2, $y2, $quoteColor);
	}

	// Draw line for regression.
	$x1 = 0;
	$y1 = ($imgmaxy-$yoffset)-($intercept*$yscale);
	$x2 = ($ndays-1)*$xscale;
	$y2 = ($imgmaxy-$yoffset)-((($slope*($ndays-1))+$intercept)*$yscale);
	imageline($img, $x1, $y1, $x2, $y2, $regressionColor);

	// Draw symbol label.
	imagefttext($img, 8, 0, 2, 10, $labelColor, $labelFont, strtoupper($symbol));

	// Draw beginning and ending date labels.
	imagefttext($img, 8, 0, 2, $graphHeight/2, $labelColor, $labelFont, $beginDate);

	$bb = imageftbbox(8, 0, $labelFont, $endDate);
	$sw = max($bb[2], $bb[4])-min($bb[0], $bb[6]);
	imagefttext($img, 8, 0, ($graphWidth-3)-$sw, $graphHeight/2, $labelColor, $labelFont, $endDate);

	// Draw min and max price labels.
	$txt = sprintf('$%d.00', $minPrice);
	$bb = imageftbbox(8, 0, $labelFont, $txt);
	$sw = max($bb[2], $bb[4])-min($bb[0], $bb[6]);
	imagefttext($img, 8, 0, ($graphWidth-2)-$sw, $graphHeight-3, $labelColor, $labelFont, $txt);

	$txt = sprintf('$%d.00', $maxPrice);
	$bb = imageftbbox(8, 0, $labelFont, $txt);
	$sw = max($bb[2], $bb[4])-min($bb[0], $bb[6]);
	imagefttext($img, 8, 0, ($graphWidth-2)-$sw, 10, $labelColor, $labelFont, $txt);

	// Draw results labels.
	imagefttext($img, 8, 0, $graphWidth/4, 10, $labelColor, $labelFont, 'Slope: '.$slope);
	imagefttext($img, 8, 0, $graphWidth/4, 20, $labelColor, $labelFont, 'Intercept: '.$intercept);
	imagefttext($img, 8, 0, $graphWidth/4, 30, $labelColor, $labelFont, 'RMS Error: '.$rmsError);

	// Save graph image.
	imagepng($img, $graphFilename);
	imagedestroy($img);

	// Report results.
	echo "symbol: $symbol\nndays: $ndays\nnpoints: $npoints\nslope: $slope\nintercept: $intercept\niterations: $iterations\nmse: $mse\nrms: $rmsError\n";
	echo "$symbol regression analysis is complete.  You can view the results in $graphFilename.\n";
}
