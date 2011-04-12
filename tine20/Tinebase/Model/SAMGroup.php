<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Samba
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Cornelius Weiss <c.weiss@metaways.de>
 */

/**
 * class Tinebase_Model_SAMGroup
 * 
 * @package     Tinebase
 * @subpackage  Samba
 */
class Tinebase_Model_SAMGroup extends Tinebase_Record_Abstract 
{
   
    protected $_identifier = 'sid';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    protected $_validators = array(
        'sid'              => array('allowEmpty' => true),
        'groupType'        => array('allowEmpty' => true),
   );
} 
