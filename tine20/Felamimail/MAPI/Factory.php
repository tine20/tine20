<?php
/**
 * Tine 2.0
 *
 * ConversionFactory for MAPI
 * 
 * @package     Felamimail
 * @subpackage  MAPI
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Ching En Cheng <c.cheng@metaways.de>
 * @copyright   Copyright (c) 2022 Metaways Infosystems GmbH (http://www.metaways.de)
 */

use Hfig\MAPI\Mime\ConversionFactory;
use Hfig\MAPI\OLE\CompoundDocumentElement as Element;


class Felamimail_MAPI_Factory implements ConversionFactory
{
    public function parseMessage(Element $root)
    {        
        return new Felamimail_MAPI_Message($root);
    }
}
