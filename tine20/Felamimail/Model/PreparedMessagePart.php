<?php
/**
 * class to hold prepared message part data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schüle <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to hold prepared message part data
 * 
 * @package     Felamimail
 * @subpackage  Model
 * @property    string  $id
 * @property    string  $contentType
 * @property    string  $preparedData
 */
class Felamimail_Model_PreparedMessagePart extends Tinebase_Record_Abstract
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
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), // message id + part id
        'contentType'           => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'preparedData'          => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
    );
}
