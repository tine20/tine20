<?php

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2018 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiß <c.weiss@metaways.de>
 */

/**
 * Class Tinebase_Http_Request
 *
 * NOTE: ^%*&* Zend\Http\PhpEnvironment\Request can't cope with input streams
 *       which leads to waste of mem e.g. on large file upload via PUT (WebDAV)
 */
class Tinebase_Http_Request extends Zend\Http\PhpEnvironment\Request
{
    protected $_inputStream;

    public function getContentStream($rewind = true)
    {
        if (! $this->_inputStream) {
            if (! empty($this->content)) {
                $this->_inputStream = fopen('php://temp', 'r+');
                fputs($this->_inputStream, $this->content);
            } else {
                // NOTE: as of php 5.6 php://input can be rewinded
                $this->_inputStream = fopen('php://input', 'r');
            }
        }

        if ($rewind) {
            rewind($this->_inputStream);
        }

        return $this->_inputStream;
    }


}