<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @subpackage  Backend
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2019 Metaways Infosystems GmbH (http://www.metaways.de)
 */

/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 * @subpackage  Backend
 */
class Addressbook_Backend_Sql extends Tinebase_Backend_Sql_Abstract
{
    /**
     * Table name without prefix
     *
     * @var string
     */
    protected $_tableName = 'addressbook';
    
    /**
     * Model name
     *
     * @var string
     */
    protected $_modelName = 'Addressbook_Model_Contact';

    /**
     * if modlog is active, we add 'is_deleted = 0' to select object in _getSelect()
     *
     * @var boolean
     */
    protected $_modlogActive = TRUE;
    
    /**
     * should the class return contacts of disabled users
     * 
     * @var boolean
     */
    protected $_getDisabledContacts = FALSE;
    
   /**
    * default column(s) for count
    *
    * @var string
    */
    protected $_defaultCountCol = 'id';
    
    /**
     * foreign tables (key => tablename)
     *
     * @var array
     */
    protected $_foreignTables = array(
        'jpegphoto'    => array(
            'table'         => 'addressbook_image',
            'joinOn'        => 'contact_id',
            'select'        => null, // set by constructor
            'singleValue'   => TRUE,
        ),
        'account_id'    => array(
            'table'         => 'accounts',
            'joinOn'        => 'contact_id',
            'singleValue'   => TRUE,
        ),
    );
    
    /**
     * @see Tinebase_Backend_Sql_Abstract::__construct()
     * @param Zend_Db_Adapter_Abstract $_dbAdapter (optional)
     * @param array $_options (optional)
     */
    public function __construct($_dbAdapter = NULL, $_options = array())
    {
        parent::__construct($_dbAdapter, $_options);
        
        $this->_foreignTables['jpegphoto']['select'] = array('jpegphoto' => $this->_dbCommand->getIfIsNull('addressbook_image.contact_id', 0, 1));
    }
    /**
     * fetch one contact of a user identified by his user_id
     *
     * @param   string $_userId
     * @return  Addressbook_Model_Contact 
     * @throws  Addressbook_Exception_NotFound if contact not found
     */
    public function getByUserId($_userId)
    {
        $select = $this->_getSelect()
            ->where($this->_db->quoteIdentifier('accounts.id') . ' = ?', $_userId)
            ->limit(1);
        
        $stmt = $this->_db->query($select);
        $queryResult = $stmt->fetch();
        $stmt->closeCursor();
                
        if (!$queryResult) {
            throw new Addressbook_Exception_NotFound('Contact with user id ' . $_userId . ' not found.');
        }
        
        $contact = $this->_rawDataToRecord($queryResult);
               
        return $contact;
    }
    
    /**
     * Creates new entry
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Tinebase_Record_Interface
     * @throws  Tinebase_Exception_InvalidArgument
     * @throws  Tinebase_Exception_UnexpectedValue
     */
    public function create(Tinebase_Record_Interface $_record) 
    {
        $contact = parent::create($_record);
        
        if (! empty($_record->jpegphoto)) {
            $contact->jpegphoto = $this->_saveImage($contact->getId(), $_record->jpegphoto);
        }
        
        return $contact;
    }
    
    /**
     * Updates existing entry
     *
     * @param Tinebase_Record_Interface $_record
     * @throws Tinebase_Exception_Record_Validation|Tinebase_Exception_InvalidArgument
     * @return Tinebase_Record_Interface Record|NULL
     */
    public function update(Tinebase_Record_Interface $_record) 
    {
        $contact = parent::update($_record);
        
        if (isset($_record->jpegphoto)) {
            $contact->jpegphoto = $this->_saveImage($contact->getId(), $_record->jpegphoto);
        }
        
        return $contact;
    }
    
    /**
     * get disabled contacts?
     * 
     * @param boolean $_value
     */
    public function setGetDisabledContacts($_value)
    {
        $this->_getDisabledContacts = !!$_value;
    }
    
    /**
     * returns contact image
     *
     * @param int $contactId
     * @return string
     */
    public function getImage($contactId)
    {
        $select = $this->_db->select()
            ->from($this->_tablePrefix . 'addressbook_image', array('image'))
            ->where($this->_db->quoteInto($this->_db->quoteIdentifier('contact_id'). ' = ?', $contactId));
        
        $imageData = $this->_db->fetchOne($select);
        
        return $imageData ? base64_decode($imageData) : '';
    }
    
    /**
     * saves image to db
     *
     * @param  string $contactId
     * @param  string $imageData
     * @return string
     */
    public function _saveImage($contactId, $imageData)
    {
        if ($imageData == 1) {
            // happens for example when record is going to be deleted - do nothing
            return $imageData;
        }

        if (! empty($imageData)) {
            // make sure that we got a valid image blob
            try {
                Tinebase_ImageHelper::getImageInfoFromBlob($imageData);
            } catch (Exception $e) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                    . ' Invalid image blob data, preserving old image');
                return $this->getImage($contactId);
            }
        }
        
        if (! empty($imageData)) {
            $currentImg = $this->getImage($contactId);
            if (empty($currentImg)) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Creating contact image.');
                
                $this->_db->insert($this->_tablePrefix . 'addressbook_image', array(
                    'contact_id'    => $contactId,
                    'image'         => base64_encode($imageData)
                ));
            } else if ($currentImg !== $imageData) {
                if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                    . ' Updating contact image.');
                
                $where  = array(
                    $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $contactId),
                );
                $this->_db->update($this->_tablePrefix . 'addressbook_image', array(
                    'image'         => base64_encode($imageData)
                ), $where);
            }
        } else if ($imageData === '') {
            if (Tinebase_Core::isLogLevel(Zend_Log::INFO)) Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__
                . ' Removing contact image.');
            $this->_db->delete($this->_tablePrefix . 'addressbook_image', $this->_db->quoteInto($this->_db->quoteIdentifier('contact_id') . ' = ?', $contactId));
        }
        
        return $imageData;
    }

    public function updateSyncBackendIds($_contactId, $_syncBackendIds)
    {
        $where  = array(
            $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $_contactId),
        );
        $this->_db->update($this->_tablePrefix . 'addressbook', array(
            'syncBackendIds'         => $_syncBackendIds
        ), $where);
    }

    public function resetAllSyncBackendIds()
    {
        $this->_db->update($this->_tablePrefix . 'addressbook', array(
            'syncBackendIds'         => null
        ));
    }
}
