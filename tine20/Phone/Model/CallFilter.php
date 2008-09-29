<?php
/**
 * Tine 2.0
 * 
 * @package     Phone
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 * @todo        add more filters
 */

/**
 * Call Filter Class
 * @package Phone
 */
class Phone_Model_CallFilter extends Tinebase_Record_Abstract
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
    protected $_application = 'Phone';
    
    /**
     * zend validators
     *
     * @var array
     */
    protected $_validators = array(
        'id'                   => array('allowEmpty' => true,  'Int'   ),
        'query'                => array('allowEmpty' => true           ), // source / destination
        'phone_id'             => array('allowEmpty' => true),
    ); 
    
    /**
     * check user phones (add user phone ids to filter
     *
     * @param unknown_type $_userId
     */
    public function checkUserPhones($_userId) {
        // set user phone ids as filter
        $userPhoneIds = Voipmanager_Controller::getInstance()->getMyPhones('description', 'ASC', '', $_userId)->getArrayOfIds();
        if (empty($this->phone_id)) {
            $this->phone_id = $userPhoneIds;
        } else {
            if (is_array($this->phone_id)) {
                $this->phone_id = array_intersect($this->phone_id, $userPhoneIds);
            } else {
                if (!in_array($this->phone_id, $userPhoneIds)) {
                    $this->phone_id = '';
                }
            }                
        }
    }
}
