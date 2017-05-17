<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem preview service test implementation
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
class Tinebase_FileSystem_TestPreviewService implements Tinebase_FileSystem_Preview_ServiceInterface
{
    public function getPreviewsForFile($_filePath, array $_config)
    {
        return array('thumbnail' => array('blob'), 'previews' => array('blob1', 'blob2', 'blob3'));
    }
}