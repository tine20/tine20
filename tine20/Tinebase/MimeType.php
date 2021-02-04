<?php declare(strict_types=1);

/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  MimeType
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 * @copyright   Copyright (c) 2021 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * filesystem controller
 *
 * @package     Tinebase
 * @subpackage  MimeType
 */
class Tinebase_MimeType
{
    use Tinebase_Controller_SingletonTrait;

    /**
     * @var array<string>
     */
    protected $_mimeTypes;

    protected function __construct()
    {
        $this->_mimeTypes = require 'mimeTypes.php';
    }

    final public function getMimeTypeForExtention(string $extension): ?string
    {
        return isset($this->_mimeTypes[$extension]) ? $this->_mimeTypes[$extension] : null;
    }
}
