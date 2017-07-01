<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Convert
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2011-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * class to convert a Evolution vcard to contact model and back again
 *
 * @package     Addressbook
 * @subpackage  Convert
 */
class Addressbook_Convert_Contact_VCard_Evolution extends Addressbook_Convert_Contact_VCard_Abstract
{
    // Mozilla/5.0 (X11; Linux i686) KHTML/4.7.3 (like Gecko) Konqueror/4.7
    // KDE DAV groupware client
    const HEADER_MATCH = '/Evolution\/(?P<version>.*)/';
    
    protected $_emptyArray = array(
        'adr_one_countryname'   => null,# 1 1
        'adr_one_locality'      => null,# 1 1
        'adr_one_postalcode'    => null,# 1 1
        'adr_one_region'        => null,# 1 1
        'adr_one_street'        => null,# 1 1
        'adr_one_street2'       => null,# 1 1
        'adr_two_countryname'   => null,# 1 1
        'adr_two_locality'      => null,# 1 1
        'adr_two_postalcode'    => null,# 1 1
        'adr_two_region'        => null,# 1 1
        'adr_two_street'        => null,# 1 1
        'adr_two_street2'       => null,# 1 1
        'assistent'             => null,# 1 1
        'bday'                  => null,# 1 1
        'calendar_uri'          => null,# 1 1
        'email'                 => null,# 1 1
        'email_home'            => null,# 1 1
        'jpegphoto'             => null,# 1 1
        'freebusy_uri'          => null,# 1 1
        'note'                  => null,# 1 1
        'role'                  => null,# 1 1
        #'salutation'            => null,
        'title'                 => null,# 1 1
        'url'                   => null,# 1 1
        'url_home'              => null,# 1 1
        'n_family'              => null,# 1 1
        'n_fileas'              => null,# 1 1
        'n_fn'                  => null,# 1 1
        'n_given'               => null,# 1 1
        'n_middle'              => null,# 1 1
        'n_prefix'              => null,# 1 1
        'n_suffix'              => null,# 1 1 
        'org_name'              => null,# 1 1
        'org_unit'              => null,# 1 1
        #'pubkey'                => null,
        'room'                  => null,
        'tel_assistent'         => null,# 1 1
        'tel_car'               => null,# 1 1
        'tel_cell'              => null,# 1 1
        'tel_cell_private'      => null,# 0 0
        'tel_fax'               => null,# 1 1
        'tel_fax_home'          => null,# 1 1
        'tel_home'              => null,# 1 1
        'tel_pager'             => null,# 1 1
        'tel_work'              => null,# 1 1
        'tel_other'             => null,# 1 1
        'tel_prefer'            => null,# 1 1
        #'tz'                    => null,
        #'geo'                   => null,
        #'lon'                   => null,
        #'lat'                   => null,
        'tags'                  => null,# 1 1
        'notes'                 => null,# 1
    );
        
    /**
     * converts Addressbook_Model_Contact to vcard
     * 
     * @todo return all supported fields in correct format see http://forge.tine20.org/mantisbt/view.php?id=5346
     * @param  Addressbook_Model_Contact  $_record
     * @return \Sabre\VObject\Component\VCard
     */
    public function fromTine20Model(Tinebase_Record_Abstract $_record)
    {
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' contact ' . print_r($_record->toArray(), true));
        
        // initialize vcard object
        $card = $this->_fromTine20ModelRequiredFields(
            $_record,
            trim($_record->n_prefix . " " . $_record->n_given . " " . $_record->n_middle . " " . $_record->n_family . " " . $_record->n_suffix), 
            array($_record->org_name, $_record->org_unit,$_record->room)
        );
/*
FN:DI. Dr. Christine Johanna Huber Jr.
N:Huber;Christine;Johanna;DI. Dr.;Jr.
            'N'       => array($record->n_family, $record->n_given, $record->n_middle, $record->n_prefix, $record->n_suffix),
*/
	$card->add('X-EVOLUTION-FILE-AS',$_record->n_fileas);
	$card->add('ROLE',$_record->role);
	$card->add('TITLE',$_record->role);
        $card->add('X-EVOLUTION-ASSISTANT',$_record->assistent);
        $card->add('TEL', $_record->tel_work, array('TYPE' => array('WORK','VOICE')));
        
        $card->add('TEL', $_record->tel_home, array('TYPE' => array('HOME','VOICE')));
        
//        $card->add('TEL', $_record->tel_cell, array('TYPE' => 'X-EVOLUTION-COMPANY"'));
        
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

