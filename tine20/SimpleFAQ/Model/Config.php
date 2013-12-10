<?php
/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * SimpleFAQ class SimpleFAQ_Model_Config
 *
 * @package     SimpleFAQ
 */

Class SimpleFAQ_Model_Config extends Tinebase_Record_Abstract
{
    /**
     * const to describe faq status
     *
     * @var string
     */
    const FAQSTATUSES = 'faqstatuses';

    /**
     * const to describe faq type
     *
     * @var string
     */
    const FAQTYPES = 'faqtypes';

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
    protected  $_application = 'SimpleFAQ';

    /**
     * record validators
     *
     * @var array
     */
    protected $_validators = array(
            'id'            => array('allowEmpty' => true ),
            'faqstatuses'   => array('allowEmpty' => true ),
            'faqtypes'      => array('allowEmpty' => true ),
            'defaults'      => array('allowEmpty' => true ),
    );

    /**
     * get an array in a multidimensional array by its property
     *
     * @param array $_id
     * @param string $_property
     * @return array
     *
     * @todo pr: anwendung?
     */
    public function getOptionById($_id, $_property, $_idProperty = 'id')
    {
        if ($this->has($_property) && isset($this->$_property) && is_array($this->$_property)) {
            foreach ($this->$_property as $sub) {
                if ((isset($sub[$_idProperty]) || array_key_exists($_idProperty, $sub)) && $sub[$_idProperty] == $_id) {
                    return $sub;
                }
            }
        }

        return array();
    }

}