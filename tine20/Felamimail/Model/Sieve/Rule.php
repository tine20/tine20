<?php
/**
 * class to hold Sieve Rule data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to hold Rule data
 * 
 * @property    integer  order
 * @property    array  actions
 * @property    array  conditions
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_Rule extends Tinebase_Record_Abstract
{  
    /**
     * key in $_validators/$_properties array for the field which 
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
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), // account id
        'order'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'action'                => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
        'conditions'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
    );
    
    /**
     * set from sieve rule object
     * 
     * @param Felamimail_Sieve_Rule $fsr
     */
    public function setFromFSR(Felamimail_Sieve_Rule $fsr)
    {
        $this->setFromArray($fsv->toArray());
    }
    
    /**
     * get sieve rule object
     * 
     * @return Felamimail_Sieve_Rule
     * 
     * @todo finish (add action + conditions)
     */
    public function getFSR()
    {
        $fsr = new Felamimail_Sieve_Rule();
        $fsr->setEnabled($this->enabled);

        /*
        $action = $this->action
        foreach ($this->conditions as $conditions) {
            
        } 
        */    
            
        return $fsr;
    }
    
}
