<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Christoph Elisabeth Hintermüller <christoph@out-world.com>
 * @copyright   Copyright (c) 2011-2017 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Evolution vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Evolution extends Addressbook_Convert_Contact_VCard_Abstract
{
    // Evolution/3.18.5

    const HEADER_MATCH = '/Evolution\/(?P<version>.*)/';
    
    protected $_emptyArray = array(
        'adr_one_countryname'   => null,
        'adr_one_locality'      => null,
        'adr_one_postalcode'    => null,
        'adr_one_region'        => null,
        'adr_one_street'        => null,
        'adr_one_street2'       => null,
        'adr_two_countryname'   => null,
        'adr_two_locality'      => null,
        'adr_two_postalcode'    => null,
        'adr_two_region'        => null,
        'adr_two_street'        => null,
        'adr_two_street2'       => null,
        'assistent'             => null,
        'bday'                  => null,
        'calendar_uri'          => null,
        'email'                 => null,
        'email_home'            => null,
        'jpegphoto'             => null,
        'freebusy_uri'          => null,
        'note'                  => null,
        'role'                  => null,
        'title'                 => null,
        'url'                   => null,
        'url_home'              => null,
        'n_family'              => null,
        'n_fileas'              => null,
        'n_fn'                  => null,
        'n_given'               => null,
        'n_middle'              => null,
        'n_prefix'              => null,
        'n_suffix'              => null, 
        'org_name'              => null,
        'org_unit'              => null,
        'room'                  => null,
        'tel_assistent'         => null,
        'tel_car'               => null,
        'tel_cell'              => null,
        'tel_cell_private'      => null,
        'tel_fax'               => null,
        'tel_fax_home'          => null,
        'tel_home'              => null,
        'tel_pager'             => null,
        'tel_work'              => null,
        'tel_other'             => null,
        'tel_prefer'            => null,
        'tags'                  => null,
        'notes'                 => null,
    );
        
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @todo return all supported fields in correct format see http://forge.tine20.org/mantisbt/view.php?id=5346
     * @param  Addressbook_Model_Contact  $_record
     * @return \Sabre\VObject\Component\VCard
     */
    public function fromTine20Model(Tinebase_Record_Interface $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_record->toArray(), true));
        
        // initialize vcard object
        $card = $this->_fromTine20ModelRequiredFields(
            $_record,
            trim($_record->n_prefix . " " . $_record->n_given . " " . $_record->n_middle . " " . $_record->n_family . " " . $_record->n_suffix), 
            array($_record->org_name, $_record->org_unit,$_record->room)
        );
	$card->add('X-EVOLUTION-FILE-AS',$_record->n_fileas);
	$card->add('ROLE',$_record->role);
	$card->add('TITLE',$_record->role);
        $card->add('X-EVOLUTION-ASSISTANT',$_record->assistent);
        $card->add('TEL', $_record->tel_work, array('TYPE' => array('WORK','VOICE')));
        
        $card->add('TEL', $_record->tel_home, array('TYPE' => array('HOME','VOICE')));
        
        
        $card->add('TEL', $_record->tel_cell, array('TYPE' => array('CELL')));
        
        $card->add('TEL', $_record->tel_fax, array('TYPE' => array('FAX','WORK')));
        
        $card->add('TEL', $_record->tel_fax_home, array('TYPE' => array('FAX', 'HOME')));
        
        $card->add('TEL', $_record->tel_pager, array('TYPE' => 'PAGER'));
        $card->add('TEL', $_record->tel_car, array('TYPE' => 'CAR'));
        $card->add('TEL', $_record->tel_assistent, array('TYPE' => 'X-EVOLUTION-ASSISTANT'));
        $card->add('TEL', $_record->tel_other, array('TYPE' => 'X-EVOLUTION-CALLBACK'));
        $card->add('TEL', $_record->tel_prefer, array('TYPE' => 'PREF'));
        
        $card->add('ADR', array(null, $_record->adr_one_street2, $_record->adr_one_street, $_record->adr_one_locality, $_record->adr_one_region, $_record->adr_one_postalcode, $_record->adr_one_countryname), array('TYPE' => 'WORK'));
        
        $card->add('ADR', array(null, $_record->adr_two_street2, $_record->adr_two_street, $_record->adr_two_locality, $_record->adr_two_region, $_record->adr_two_postalcode, $_record->adr_two_countryname), array('TYPE' => 'HOME')); 
        
        $card->add('EMAIL', $_record->email, array('TYPE' => 'WORK'));
        
        $card->add('EMAIL', $_record->email_home, array('TYPE' => 'HOME'));
                
        $card->add('URL', $_record->url);

        $card->add('X-EVOLUTION-BLOG-URL', $_record->url_home); # TODO change when there will be added a blog url
        
        $card->add('NOTE', $_record->note);
        
	$card->add('CALURI',$_record->calendar_uri);

	$card->add('FBURL',$_record->freebusy_uri);

        $this->_fromTine20ModelAddBirthday($_record, $card);
        
        $this->_fromTine20ModelAddPhoto($_record, $card);
        
        $this->_fromTine20ModelAddGeoData($_record, $card);
        
        $this->_fromTine20ModelAddCategories($_record, $card);
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) 
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' card ' . $card->serialize());
        
        return $card;
    }
    
    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseOther()
     */
    protected function _toTine20ModelParseOther(&$data, \Sabre\VObject\Property $property) {

         $otherField = null;

         switch ($property->name) {
             case 'X-EVOLUTION-FILE-AS': {
                 $data['n_fileas'] = $property->getValue();
                 break;
             }
             case 'X-EVOLUTION-ASSISTANT': {
                 $data['assistent'] = $property->getValue();
                 break;
             }
             case 'ROLE': {
                 $data['role'] = $property->getValue();
                 break;
             }
             case 'X-EVOLUTION-BLOG-URL' : {
                 $data['url_home'] = $property->getValue();
                 break;
             }	
             case 'CALURI' : {
                 $data['calendar_uri'] = $property->getValue();
                 break;
             }
             case 'FBURL' : {
                 $data['freebusy_uri'] = $property->getValue();
                 break;
            }
	}
    }
    
    
    /**
     * parse extra fields provided with org entry in vcard
     * 
     * @param array $data
     * @param array $orgextra
     */
    protected function _toTine20ModelParseOrgExtra(&$data,$parts) {
         $data['room'] = isset($parts[2]) ? $parts[2] : null;
    }

    /**
     * (non-PHPdoc)
     * @see Addressbook_Convert_Contact_VCard_Abstract::_toTine20ModelParseTel()
     */
    protected function _toTine20ModelParseTel(&$data, \Sabre\VObject\Property $property)
    {
        $telField = null;
        
        if (isset($property['TYPE'])) {
            // CELL
            if ($property['TYPE']->has('work') && $property['TYPE']->has('voice')) {
                $telField = 'tel_work';
            } elseif ($property['TYPE']->has('home') && $property['TYPE']->has('voice')) {
                $telField = 'tel_home';
            } elseif (!$property['TYPE']->has('work') && !$property['TYPE']->has('home') && $property['TYPE']->has('voice') ) {
                $telField = 'tel_other';
            } elseif ( $property['Type']->has('x-evolution-assistant') ) {
                $telField = 'tel_assistent';
            } elseif ( $property['TYPE']->has('car') ) {
                $telField = 'tel_car';
            } elseif ( $property['TYPE']->has('pref') ) {
                $telField = 'tel_prefer';
            }
            
        }
        
        if (!empty($telField)) {
            $data[$telField] = $property->getValue();
        } else {
            parent::_toTine20ModelParseTel($data, $property);
        }
    }
}

