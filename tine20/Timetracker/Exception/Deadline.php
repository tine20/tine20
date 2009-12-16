<?php
/**
 * Tine 2.0
 * 
 * @package     Timetracker
 * @subpackage  Exception
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 *
 */

/**
 * Deadline exception
 * 
 * @package     Timetracker
 * @subpackage  Exception
 */
class Timetracker_Exception_Deadline extends Timetracker_Exception
{
    /**
     * create new Deadline exception
     * 
     * @param string $_message
     * @param integer $_code
     * @return void
     */
    public function __construct($_message = 'You are not allowed create or update this timesheet because the deadline was exceeded.', $_code = 902) {
        parent::__construct($_message, $_code);
    }
}
