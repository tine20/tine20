<?php
/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 */

/**
 * class to hold Folder data
 * 
 * @package     Felamimail
 */
class Felamimail_Model_Folder extends Tinebase_Record_Abstract
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
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'localname'             => array(Zend_Filter_Input::ALLOW_EMPTY => false),
        'globalname'            => array(Zend_Filter_Input::ALLOW_EMPTY => false), // global name is the path from root folder
        'backend_id'            => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 'default'),
        'delimiter'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'is_selectable'         => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
        'has_children'          => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 1),
    // cache sync values 
        'uidnext'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'uidvalidity'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
}
