<?php
/**
 * this classes provides access to the sql table <prefix>_cal_user
 * 
 * @package     Calendar
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @copyright   Copyright (c) 2007-2007 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */

/**
 * this classes provides access to the sql table <prefix>_cal_user
 * 
 * @package     Calendar
 */
class Calendar_Backend_Sql_User extends Zend_Db_Table_Abstract
{
    
    // NOTE: We leave out the users reference on the egw_accounts, as we
    // maintain a users list in the client, and dont want cascading deletes any
    // time we delete a user!
    protected $_referenceMap = array(
        'Events' => array(
            'columns'       => 'cal_id',
            'refTableClass' => 'Calendar_Backend_Sql_Events',
            'refClumns'     => 'cal_id'
        )
    );
    
}