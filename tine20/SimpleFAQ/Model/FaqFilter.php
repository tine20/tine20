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
 * Faq Filter Class
 *
 */
class SimpleFAQ_Model_FaqFilter extends Tinebase_Model_Filter_FilterGroup
{
     /**
     * @var string application of this filter group
     */
    protected $_applicationName = 'SimpleFAQ';

    /**
     * @var string name of model this filter group is designed for
     */
    protected $_modelName = 'SimpleFAQ_Model_Faq';

    /**
     * @var array filter model fieldName => definition
     */
    protected $_filterModel = array(
        'id'                => array('filter' => 'Tinebase_Model_Filter_Id'),
        'query'             => array('filter' => 'Tinebase_Model_Filter_Query', 'options' => array('fields' => array('answer', 'question'))),
        'faqstatus_id'      => array('filter' => 'Tinebase_Model_Filter_Int'),
        'faqtype_id'        => array('filter' => 'Tinebase_Model_Filter_Int'),
        'question'          => array('filter' => 'Tinebase_Model_Filter_Text'),
        'answer'            => array('filter' => 'Tinebase_Model_Filter_Text'),
        'tag'               => array('filter' => 'Tinebase_Model_Filter_Tag', 'options' => array('idProperty' => 'simple_faq.id')),
        'container_id'      => array('filter' => 'Tinebase_Model_Filter_Container', 'options' => array('applicationName' => 'SimpleFAQ')),
    );
}