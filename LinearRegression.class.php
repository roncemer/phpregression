<?php
// LinearRegression.class.php
// Copyright (c) 2011-2014 Ronald B. Cemer
// All rights reserved.
// This software is released under the BSD license.
// Please see the accompanying LICENSE.txt for details.

/**
 * <p>Performs fast linear regression on a set of [x,y] data points to arrive at a
 * pair of [slope,intercept] line coefficents.</p>
 */
class LinearRegression {
	/**
	 * Perform linear regression.
	 * @param array $xarr An array of $npoints floating-point values, comprising the X values of
	 * the data points.
	 * @param array $yarr An array of $npoints floating-point values, comprising the Y values of
	 * the data points.
	 * @param int $npoints The number of data points.
	 * @return object An object with slope and intercept floating point attributes.
	 */
	public static function calculate($xarr, $yarr, $npoints) {
		if ($npoints < 2) {
			throw new Exception('At least two data points are required for linear regression');
		}

		$sumx = 0.0;
		$sumy = 0.0;
		$sumxy = 0.0;
		$sumxx = 0.0;
		$sumyy = 0.0;

		for ($i = 0; $i < $npoints; $i++) {
			$x = $xarr[$i];
			$y = $yarr[$i];

			$sumx += $x;
			$sumy += $y;
			$sumxy += $x*$y;
			$sumxx += $x*$x;
			$sumyy += $y*$y;
		}

		$denominator = (($npoints*$sumxx)-($sumx*$sumx));

		$result = new stdClass();
		$result->slope = (($npoints*$sumxy)-($sumx*$sumy)) / $denominator;
		$result->intercept = (($sumy*$sumxx)-($sumx*$sumxy)) / $denominator;

		return $result;
	}

	/**
	 * Calculate the sum of the squared error residuals between a set of [x,y] data points and a
	 * line expressed using the line formula <tt>(y = mx + b)</tt>.
	 * @param array $xarr An array of $npoints floating-point values, comprising the X values of
	 * the data points.
	 * @param array $yarr An array of $npoints floating-point values, comprising the Y values of
	 * the data points.
	 * @param int $npoints The number of data points.
	 * @param double $slope The slope (m) value for the line formula <tt>(y = mx + b)</tt>.
	 * @param double $intercept The intercept (b) value for the line formula <tt>(y = mx + b)</tt>.
	 * @return double The sum of the squared error residuals.
	 */
	public static function calcSquaredError($xarr, $yarr, $npoints, $slope, $intercept) {
		$sqrerr = 0.0;
		for ($i = 0; $i < $npoints; $i++) {
			$predy = ($slope*$xarr[$i])+$intercept;
			$err = $predy-$yarr[$i];
			$sqrerr += ($err*$err);
		}
		return $sqrerr;
	}
}
