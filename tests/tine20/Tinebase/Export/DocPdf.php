<?php
/**
 * Tine 2.0 - http://www.tine20.org
 *
 * @package     Tinebase
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2010-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Milan Mertens <m.mertens@metaways.de>
 */


class Tinebase_Export_DocPdf extends Tinebase_Export_DocMock
{
    use Tinebase_Export_DocumentPdfTrait;

    /**
     * @return string
     */
    protected function _getOldFormat()
    {
        return 'doc';
    }
}