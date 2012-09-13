<?php
/**
 * Tine 2.0
 * 
 * vcard import class for the addressbook
 *
 * @package     Addressbook
 * @subpackage  Import
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Yann Le Moigne <segfaultmaker@gmail.com>
 * @copyright   Copyright (c) 2011-2012 Metaways Infosystems GmbH (http://www.metaways.de)
 */

require_once 'vcardphp/vcard.php';

/**
 * vcard import class for the addressbook
 * 
 * @package     Addressbook
 * @subpackage  Import
 * @see ftp://ftp.rfc-editor.org/in-notes/rfc2426.txt
 */
class Addressbook_Import_VCard extends Tinebase_Import_Abstract
{
    /**
     * @var array
     */
    protected $_options = array(
        'encoding'          => 'auto',
        'encodingTo'        => 'UTF-8',
        'dryrun'            => FALSE,
        'duplicateCount'    => 0,
        'updateMethod'      => 'update',
        'createMethod'      => 'create',
        'model'             => '',
        'urlIsHome'            => 0,
        'mapNicknameToField'=> '',
        'useStreamFilter'   => FALSE,
        'shared_tags'       => 'create', //'onlyexisting',
        'autotags'          => array(),
    );
    
    /**
     * additional config options
     * 
     * @var array
     */
    protected $_additionalOptions = array(
        'container_id'      => '',
    );
    
    /**
     * creates a new importer from an importexport definition
     * 
     * @param  Tinebase_Model_ImportExportDefinition $_definition
     * @param  array                                 $_options
     * @return Addressbook_Import_VCard
     * 
     * @todo move this to abstract when we no longer need to be php 5.2 compatible
     */
    public static function createFromDefinition(Tinebase_Model_ImportExportDefinition $_definition, array $_options = array())
    {
        return new Addressbook_Import_VCard(self::getOptionsArrayFromDefinition($_definition, $_options));
    }

    /**
     * constructs a new importer from given config
     * 
     * @param array $_options
     */
    public function __construct(array $_options = array())
    {
        parent::__construct($_options);
        
        if (empty($this->_options['model'])) {
            throw new Tinebase_Exception_InvalidArgument(get_class($this) . ' needs model in config.');
        }
        
        $this->_setController();
        
        // don't set geodata for imported contacts as this is too much traffic for the nominatim server
        $this->_controller->setGeoDataForContacts(FALSE);
        
        // get container id from default container if not set
        if (empty($this->_options['container_id'])) {
            $defaultContainer = $this->_controller->getDefaultAddressbook();
            $this->_options['container_id'] = $defaultContainer->getId();
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Setting default container id: ' . $this->_options['container_id']);
        }
    }
    
    /**
     * add some more values (container id)
     *
     * @return array
     */
    protected function _addData()
    {
        $result['container_id'] = $this->_options['container_id'];
        return $result;
    }
    
    /**
     * import the data
     *
     * @param  resource $_resource (if $_filename is a stream)
     * @param  array $_clientRecordData
     * @return array with Tinebase_Record_RecordSet the imported records (if dryrun) and totalcount 
     */
    public function import($_resource = NULL, $_clientRecordData = array())
    {
        $this->_initImportResult();
        
        $lines = array();
        while ($line = fgets($_resource)) {
            $lines[] = str_replace("\n", "", $line);
        }
        
        $this->_doImport($lines, $_clientRecordData);
        $this->_logImportResult();
        
        return $this->_importResult;
    }
    
    /**
     * get raw data of a single record
     * 
     * @param  mixed $_resource
     * @return array
     */
    protected function _getRawData(&$_resource) 
    {
        $card = new VCard();
        
        $result = FALSE;
        while ($card->parse($_resource)) {
            if ($card->getProperty('N')) {
                return $card;
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No N property: ' . print_r($card, TRUE));
            }
        }
        
        return FALSE;
    }
    
