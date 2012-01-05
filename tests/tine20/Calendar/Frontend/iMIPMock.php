<?php
/**
 * Tine 2.0
 * 
 * @package     Calendar
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * iMIP (RFC 6047) frontend for calendar (MOCK for unittests)
 * 
 * @package     Calendar
 * @subpackage  Frontend
 */
class Calendar_Frontend_iMIPMock extends Calendar_Frontend_iMIP
{
    /**
    * manual process iMIP component and optionally set status
    * - client spoofing detection removed!
    *
    * @param  Calendar_Model_iMIP   $_iMIP
    * @param  string                $_status
    */
    public function process($_iMIP, $_status = NULL)
    {
        return $this->_process($_iMIP, $_status);
    }
}
