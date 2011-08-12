<?php
/**
 * Tine 2.0
 *
 * @package     Library
 * @subpackage  StreamFilter
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * 
 * @todo        add regex functionality
 */
class StreamFilter_StringReplace extends php_user_filter
{
    protected $_search = "";
    protected $_replace = "";

    /**
     * (non-PHPdoc)
     * @see php_user_filter::filter()
     */
    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = str_replace(
                $this->_search, 
                $this->_replace, 
                $bucket->data
            );
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }
        return PSFS_PASS_ON;
    }

    /**
     * (non-PHPdoc)
     * @see php_user_filter::onCreate()
     */
    public function onCreate()
    {
        if (isset($this->params['search'])) {
            $this->_search = $this->params['search'];
        }
        if (isset($this->params['replace'])) {
            $this->_replace = $this->params['replace'];
        }
    }
}
stream_filter_register('str.replace', 'StreamFilter_StringReplace');
