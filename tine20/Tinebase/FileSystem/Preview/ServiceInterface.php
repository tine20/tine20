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
 * filesystem preview service interface
 *
 * @package     Tinebase
 * @subpackage  Filesystem
 */
interface Tinebase_FileSystem_Preview_ServiceInterface
{
    /**
     * @param $_filePath
     * @param array $_config
     * @return array|bool
     */
    public function getPreviewsForFile($_filePath, array $_config);
}