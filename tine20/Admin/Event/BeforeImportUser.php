<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Event
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * thrown before a user account gets imported
 *
 */
class Admin_Event_BeforeImportUser extends Tinebase_Event_Abstract
{
    /**
     * @var Tinebase_Model_FullUser account of the teacher
     */
    public $account;
    
    /**
     * @var array options of the import plugin
     */
    public $options;
    
    public function __construct($_account, $_options)
    {
        $this->account = $_account;
        $this->options = $_options;
    }
}
