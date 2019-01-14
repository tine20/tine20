<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */

class Tinebase_FileSystem_Preview_BadRequestException extends Tinebase_Exception_ProgramFlow
{
    public function __construct($_message)
    {
        parent::__construct($_message);
    }
}