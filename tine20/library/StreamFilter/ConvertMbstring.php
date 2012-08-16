<?php
/**
 * Tine 2.0
 *
 * @package     Library
 * @subpackage  StreamFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2012 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add from & to params?
 */
class StreamFilter_ConvertMbstring extends php_user_filter
{
    /**
     * (non-PHPdoc)
     * @see php_user_filter::filter()
     */
    function filter($in, $out, &$consumed, $closing) {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $encoding = mb_detect_encoding($bucket->data, array('utf-8', 'iso-8859-1', 'windows-1252', 'iso-8859-15'));
            if ($encoding !== FALSE) {
                $bucket->data = @mb_convert_encoding($bucket->data, 'utf-8', $encoding);
            }
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}
stream_filter_register("convert.mbstring", "StreamFilter_ConvertMbstring");
