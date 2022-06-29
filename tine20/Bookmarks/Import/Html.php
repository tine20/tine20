<?php
/**
 * Tine 2.0
 *
 * @package     bookmarks
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Timo Scholz <t.scholz@metaways.de>
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * html import class for the bookmarks
 *
 * @package     Bookmarks
 * @subpackage  Import
 *
 * @property Bookmarks_Controller_Bookmark    $_controller    protected property!
 */
class Bookmarks_Import_Html extends Tinebase_Import_Abstract
{
    /**
     * additional config options
     *
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
    );

    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }

    protected function _getRawData(&$_resource)
    {
        return [];
    }


    /**
     * import the data
     *
     * @param mixed $_resource (if $_filename is a stream)
     * @param array $_clientRecordData
     * @return array with import data (imported records, failures, duplicates and totalcount)
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
            . ' Starting import of ' . ((! empty($this->_options['model'])) ? $this->_options['model'] . 's' : ' records'));

        $this->_initImportResult();

        $html = stream_get_contents($_resource);

        // read html into DOM
        $dom = new DOMDocument;

        // load HTML
        $dom->loadHTML(str_replace('&', '&amp;', $html));

        // import records
        foreach ($dom->getElementsByTagName('a') as $node) {
            $bookmark = new Bookmarks_Model_Bookmark();

            $bookmark->url = $node->getAttribute('href');
            $bookmark->name = ! empty($node->textContent) ? $node->textContent : mb_substr($bookmark->url, 0, 255);
            
            $this->_setController();
            $this->_importRecord($bookmark);
        }

        $this->_logImportResult();

        return $this->_importResult;
    }
}
