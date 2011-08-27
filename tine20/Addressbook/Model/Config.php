<?php
/**
 * Tine 2.0
 * 
 * @package     Addressbook
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * 
 */

/**
 * class Addressbook_Model_Config
 * 
 * @package     Addressbook
 * @subpackage  Record
 */
class Addressbook_Model_Config extends Tinebase_Record_Abstract 
{   
    /**
     * default addressbook for map panel
     * 
     * @var string
     */
    const DEFAULTMAPADDRESS = 'defaultMapAddress';
        
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
    protected $_application = 'Addressbook';
    
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
