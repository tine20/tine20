<?php
/**
 * Tine 2.0
 * 
 * @package     Sales
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * class Sales_Model_Config
 * 
 * @package     Sales
 * @subpackage  Model
 */
class Sales_Model_Config extends Tinebase_Record_Abstract 
{
    /**
     * ods export config
     * 
     * @var string
     */
    const SHAREDCONTRACTSID = 'sharedcontractsid';
    
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
    protected $_application = 'Sales';
    
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
