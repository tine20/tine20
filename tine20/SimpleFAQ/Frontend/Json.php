<?php
/**
 * Tine 2.0
 * 
 * @package     SimpleFAQ
 * @subpackage  Frontend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Patrick Ryser <patrick.ryser@gmail.com>
 * @copyright   Copyright (c) 2007-2011 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * This class handles all Json requests for the SimpleFAQ application
 *
 * @package     SimpleFAQ
 * @subpackage  Frontend
 */
class SimpleFAQ_Frontend_Json extends Tinebase_Frontend_Json_Abstract
{
    /**
     * the controller
     *
     * @var ExampleApplication_Controller_ExampleRecord
     */
    protected $_controller = NULL;

    /**
     * the constructor
     *
     */
    public function __construct()
    {
        $this->_applicationName = 'SimpleFAQ';
        $this->_controller = SimpleFAQ_Controller_Faq::getInstance();
    }

    /**
     * Search for records matching given arguments
     *
     * @param  array $filter
     * @param  array $paging
     * @return array
     */
    public function searchFaqs($filter, $paging)
    {
        return $this->_search($filter, $paging, $this->_controller, 'SimpleFAQ_Model_FaqFilter', TRUE);
    }

    /**
     * Return a single record
     *
     * @param   string $id
     * @return  array record data
     */
    public function getFaq($id)
    {
        return $this->_get($id, $this->_controller);
    }

    /**
     * creates/updates a record
     *
     * @param  array $recordData
     * @return array created/updated record
     */
    public function saveFaq($recordData)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . ' (' . __LINE__ . ') pr hier: ' . print_r($recordData, true) );
        return $this->_save($recordData, $this->_controller, 'Faq');
    }

    /**
     * deletes existing records
     *
     * @param  array  $ids
     * @return string
     */
    public function deleteFaqs($ids)
    {
        return $this->_delete($ids, $this->_controller);
    }

    /**
     * Returns registry data
     *
     * @return array
     */

    /**
     * Returns registry data of SimpleFAQ.
     * @see Tinebase_Application_Json_Abstract
     *
     * @return  mixed array 'variable name' => 'data'
     *
     */
    public function getRegistryData()
    {
        $settings = $this->getSettings();
        $defaults = $settings['defaults'];

        // get default container
        $defaultContainerArray = Tinebase_Container::getInstance()->getDefaultContainer($this->_applicationName)->toArray();
        $defaultContainerArray['account_grants'] = Tinebase_Container::getInstance()->getGrantsOfAccount(
            Tinebase_Core::getUser(),
            $defaultContainerArray['id']
        )->toArray();
        $defaults['container_id'] = $defaultContainerArray;

        $registryData = array(
            'faqstatuses'     => array(
                'results' => $settings[SimpleFAQ_Model_Config::FAQSTATUSES],
                'totalcount' => count($settings[SimpleFAQ_Model_Config::FAQSTATUSES])
            ),
            'faqtypes'    => array(
                'results' => $settings[SimpleFAQ_Model_Config::FAQTYPES],
                'totalcount' => count($settings[SimpleFAQ_Model_Config::FAQTYPES])
            ),
            'defaults'      => $defaults,
        );

        return $registryData;
    }

    /**
     * Returns settings for SimpleFAQ app
     *
     * @return  array record data
     *
     * @todo pr: Anwendung
     */
    public function getSettings()
    {
        $result = SimpleFAQ_Controller::getInstance()->getConfigSettings()->toArray();

        return $result;
    }

    /**
     * creates/updates settings
     *
     * @return array created/updated settings
     *
     * @todo pr: Anwendung
     */
    public function saveSettings($recordData)
    {
        $settings = new SimpleFAQ_Model_Config($recordData);
        $result = SimpleFAQ_Controller::getInstance()->saveConfigSettings($settings)->toArray();

        return $result;
    }
}
