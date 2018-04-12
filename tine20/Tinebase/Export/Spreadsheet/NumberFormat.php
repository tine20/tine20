<?php
/**
 * Tinebase Export PhpSpreadsheet NumberFormat extender
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Export PhpSpreadsheet NumberFormat extender class
 *
 * @package     Tinebase
 * @subpackage    Export
 *
 */
class Tinebase_Export_Spreadsheet_NumberFormat extends \PhpOffice\PhpSpreadsheet\Style\NumberFormat
{
    public static function fillBuiltInFormatCodes()
    {
        //  [MS-OI29500: Microsoft Office Implementation Information for ISO/IEC-29500 Standard Compliance]
        //  18.8.30. numFmt (Number Format)
        //
        //  The ECMA standard defines built-in format IDs
        //      14: "mm-dd-yy"
        //      22: "m/d/yy h:mm"
        //      37: "#,##0 ;(#,##0)"
        //      38: "#,##0 ;[Red](#,##0)"
        //      39: "#,##0.00;(#,##0.00)"
        //      40: "#,##0.00;[Red](#,##0.00)"
        //      47: "mmss.0"
        //      KOR fmt 55: "yyyy-mm-dd"
        //  Excel defines built-in format IDs
        //      14: "m/d/yyyy"
        //      22: "m/d/yyyy h:mm"
        //      37: "#,##0_);(#,##0)"
        //      38: "#,##0_);[Red](#,##0)"
        //      39: "#,##0.00_);(#,##0.00)"
        //      40: "#,##0.00_);[Red](#,##0.00)"
        //      47: "mm:ss.0"
        //      KOR fmt 55: "yyyy/mm/dd"

        // Built-in format codes
        if (is_null(self::$builtInFormats)) {
            self::$builtInFormats = [];

            // General
            self::$builtInFormats[0] = self::FORMAT_GENERAL;
            self::$builtInFormats[1] = '0';
            self::$builtInFormats[2] = '0.00';
            self::$builtInFormats[3] = '#,##0';
            self::$builtInFormats[4] = '#,##0.00';

            self::$builtInFormats[9] = '0%';
            self::$builtInFormats[10] = '0.00%';
            self::$builtInFormats[11] = '0.00E+00';
            self::$builtInFormats[12] = '# ?/?';
            self::$builtInFormats[13] = '# ??/??';
            self::$builtInFormats[14] = 'm/d/yyyy'; // Despite ECMA 'mm-dd-yy';
            self::$builtInFormats[15] = 'd-mmm-yy';
            self::$builtInFormats[16] = 'd-mmm';
            self::$builtInFormats[17] = 'mmm-yy';
            self::$builtInFormats[18] = 'h:mm AM/PM';
            self::$builtInFormats[19] = 'h:mm:ss AM/PM';
            self::$builtInFormats[20] = 'h:mm';
            self::$builtInFormats[21] = 'h:mm:ss';
            self::$builtInFormats[22] = 'm/d/yyyy h:mm'; // Despite ECMA 'm/d/yy h:mm';

            self::$builtInFormats[37] = '#,##0_);(#,##0)'; //  Despite ECMA '#,##0 ;(#,##0)';
            self::$builtInFormats[38] = '#,##0_);[Red](#,##0)'; //  Despite ECMA '#,##0 ;[Red](#,##0)';
            self::$builtInFormats[39] = '#,##0.00_);(#,##0.00)'; //  Despite ECMA '#,##0.00;(#,##0.00)';
            self::$builtInFormats[40] = '#,##0.00_);[Red](#,##0.00)'; //  Despite ECMA '#,##0.00;[Red](#,##0.00)';

            self::$builtInFormats[44] = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
            self::$builtInFormats[45] = 'mm:ss';
            self::$builtInFormats[46] = '[h]:mm:ss';
            self::$builtInFormats[47] = 'mm:ss.0'; //  Despite ECMA 'mmss.0';
            self::$builtInFormats[48] = '##0.0E+0';
            self::$builtInFormats[49] = '@';

            // CHT
            self::$builtInFormats[27] = '[$-404]e/m/d';
            self::$builtInFormats[30] = 'm/d/yy';
            self::$builtInFormats[36] = '[$-404]e/m/d';
            self::$builtInFormats[50] = '[$-404]e/m/d';
            self::$builtInFormats[57] = '[$-404]e/m/d';

            // THA
            self::$builtInFormats[59] = 't0';
            self::$builtInFormats[60] = 't0.00';
            self::$builtInFormats[61] = 't#,##0';
            self::$builtInFormats[62] = 't#,##0.00';
            self::$builtInFormats[67] = 't0%';
            self::$builtInFormats[68] = 't0.00%';
            self::$builtInFormats[69] = 't# ?/?';
            self::$builtInFormats[70] = 't# ??/??';
            
            // our stuff
            static::$builtInFormats[7] = '#,##0.00\ "€";\-#,##0.00\ "€"';

            // Flip array (for faster lookups)
            static::$flippedBuiltInFormats = array_flip(static::$builtInFormats);
        }
    }
}