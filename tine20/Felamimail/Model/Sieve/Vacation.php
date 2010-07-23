<?php
/**
 * class to hold Sieve Vacation data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */

/**
 * class to hold Vacation data
 * 
 * @property  string  trash_folder
 * @package     Felamimail
 */
class Felamimail_Model_Sieve_Vacation extends Tinebase_Record_Abstract
{  
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */    
    //protected $_identifier = 'id';    
    
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
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'account_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'vacation'              => array(Zend_Filter_Input::ALLOW_EMPTY => true),    
    );
}
