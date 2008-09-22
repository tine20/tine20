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
        'phone_id'             => array('allowEmpty' => true           ),
    ); 
}
