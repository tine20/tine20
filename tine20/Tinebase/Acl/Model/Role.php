<?php
/**
 * model to handle roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * @todo    make it work! wollen wir das überhaupt nutzen??
 */

/**
 * defines the datatype for roles
 * 
 * @package     Tinebase
 * @subpackage  Acl
 *  */
class Tinebase_Acl_Model_Role extends Tinebase_Record_Abstract
{
	/**
     * key in $_validators/$_properties array for the filed which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Tinebase';
    
    /**
     * list of zend inputfilter
     * 
     * this filter get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_filters = array(
        //'*'      => 'StringTrim'
    );

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Filter_Input
     *
     * @var array
     */
    protected $_validators = array();

    /**
     * @see Tinebase_Record_Abstract
     */
    public function __construct($_data = NULL, $_bypassFilters = false, $_convertDates = NULL)
    {
        $this->_validators = array(
            'id'                => array('allowEmpty' => true),
            /*'application_id'    => array('presence' => 'required'),
            'account_id'        => array('presence' => 'required', 'allowEmpty' => true),
            'account_type'      => array(
                new Zend_Validate_InArray(array('account', 'group', 'anyone')) 
            ),
            'right'             => array('presence' => 'required')*/
        );
        
        return parent::__construct($_data, $_bypassFilters);
    }
}