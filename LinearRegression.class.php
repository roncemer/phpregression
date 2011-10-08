<?php
// LinearRegression.class.php
// Copyright (c) 2011 Ronald B. Cemer
// All rights reserved.
// This software is released under the BSD license.
// Please see the accompanying LICENSE.txt for details.

/**
 * <p>Performs fast, least-squares linear regression on a set of [x,y] data points to arrive at a
 * pair of [slope,intercept] line coefficents.</p>
 *
 * <p>The algorithm comprises an iterative binary search to arrive at increasingly accurate values
 * until the desired accuracy is achieved.</p>
 */
class LinearRegression {
	/**
	 * Perform fast, least-squares linear regression.
	 * @param array $xarr An array of $npoints floating-point values, comprising the X values of
	 * the data points.
	 * @param array $yarr An array of $npoints floating-point values, comprising the Y values of
	 * the data points.
	 * @param int $npoints The number of data points.
	 * @param double $slopeAccuracy The maximum allowed error to allow in the calculated slope.
	 * The smaller this value, the more iterations will be required to achieve the desired accuracy.
	 * Optional.  Defaults to 0.001.
	 * @param double $interceptAccuracy The maximum allowed error to allow in the calculated
	 * intercept.
	 * The smaller this value, the more iterations will be required to achieve the desired accuracy.
	 * Optional.  Defaults to 0.001.
	 * @return array An array containing the results, as follows:
	 * <blockquote>
	 * Element [0] = slope (double)
	 * Element [1] = intercept (double)
	 * Element [2] = number of iterations which were required to arrive at the desired
	 *               accuracy (int)
	 * Element [3] = mean squared error (MSE) of the error residuals between the projected
	 *               line and the real y values (double)
	 * </blockquote>
	 */
	public static function calculate
		(&$xarr, &$yarr, $npoints, $slopeAccuracy = 0.001, $interceptAccuracy = 0.001) {

		if ($npoints < 2) {
			throw new Exception('At least two data points are required for linear regression');
		}

		$miny = $maxy = $yarr[0];
		for ($i = 1; $i < $npoints; $i++) {
			$miny = min($miny, $yarr[$i]);
			$maxy = max($maxy, $yarr[$i]);
		}

		$slope = 0.0;
		$intercept = 0.0;
		$iterations = 0;

		do {
			$prevSlope = $slope;
			$prevIntercept = $intercept;
			$iterations++;

			// Using current intercept, solve slope using a binary search.
			$maxslope = abs($maxy-$miny);
			$minslope = -$maxslope;
			$minslopeErr = self::calcSquaredError($xarr, $yarr, $npoints, $minslope, $intercept);
			$maxslopeErr = self::calcSquaredError($xarr, $yarr, $npoints, $maxslope, $intercept);
			while (($maxslope-$minslope) > $slopeAccuracy) {
				if ($maxslopeErr < $minslopeErr) {
					$minslope = ($maxslope+$minslope)/2.0;
					$minslopeErr = self::calcSquaredError($xarr, $yarr, $npoints, $minslope, $intercept);
				} else if ($minslopeErr < $maxslopeErr) {
					$maxslope = ($maxslope+$minslope)/2.0;
					$maxslopeErr = self::calcSquaredError($xarr, $yarr, $npoints, $maxslope, $intercept);
				} else {
					break;
				}
			}
			$slope = ($minslope+$maxslope)/2.0;

			// Using current slope, solve intercept using a binary search.
			$minintercept = $miny;
			$maxintercept = $maxy;
			$mininterceptErr = self::calcSquaredError($xarr, $yarr, $npoints, $slope, $minintercept);
			$maxinterceptErr = self::calcSquaredError($xarr, $yarr, $npoints, $slope, $maxintercept);
			while (($maxintercept-$minintercept) > 1) {
				if ($maxinterceptErr < $mininterceptErr) {
					$minintercept = ($maxintercept+$minintercept)/2.0;
					$mininterceptErr = self::calcSquaredError($xarr, $yarr, $npoints, $slope, $minintercept);
				} else if ($mininterceptErr < $maxinterceptErr) {
					$maxintercept = ($maxintercept+$minintercept)/2.0;
					$maxinterceptErr = self::calcSquaredError($xarr, $yarr, $npoints, $slope, $maxintercept);
				} else {
					break;
				}
			}
			$intercept = (int)(($minintercept+$maxintercept)/2.0);
		} while ((abs($slope-$prevSlope) > $slopeAccuracy) || (abs($intercept-$prevIntercept) > $interceptAccuracy));

		$squaredError = self::calcSquaredError($xarr, $yarr, $npoints, $slope, $intercept);
		return array($slope, $intercept, $iterations, ($squaredError/(double)$npoints));
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
	public static function calcSquaredError(&$xarr, &$yarr, $npoints, $slope, $intercept) {
		$sqrerr = 0.0;
		for ($i = 0; $i < $npoints; $i++) {
			$predy = ($slope*$xarr[$i])+$intercept;
			$err = $predy-$yarr[$i];
			$sqrerr += ($err*$err);
		}
		return $sqrerr;
	}
}
