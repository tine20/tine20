<?php
/**
 * Tine 2.0
 *
 * @package     Courses
 * @subpackage  Events
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 * @version     $Id$
 */

/**
 * thrown before a uset account gets importet
 *
 */
class Admin_Event_BeforeImportUser extends Tinebase_Events_Abstract
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