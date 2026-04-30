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
     *
     * @param mixed $num
     * @param mixed $precision
     *
     * @return array|float|string
     */
    public static function ROUND($num, $precision)
    {
        if (is_array($num) || is_array($precision)) {
            return MathTrig\Round::round($num, $precision);
        }

        $num       = Functions::flattenSingleValue($num);
        $precision = Functions::flattenSingleValue($precision);

        return round((float) $num, (int) $precision);
    }

    /**
     * BC for our broken kasko insurer templates
     * This is obvious bug that was fixed: https://github.com/PHPOffice/PhpSpreadsheet/issues/2066
     *
     * @param mixed $arrayValues
     * @param mixed $rowNum
     * @param mixed $columnNum
     *
     * @return mixed
     */
    public static function INDEX($arrayValues, $rowNum = 0, $columnNum = null)
    {
        if (Functions::NA() === Functions::flattenSingleValue($rowNum)) {
            return $arrayValues;
        }

        return LookupRef\Matrix::index($arrayValues, $rowNum, $columnNum);
    }
}
