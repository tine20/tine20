<?php declare(strict_types=1);
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Sabre\DAV\URLUtil;

/**
 * object tree for the sabre server to work with
 *
 * @package     Tinebase
 * @subpackage  WebDAV
 */
class Tinebase_WebDav_ObjectTree extends \Sabre\DAV\ObjectTree
{
    /**
     * Moves a file from one location to another
     *
     * @param string $sourcePath The path to the file which should be moved
     * @param string $destinationPath The full destination path, so not just the destination parent node
     * @return int
     */
    public function move($sourcePath, $destinationPath) {

        list($sourceDir,) = URLUtil::splitPath($sourcePath);
        list($destinationDir, $destinationName) = URLUtil::splitPath($destinationPath);
        $sourceNode = $this->getNodeForPath($sourcePath);

        if ($sourceDir===$destinationDir) {
            $sourceNode->setName($destinationName);
        } elseif($sourceNode instanceof Tinebase_Frontend_WebDAV_IRenamable) {
            $destinationParent = $this->getNodeForPath($destinationDir);
            if (!$destinationParent instanceof Filemanager_Frontend_WebDAV_Container &&
                    !$destinationParent instanceof Filemanager_Frontend_WebDAV) {
                throw new Tinebase_Exception_UnexpectedValue('node ' . $destinationDir .
                    ' excpected to be instance of ' . Filemanager_Frontend_WebDAV_Container::class . ' or ' .
                    Filemanager_Frontend_WebDAV::class);
            }

            $sourceNode->rename($destinationParent->getPath() . '/' . $destinationName);
        } else {
            $this->copy($sourcePath,$destinationPath);
            $this->getNodeForPath($sourcePath)->delete();
        }
        $this->markDirty($sourceDir);
        $this->markDirty($destinationDir);

        return 0;
    }
}