    /**
     * do the mapping and replacements
     *
     * @param VCard $card
     * @param array $_headline [optional]
     * @return array
     * 
     * @todo split this into smaller parts
     */
    protected function _doMapping($card)
    {
        $data = array();

        $data = $this->_getName($card, $data);
        $data = $this->_getPhoto($card, $data);
        $data = $this->_getUrl($card, $data);
        
        // TODO check sample format support
        // BDAY:1996-04-15
        // BDAY:1953-10-15T23:10:00Z
        // BDAY:1987-09-27T08:30:00-06:00
        if ($card->getProperty('BDAY')) $data['bday'] = $card->getProperty('BDAY')->value;
        
        $addressProperty = ($card->getProperty('ADR')) ? 'ADR' : (($card->getProperty('ITEM1.ADR')) ? 'ITEM1.ADR' : '');
        if ($addressProperty) {
            $properties = $card->getProperties($addressProperty);
            foreach ($properties as $property){
                // types available from RFC : 'dom', 'intl', 'postal', 'parcel', 'home', 'work', 'pref'
                $types = $property->params['TYPE'];
                
                //post office box; the extended address; the street
                //address; the locality (e.g., city); the region (e.g., state or
                //province); the postal code; the country name
                
                $components = $property->getComponents();
                
                if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ 
                    . ' Address components ' . print_r($components, TRUE));
                
                $mapping = array(NULL, 'street2', 'street', 'locality', 'region', 'postalcode', 'countryname');
                $adrType = ($types && in_array_case($types, 'home')) ? 'two' : 'one';
                foreach ($components as $index => $value) {
                    if (! isset($mapping[$index]) || $mapping[$index] === NULL) {
                        continue;
                    }
                    
                    $data['adr_' . $adrType . '_' . $mapping[$index]] = $value;
                }
            }
        }
        
        // $properties = $card->getProperties('LABEL'); //NOT_IMPLEMENTED
        if ($card->getProperty('TEL')) {
            $properties = $card->getProperties('TEL');
            foreach($properties as $property){
                // types available from RFC : "home", "msg", "work", "pref", "voice", "fax", "cell", "video", "pager", "bbs", "modem", "car", "isdn", "pcs"
                $types = $property->params['TYPE'];
                
                $key = 'tel_work';
                if($types){
                    if(in_array_case($types, 'home') && !in_array_case($types, 'cell') && !in_array_case($types, 'fax')){
                        $key = 'tel_home';    
                    }else if(in_array_case($types, 'home') && in_array_case($types, 'cell')){
                        $key = 'tel_cell_private';
                    }else if(in_array_case($types, 'home') && in_array_case($types, 'fax')){
                        $key = 'tel_fax_home';
                    }else if(in_array_case($types, 'work') && !in_array_case($types, 'cell') && !in_array_case($types, 'fax')){
                        $key = 'tel_work';
                    }else if(in_array_case($types, 'work') && in_array_case($types, 'cell')){
                        $key = 'tel_cell';
                    }else if(in_array_case($types, 'work') && !in_array_case($types, 'fax')){
                        $key = 'tel_fax';
                    }else if(in_array_case($types, 'car')){
                        $key = 'tel_car';
                    }else if(in_array_case($types, 'pager')){
                        $key = 'tel_pager';
                    }else if(in_array_case($types, 'fax')){
                        $key = 'tel_fax';
                    }else if(in_array_case($types, 'cell')){
                        $key = 'tel_cell';
                    }
                }
                $data[$key] = $property->value;
                
                //$data['tel_assistent'] = ''; //RFC has *a lot* of type, but not this one ^^
            }
        }
        
        if ($card->getProperty('EMAIL')) {
            $properties = $card->getProperties('EMAIL');
            foreach($properties as $property){
                // types available from RFC (custom allowed): "internet", "x400", "pref"
                // home and work are commons, so we manage them
                $types = $property->params['TYPE'];
                
                $key = 'email';
                if($types){
                    if(in_array_case($types, 'home')){
                        $key = 'email_home';    
                    }
                }
                $data[$key] = $property->value;
            }
        }
        
        // $properties = $card->getProperties('MAILER'); //NOT_IMPLEMENTED
        
