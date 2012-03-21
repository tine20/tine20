<?php
/**
 * Tine 2.0
 * 
 * @package     Admin
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * class Admin_Model_Config
 * 
 * @package     Admin
 * @subpackage  Record
 * 
 * @todo        refactor to use new config implementation
 */
class Admin_Model_Config extends Tinebase_Record_Abstract 
{
    /**
     * default internal addressbook for new users/groups
     * 
     * @var string
     * 
     * @todo move to addressbook?
     */
    const DEFAULTINTERNALADDRESSBOOK = 'defaultInternalAddressbook';
        
    /**
     * identifier
     * 
     * @var string
     */ 
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Admin';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => true ),
        'defaults'          => array('allowEmpty' => true ),
    );
}
