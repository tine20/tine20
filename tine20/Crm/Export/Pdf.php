<?php
/**
 * crm pdf generation class
 *
 * @package     Crm
 * @subpackage  Export
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 */


/**
 * crm pdf export class
 * 
 * @package     Crm
 * @subpackage  Export
  */
class Crm_Export_Pdf extends Tinebase_Export_Pdf
{

    /**
     * create lead pdf
     *
     * @param	Crm_Model_Lead $_lead lead data
     * 
     * @return	string	the pdf
     */
    public function generate(Crm_Model_Lead $_lead, $_pageNumber = 0)
    {
        $locale = Tinebase_Core::get('locale');
        $translate = Tinebase_Translation::getTranslation('Crm');    
        
        // set user timezone
        $_lead->setTimezone(Tinebase_Core::get('userTimeZone'));

        /*********************** build data array ***************************/
        
        $record = $this->getRecord($_lead, $locale, $translate);	    

        /******************* build title / subtitle / description ***********/
        
        $title = $_lead->lead_name; 
        $subtitle = "";
        $description = $_lead->description;
        $titleIcon = "/images/oxygen/32x32/actions/datashowchart.png";

        /*********************** add linked objects *************************/

        $linkedObjects = $this->getLinkedObjects($_lead, $locale, $translate);       
        $tags = ($_lead->tags instanceof Tinebase_Record_RecordSet) ? $_lead->tags->toArray() : array();
        
        /***************************** generate pdf now! ********************/
                    
        parent::generatePdf($record, $title, $subtitle, $tags,
            $description, $titleIcon, NULL, $linkedObjects, FALSE);
        
    }

    /**
     * get record array
     *
     * @param   Crm_Model_Lead $_lead lead data
     * @param   Zend_Locale $_locale the locale
     * @param   Zend_Translate $_translate
     * @return  array  the record
     *
     */
    protected function getRecord(Crm_Model_Lead $_lead, Zend_Locale $_locale, Zend_Translate $_translate)
    {        
        $settings = Crm_Controller::getInstance()->getSettings();
        
        $leadFields = array (
            array(  'label' => /* $_translate->_('Lead Data') */ "", 
                    'type' => 'separator' 
            ),
            array(  'label' => $_translate->_('Leadstate'), 
                    'value' => array( 'leadstate_id' ),
            ),
            array(  'label' => $_translate->_('Leadtype'), 
                    'value' => array( 'leadtype_id' ),
            ),
            array(  'label' => $_translate->_('Leadsource'), 
                    'value' => array( 'leadsource_id' ),
            ),
            array(  'label' => $_translate->_('Turnover'), 
                    'value' => array( 'turnover' ),
            ),
            array(  'label' => $_translate->_('Probability'), 
                    'value' => array( 'probability' ),
            ),
            array(  'label' => $_translate->_('Start'), 
                    'value' => array( 'start' ),
            ),
            array(  'label' => $_translate->_('End'), 
                    'value' => array( 'end' ),
            ),
            array(  'label' => $_translate->_('End Scheduled'), 
                    'value' => array( 'end_scheduled' ),
            ),
            
        );
        
        // add data to array
        $record = array ();
        foreach ($leadFields as $fieldArray) {
            if (!isset($fieldArray['type']) || $fieldArray['type'] !== 'separator') {
                $values = array();
                foreach ( $fieldArray['value'] as $valueFields ) {
                    $content = array();
                    if ( is_array($valueFields) ) {
                        $keys = $valueFields;
                    } else {
                        $keys = array ( $valueFields );
                    }
                    foreach ( $keys as $key ) {
                        if ( $_lead->$key instanceof Zend_Date ) {
                            $content[] = $_lead->$key->toString(Zend_Locale_Format::getDateFormat(Tinebase_Core::get('locale')), Tinebase_Core::get('locale') );
                        } elseif (!empty($_lead->$key) ) {
                            if ( $key === 'turnover' ) {
                                $content[] = Zend_Locale_Format::toNumber($_lead->$key, array('locale' => $_locale)) . " €";
                            } elseif ( $key === 'probability' ) {
                                $content[] = $_lead->$key . " %";
                            } elseif ( $key === 'leadstate_id' ) {
                                $state = $settings->getOptionById($_lead->leadstate_id, 'leadstates');
                                $content[] = $state['leadstate'];
                            } elseif ( $key === 'leadtype_id' ) {
                                $type = $settings->getOptionById($_lead->leadtype_id, 'leadtypes');
                                $content[] = $type['leadtype'];
                            } elseif ( $key === 'leadsource_id' ) {
                                $source = $settings->getOptionById($_lead->leadsource_id, 'leadsources');
                                $content[] = $source['leadsource'];
                            } else {
                                $content[] = $_lead->$key;
                            }
                        }
                    }
                    if ( !empty($content) ) {
                        $glue = ( isset($fieldArray['glue']) ) ? $fieldArray['glue'] : " ";
                        $values[] = implode($glue,$content);
                    }
                }
                if ( !empty($values) ) {
                    $record[] = array ( 'label' => $fieldArray['label'],
                                        'type'  => ( isset($fieldArray['type']) ) ? $fieldArray['type'] : 'singleRow',
                                        'value' => ( sizeof($values) === 1 ) ? $values[0] : $values,
                    ); 
                }
            } elseif ( isset($fieldArray['type']) && $fieldArray['type'] === 'separator' ) {
                $record[] = $fieldArray;
            }
        }     
        
        $record = $this->_addActivities($record, $_lead->notes);
        
        return $record;
    }
        
