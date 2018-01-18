<?php
/**
 * Tinebase Export PHPExcel NumberFormat extender
 *
 * @package     Tinebase
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2018-2018 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * Tinebase Export PHPExcel NumberFormat extender class
 *
 * @package     Tinebase
 * @subpackage    Export
 *
 */
class Tinebase_Export_Spreadsheet_NumberFormat extends PHPExcel_Style_NumberFormat
{
    public static function fillBuildInTypes()
    {
        // really? man this is ugly :-/ that is why extensive use of private sucks
        // \PHPExcel_Style_NumberFormat::fillBuiltInFormatCodes copied code
        // Built-in format codes
        if (is_null(static::$_builtInFormats)) {
            static::$_builtInFormats = array();

            // General
            static::$_builtInFormats[0] = PHPExcel_Style_NumberFormat::FORMAT_GENERAL;
            static::$_builtInFormats[1] = '0';
            static::$_builtInFormats[2] = '0.00';
            static::$_builtInFormats[3] = '#,##0';
            static::$_builtInFormats[4] = '#,##0.00';

            static::$_builtInFormats[9] = '0%';
            static::$_builtInFormats[10] = '0.00%';
            static::$_builtInFormats[11] = '0.00E+00';
            static::$_builtInFormats[12] = '# ?/?';
            static::$_builtInFormats[13] = '# ??/??';
            static::$_builtInFormats[14] = 'mm-dd-yy';
            static::$_builtInFormats[15] = 'd-mmm-yy';
            static::$_builtInFormats[16] = 'd-mmm';
            static::$_builtInFormats[17] = 'mmm-yy';
            static::$_builtInFormats[18] = 'h:mm AM/PM';
            static::$_builtInFormats[19] = 'h:mm:ss AM/PM';
            static::$_builtInFormats[20] = 'h:mm';
            static::$_builtInFormats[21] = 'h:mm:ss';
            static::$_builtInFormats[22] = 'm/d/yy h:mm';

            static::$_builtInFormats[37] = '#,##0 ;(#,##0)';
            static::$_builtInFormats[38] = '#,##0 ;[Red](#,##0)';
            static::$_builtInFormats[39] = '#,##0.00;(#,##0.00)';
            static::$_builtInFormats[40] = '#,##0.00;[Red](#,##0.00)';

            static::$_builtInFormats[44] = '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)';
            static::$_builtInFormats[45] = 'mm:ss';
            static::$_builtInFormats[46] = '[h]:mm:ss';
            static::$_builtInFormats[47] = 'mmss.0';
            static::$_builtInFormats[48] = '##0.0E+0';
            static::$_builtInFormats[49] = '@';

            // CHT
            static::$_builtInFormats[27] = '[$-404]e/m/d';
            static::$_builtInFormats[30] = 'm/d/yy';
            static::$_builtInFormats[36] = '[$-404]e/m/d';
            static::$_builtInFormats[50] = '[$-404]e/m/d';
            static::$_builtInFormats[57] = '[$-404]e/m/d';

            // THA
            static::$_builtInFormats[59] = 't0';
            static::$_builtInFormats[60] = 't0.00';
            static::$_builtInFormats[61] = 't#,##0';
            static::$_builtInFormats[62] = 't#,##0.00';
            static::$_builtInFormats[67] = 't0%';
            static::$_builtInFormats[68] = 't0.00%';
            static::$_builtInFormats[69] = 't# ?/?';
            static::$_builtInFormats[70] = 't# ??/??';

            // our stuff
            static::$_builtInFormats[7] = '#,##0.00\ "€";\-#,##0.00\ "€"';

            // Flip array (for faster lookups)
            static::$_flippedBuiltInFormats = array_flip(static::$_builtInFormats);
        }
    }
}