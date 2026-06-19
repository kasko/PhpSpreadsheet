<?php

namespace PhpOffice\PhpSpreadsheet\Calculation;

class Kasko
{
    /**
     * Casting num native function for php 8.0
     * Problem described here: https://github.com/PHPOffice/PhpSpreadsheet/issues/1789
     * It was fixed by just adding validation, but our templates are full of formulas like:
     * =ROUND('1.111'; 0)
     *
     * Other possible functions that we are not using, but might break:
     *
     * MathTrig:
     * ABS, ACOS, ACOSH, ASIN, ASINH, ATAN, ATANH,
     * COS, COSH, DEGREES (rad2deg), EXP, LN (log), LOG10,
     * RADIANS (deg2rad), REPT (str_repeat), SIN, SINH, SQRT, TAN, TANH.
     *
     * TextData function (REPT) is also affected.
     */
    public static function ROUND($num, $precision)
    {
        $num       = Functions::flattenSingleValue($num);
        $precision = Functions::flattenSingleValue($precision);

        // php 8.4 rewrote round() and removed its internal "pre-rounding" step.
        // for a value whose ieee-754 representation sits just below a .5 boundary
        // because of accumulated float error (e.g. 366.49999999999977, which the
        // spreadsheet math intends as 366.5) pre-8.4 round() snapped it up to 367,
        // while php 8.4+ rounds down to 366. that silently drifted insurer pricing
        // output by a cent (e.g. visana content_insurance_discount_on_tax).
        // to keep excel ROUND output backward-compatible / insurer-signed-off and
        // independent of the php version we run on, we reproduce the pre-8.4
        // _php_math_round algorithm here (pre-round to absorb representation error,
        // then round half away from zero) instead of calling the native round().
        // this is deliberately ROUND-only: MROUND / ROUNDUP / ROUNDDOWN / ROUNDBAHT
        // and every other function keep using the native php behaviour.
        return self::roundPre84((float) $num, (int) $precision);
    }

    // faithful port of pre-8.4 php-src _php_math_round (ext/standard/math.c),
    // default mode = round half away from zero. kept private and used only by
    // ROUND() above so no other calculation is affected.
    private static function roundPre84(float $value, int $places): float
    {
        if (!is_finite($value) || $value == 0.0) {
            return $value;
        }

        // number of significant decimal places we can trust before float noise.
        $precisionPlaces = 14 - (int) floor(log10(abs($value)));

        $f1 = 10.0 ** (float) abs($places);

        if ($precisionPlaces > $places && $precisionPlaces - $places < 15) {
            // pre-round at the trustworthy precision first to absorb the
            // representation error, then round down to the requested precision.
            $f2 = 10.0 ** (float) abs($precisionPlaces);
            $tmpValue = ($precisionPlaces >= 0) ? $value * $f2 : $value / $f2;
            $tmpValue = self::roundHelperPre84($tmpValue);

            $usePrecision = $precisionPlaces - $places;
            $usePrecision = ($usePrecision < -15) ? -15 : (($usePrecision > 15) ? 15 : $usePrecision);

            $f2 = 10.0 ** (float) abs($usePrecision);
            $tmpValue = ($usePrecision >= 0) ? $tmpValue / $f2 : $tmpValue * $f2;
            $tmpValue = self::roundHelperPre84($tmpValue);
        } else {
            $tmpValue = ($places >= 0) ? $value * $f1 : $value / $f1;
            // values >= 1e15 have no fractional part left to round.
            if (abs($tmpValue) >= 1e15) {
                return $value;
            }
            $tmpValue = self::roundHelperPre84($tmpValue);
        }

        if (abs($places) < 23) {
            $tmpValue = ($places > 0) ? $tmpValue / $f1 : $tmpValue * $f1;
        } else {
            $str = sprintf('%15fe%d', $tmpValue, -$places);
            $tmpValue = (float) $str;
            if (!is_finite($tmpValue) || $tmpValue == 0.0) {
                $tmpValue = $value;
            }
        }

        return $tmpValue;
    }

    // pre-8.4 php_round_helper for the default (half away from zero) mode.
    private static function roundHelperPre84(float $value): float
    {
        if ($value >= 0.0) {
            $tmpValue = floor($value + 0.5);
            if (($tmpValue - $value) > 0.5) {
                $tmpValue -= 1.0;
            }
        } else {
            $tmpValue = ceil($value - 0.5);
            if (($value - $tmpValue) > 0.5) {
                $tmpValue += 1.0;
            }
        }

        return $tmpValue;
    }

    /**
     * BC for our broken kasko insurer templates
     * This is obvious bug that was fixed: https://github.com/PHPOffice/PhpSpreadsheet/issues/2066
     */
    public static function INDEX($arrayValues, $rowNum = 0, $columnNum = 0)
    {

        if (Functions::NA() === Functions::flattenSingleValue($rowNum)) {
            return $arrayValues;
        }

        return LookupRef::INDEX($arrayValues, $rowNum, $columnNum);
    }
}