    /**
     * get linked objects for lead pdf (contacts, tasks, ...)
     *
     * @param   Crm_Model_Lead $_lead lead data
     * @param   Zend_Locale $_locale the locale
     * @param   Zend_Translate $_translate
     * @return  array  the linked objects
     */
    protected function getLinkedObjects(Crm_Model_Lead $_lead, Zend_Locale $_locale, Zend_Translate $_translate)
    {
        $linkedObjects = array ();
	
        // check relations
        if ($_lead->relations instanceof Tinebase_Record_RecordSet) {
            
            $_lead->relations->addIndices(array('type'));

            /********************** contacts ******************/
            
            $linkedObjects[] = array($_translate->_('Contacts'), 'headline');
    
            $types = array (    "customer" => "/images/oxygen/32x32/apps/system-users.png", 
                                "partner" => "/images/oxygen/32x32/actions/view-process-own.png", 
                                "responsible" => "/images/oxygen/32x32/apps/preferences-desktop-user.png",
                            );        
            
            foreach ($types as $type => /* $headline */ $icon) {
    
                $contactRelations = $_lead->relations->filter('type', strtoupper($type));
                
                foreach ($contactRelations as $relation) {
                    try {
                        //$contact = Addressbook_Controller_Contact::getInstance()->getContact($relation->related_id);
                        $contact = $relation->related_record;
                        
                        $contactNameAndCompany = $contact->n_fn;
                        if ( !empty($contact->org_name) ) {
                            $contactNameAndCompany .= " / " . $contact->org_name;
                        }
                        $linkedObjects[] = array ($contactNameAndCompany, 'separator', $icon);
                        
                        $postalcodeLocality = ( !empty($contact->adr_one_postalcode) ) ? $contact->adr_one_postalcode . " " . $contact->adr_one_locality : $contact->adr_one_locality;
                        $regionCountry = ( !empty($contact->adr_one_region) ) ? $contact->adr_one_region . " " : "";
                        if ( !empty($contact->adr_one_countryname) ) {
                            $regionCountry .= Zend_Locale::getTranslation($contact->adr_one_countryname, 'country', $_locale);
                        }
                        $linkedObjects[] = array ($_translate->_('Address'), 
                                                array( 
                                                    $contact->adr_one_street, 
                                                    $postalcodeLocality,
                                                    $regionCountry,
                                                )
                                            );
                        $linkedObjects[] = array ($_translate->_('Telephone'), $contact->tel_work);
                        $linkedObjects[] = array ($_translate->_('Email'), $contact->email);
                    } catch (Exception $e) {
                        // do nothing so far
                    }
                }
            }
            
            /********************** tasks ******************/

            $taskRelations = $_lead->relations->filter('type', strtoupper('task'));
            
            if (!empty($taskRelations)) {
            
                $linkedObjects[] = array ( $_translate->_('Tasks'), 'headline');
                
                foreach ($taskRelations as $relation) {
                    try {
                        $task = $relation->related_record;
                        
                        $taskTitle = $task->summary . " ( " . $task->percent . " % ) ";
                        // @todo add big icon to db or preg_replace? 
                        if ( !empty($task->status_id) ) {
                            $status = Tasks_Controller_Status::getInstance()->getTaskStatus($task->status_id);
                            $icon = "/" . $status['status_icon'];
                            $linkedObjects[] = array ($taskTitle, 'separator', $icon);
                        } else {
                            $linkedObjects[] = array ($taskTitle, 'separator');
                        }
                        
                        // get due date
                        if (! empty($task->due)) {
                            $dueDate = new Zend_Date($task->due, Tinebase_Record_Abstract::ISO8601LONG);                 
                            $linkedObjects[] = array($_translate->_('Due Date'), $dueDate->toString(Zend_Locale_Format::getDateFormat(Tinebase_Core::get('locale')), Tinebase_Core::get('locale')) );
                        }    
                        
                        // get task priority
                        $taskPriority = $this->getTaskPriority($task->priority, $_translate);
                        $linkedObjects[] = array ($_translate->_('Priority'), $taskPriority );
                        
                    } catch (Exception $e) {
                        // do nothing so far
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' exception caught: ' . $e->__toString());
                    }
                }
            }
            
            /********************** products ******************/

            $productRelations = $_lead->relations->filter('type', strtoupper('product'));
            
            if (!empty($productRelations)) {
            
                $linkedObjects[] = array ( $_translate->_('Products'), 'headline');
                
                foreach ($productRelations as $relation) {
                    try {
                        $product = $relation->related_record;
                        
                        $quantity = (isset($relation['remark']['quantity'])) ? $relation['remark']['quantity'] : 1;
                        $price = (isset($relation['remark']['price'])) ? $relation['remark']['price'] : $product->price;
                        // @todo set precision for the price ?
                        $price = Zend_Locale_Format::toNumber($price, array('locale' => $_locale)/*, array('precision' => 2)*/) . " €";
                        $description = (isset($relation['remark']['description'])) ? $relation['remark']['description'] : $product->description;
                        
                        $linkedObjects[] = array (
                            $product->name . ' - ' . $description . ' (' . $price . ') x ' . $quantity, 
                            'separator'
                        );
                    
                    } catch (Exception $e) {
                        // do nothing so far
                        Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' exception caught: ' . $e->__toString());
                    }
                }
            }                        
        }
        
        return  $linkedObjects;
    }
    
    /**
     * get task priority
     * 
     * @param  int $_priorityId
     * @param  int $_translate
     * 
     * @return string priority
     * 
     * @todo    move to db / tasks ?
     */
    public function getTaskPriority($_priorityId, Zend_Translate $_translate) 
    {
        
        $priorities = array (   '0' => $_translate->_('low'),
                                '1' => $_translate->_('normal'), 
                                '2' => $_translate->_('high'),
                                '3' => $_translate->_('urgent')
        );
            
        $result = ( isset($priorities[$_priorityId]) ) ? $priorities[$_priorityId] : "";
        
        return $result;
    }
    
    
    
}
