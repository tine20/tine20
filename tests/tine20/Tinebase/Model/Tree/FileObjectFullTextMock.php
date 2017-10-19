<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2017-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * Mock Class for Full Text test purpose
 */
class Tinebase_Model_Tree_FileObjectFullTextMock extends Tinebase_Model_Tree_FileObject
{
    /**
     * returns real filesystem path
     *
     * @param string $baseDir
     * @throws Tinebase_Exception_NotFound
     * @return string
     */
    public function getFilesystemPath($baseDir = NULL)
    {
        if (empty($this->hash)) {
            throw new Tinebase_Exception_NotFound('file object hash is missing');
        }

        if (!is_file($this->hash)) {
            throw new Tinebase_Exception_NotFound('hash doesn\'t contain a file path');
        }

        return $this->hash;
    }
}