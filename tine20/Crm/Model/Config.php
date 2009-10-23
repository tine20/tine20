<?php
/**
 * Tine 2.0
 * 
 * @package     Crm
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id$
 * 
 * TODO         generalize this (by adding more generic fields)
 */

/**
 * class Crm_Model_Config
 * 
 * @package     Crm
 * @subpackage  Record
 */
class Crm_Model_Config extends Tinebase_Record_Abstract 
{   
    /**
     * ods export config
     * 
     * @var string
     */
    const LEADSTATES = 'leadstates';
    
    /**
     * app defaults
     * 
     * @var string
     */
    const LEADTYPES = 'leadtypes';
    
    /**
     * logout redirect url
     * 
     * @var string
     */
    const LEADSOURCES = 'leadsources';
    
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
    protected $_application = 'Crm';
    
    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                => array('allowEmpty' => true ),
        'leadstates'        => array('allowEmpty' => true ),
        'leadtypes'         => array('allowEmpty' => true ),
        'leadsources'       => array('allowEmpty' => true ),        
        'defaults'          => array('allowEmpty' => true ),
    );
    
    /**
     * get an array in a multidimensional array by its property
     * 
     * @param array $_id
     * @param string $_property
     * @return array
     * 
     * @todo add to generic config/settings model
     */
    function getOptionById($_id, $_property, $_idProperty = 'id')
    {
        if ($this->has($_property) && isset($this->$_property) && is_array($this->$_property)) {
            foreach ($this->$_property as $sub) {
                if (array_key_exists($_idProperty, $sub) && $sub[$_idProperty] == $_id) {
                    return $sub;
                }
            }
        }
        
        return array();
    }
    
} // end of Crm_Model_Config
