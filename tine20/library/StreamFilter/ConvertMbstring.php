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
 * @todo        add from & to params
 */
class StreamFilter_ConvertMbstring extends php_user_filter
{
    /**
     * (non-PHPdoc)
     * @see php_user_filter::filter()
     */
    function filter($in, $out, &$consumed, $closing) {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = @mb_convert_encoding($bucket->data, 'utf-8');
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }
}
stream_filter_register("convert.mbstring", "StreamFilter_ConvertMbstring");
