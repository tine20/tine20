<?php
/**
 * Tine 2.0
 *
 * @package     ActiveSync
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * sql backend class for the content state
 *
 * @package     ActiveSync
 */
class ActiveSync_Backend_ContentState extends Tinebase_Application_Backend_Sql_Abstract
{
    /**
     * the constructor
     *
     */
    public function __construct ()
    {
        parent::__construct(SQL_TABLE_PREFIX . 'acsync_content', 'ActiveSync_Model_ContentState');
    }    
}