        // TODO Check samples are supported
        // TZ:-05:00
        // TZ;VALUE=text:-05:00; EST; Raleigh/North America
        if ($card->getProperty('TZ')) $data['tz'] = $card->getProperty('TZ')->value;
        // $properties = $card->getProperties('GEO'); //NOT_IMPLEMENTED
        if ($card->getProperty('TITLE')) $data['title'] = $card->getProperty('TITLE')->value;
        if ($card->getProperty('ROLE')) $data['role'] = $properties = $card->getProperty('ROLE')->value;
        // $properties = $card->getProperties('LOGO'); // NOT_IMPLEMENTED
        
        // Type can be a specification "secretary", "assistant", etc.
        // Value can be a URI or a nested VCARD...
        // $data['assistent'] = $properties = $card->getProperties('AGENT'); // NESTED VCARD NOT SUPPORTED BY vcardphp
        
        if ($card->getProperty('ORG')) {
            $components = $card->getProperty('ORG')->getComponents();
            $data['org_name'] = $components[0];
            $data['org_unit'] = '';
            for($i=1; $i < count($components); $i++){
                $data['org_unit'] .= $components[$i].";";
            }
        }
        
        // $properties = $card->getProperties('CATEGORIES'); // NOT_IMPLEMENTED
        if ($card->getProperty('NOTE')) $data['note'] = $card->getProperty('NOTE')->value;
        // $properties = $card->getProperties('PRODID'); // NOT_IMPLEMENTED
        // $properties = $card->getProperties('REV'); // NOT_IMPLEMENTED (could be with tine20 modification history)
        // $properties = $card->getProperties('SORT-STRING'); // NOT_IMPLEMENTED
        // $properties = $card->getProperties('SOUND'); // NOT_IMPLEMENTED
        // $properties = $card->getProperties('UID'); // NOT_IMPLEMENTED
        // $properties = $card->getProperties('VERSION'); // NOT_IMPLEMENTED
        // $properties = $card->getProperties('CLASS'); // NOT_IMPLEMENTED
        // TODO $data['pubkey'] = $properties = $card->getProperties('KEY'); // NOT_IMPLEMENTED // missing binary uncode
        
        return $data;
    }
    
    /**
     * get name from vcard
     * 
     * @param VCard $_card
     * @param array $_data
     * @return array
     */
    function _getName(VCard $_card, $_data)
    {
        $_data['n_fn'] = $_card->getProperty('FN')->value;
        
        $components = $_card->getProperty('N')->getComponents();
        $_data['n_family'] = (isset($components[0])) ? $components[0] : '';
        $_data['n_given']  = (isset($components[1])) ? $components[1] : '';
        $_data['n_middle'] = (isset($components[2])) ? $components[2] : '';
        $_data['n_prefix'] = (isset($components[3])) ? $components[3] : '';
        $_data['n_suffix'] = (isset($components[4])) ? $components[4] : '';
        
        // Tine20 don't support nickname, but it's a common feature, so this allow mapping to customField
        if (strlen($this->_options['mapNicknameToField'])>0) {
            if ($_card->getProperty('NICKNAME')) $_data[$this->_options['mapNicknameToField']] = $_card->getProperty('NICKNAME')->value;
        }
        
        return $_data;
    }

    /**
     * get photo from vcard
     * 
     * @param VCard $_card
     * @param array $_data
     * @return array
     * 
     * @todo make this work / need to add base64 decode 
     */
    function _getPhoto(VCard $_card, $_data)
    {
        if ($_card->getProperty('PHOTO')) {
            // not implemented
        }
        
        return $_data;
    }

    /**
     * get url from vcard
     * 
     * @param VCard $_card
     * @param array $_data
     * @return array
     */
    function _getUrl(VCard $_card, $_data)
    {
        $urlProperty = ($_card->getProperty('URL')) ? 'URL' : (($_card->getProperty('ITEM2.URL')) ? 'ITEM2.URL' : '');
        if (empty($urlProperty)) {
            return $_data;
        }
        
        $key = 'url';
        if ($this->_options['urlIsHome']) {
            $key = 'url_home';
        }
        $_data[$key] = $_card->getProperty($urlProperty)->value;
        $_data[$key] = preg_replace('/\\\\/', '', $_data[$key]);
        
        return $_data;
    }
}
