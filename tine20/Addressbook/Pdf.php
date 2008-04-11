<?php
/**
 * contact pdf generation class
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */


/**
 * defines the datatype for simple registration object
 * 
 * @package     Addressbook
 */
class Addressbook_Pdf extends Tinebase_Export_Pdf
{
    /**
     * create contact pdf
     *
     * @param	Addressbook_Model_Contact $_contact contact data
     *
     * @return	string	the contact pdf
     */
    public function getContactPdf ( Addressbook_Model_Contact $_contact )
    {
        $locale = Zend_Registry::get('locale');
        $translate = new Zend_Translate('gettext', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'translations', null, array('scan' => Zend_Translate::LOCALE_FILENAME));
        $translate->setLocale($locale);
         
        $contactFields = array (
            array(  'label' => $translate->_('Business Contact Data'), 
                    'type' => 'separator' ),
            array(  'label' => $translate->_('Business Address'), 
                    'type'  => 'multiRow', 
                    'value' => array(   'adr_one_street', 
                                        'adr_one_street2',
                                        array ('adr_one_postalcode', 'adr_one_locality'),
                                        array ('adr_one_region', 'adr_one_countryname'),
                                    ), 
            ),
            array(  'label' => $translate->_('Organisation / Unit'), 
                    'type'  => 'singleRow',
                    'value' => array( array('org_name', 'org_unit') ),
                    'glue'  => ' / ', 
            ),
            array(  'label' => $translate->_('Email'), 
                    'value' => array( 'email' ), 
            ),
            array(  'label' => $translate->_('Telephone Work'), 
                    'value' => array( 'tel_work' ), 
            ),
            array(  'label' => $translate->_('Telephone Cellphone'), 
                    'value' => array( 'tel_cell' ), 
            ),
            array(  'label' => $translate->_('Telephone Car'), 
                    'value' => array( 'tel_car' ), 
            ),
            array(  'label' => $translate->_('Telephone Fax'), 
                    'value' => array( 'tel_fax' ), 
            ),
            array(  'label' => $translate->_('Telephone Page'), 
                    'value' => array( 'tel_pager' ), 
            ),
            array(  'label' => $translate->_('URL'), 
                    'value' => array( 'url' ), 
            ),
            array(  'label' => $translate->_('Role'), 
                    'value' => array( 'role' ), 
            ),
            array(  'label' => $translate->_('Assistant'), 
                    'value' => array( 'assistent' ), 
            ),
            array(  'label' => $translate->_('Assistant Telephone'), 
                    'value' => array( 'tel_assistent' ), 
            ),
            /******************************************/
            array(  'label' => $translate->_('Private Contact Data'), 
                    'type' => 'separator' ),
            array(  'label' => $translate->_('Private Address'), 
                    'type'  => 'multiRow', 
                    'value' => array(   'adr_two_street', 
                                        'adr_two_street2',
                                        array ('adr_two_postalcode', 'adr_two_locality'),
                                        array ('adr_two_region', 'adr_two_countryname'),
                                    ), 
            ),
            array(  'label' => $translate->_('Email Home'), 
                    'value' => array( 'email_home' ), 
            ),
            array(  'label' => $translate->_('Telephone Home'), 
                    'value' => array( 'tel_home' ), 
            ),
            array(  'label' => $translate->_('Telephone Cellphone Private'), 
                    'value' => array( 'tel_cell_private' ), 
            ),
            array(  'label' => $translate->_('Telephone Fax Home'), 
                    'value' => array( 'tel_fax_home' ), 
            ),
            array(  'label' => $translate->_('URL Home'), 
                    'value' => array( 'url_home' ), 
            ),
            /******************************************/
            array(  'label' => $translate->_('Other Data'), 
                    'type' => 'separator' ),
            array(  'label' => $translate->_('Birthday'), 
                    'value' => array( 'bday' ), 
            ),
            array(  'label' => $translate->_('Title'), 
                    'value' => array( 'title' ), 
            ),

            //'id' => 'Contact ID',    
            //'owner' => 'Owner',
            //'n_prefix' => 'Name Prefix',
            //'n_suffix' => 'Name Suffix',
             
        );

         
        //@todo	include contact photo here
        $contactPhoto = Zend_Pdf_Image::imageWithPath(dirname(dirname(__FILE__)).'/images/empty_photo.jpg');		
        
        // build title
        $title = $_contact->n_fn; 
        $subtitle = $_contact->org_name;
        $titleIcon = "/images/oxygen/32x32/apps/system-users.png";
        
        // add data to array
        $record = array ();
        foreach ( $contactFields as $fieldArray ) {
            if ( !isset($fieldArray['type']) || $fieldArray['type'] !== 'separator' ) {
                $values = array();
                foreach ( $fieldArray['value'] as $valueFields ) {
                    $content = array();
                    if ( is_array($valueFields) ) {
                        $keys = $valueFields;
                    } else {
                        $keys = array ( $valueFields );
                    }
                    foreach ( $keys as $key ) {
                        if ( $_contact->$key instanceof Zend_Date ) {
                            $content[] = $_contact->$key->toString(Zend_Locale_Format::getDateFormat(Zend_Registry::get('locale')), Zend_Registry::get('locale') );
                        } elseif (!empty($_contact->$key) ) {
                            if ( preg_match ("/countryname/", $key) ) {
                                $content[] = $locale->getCountryTranslation ( $_contact->$key );
                            } else {
                                $content[] = $_contact->$key;
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
                
        return $this->generatePdf($record, $title, $subtitle, $_contact->note, $titleIcon, $contactPhoto, array(), FALSE );        
	}

}