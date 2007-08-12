<?php
/**
 * backend class for Zend_Json_Server
 * 
 * This class handles all Json requests for the addressbook application
 * 
 * @package Addressbook
 * @version $Id$
 *
 */
class Addressbook_Json
{
    /**
     * delete a array of contacts
     *
     * @param array $_contactIDs
     * @return array
     */
    public function deleteAddress($_contactIDs)
    {
        $contactIDs = Zend_Json::decode($_contactIDs);
        if(is_array($contactIDs)) {
            $contacts = Addressbook_Contacts::factory(Addressbook_Contacts::SQL);
            $contacts->deletePersonalContacts($contactIDs);

            $result = array('success'   => TRUE, 'ids' => $contactIDs);
        } else {
            $result = array('success'   => FALSE);
        }
        
        return $result;
    }
    
    /**
     * read one contact
     *
     * @param int $_contactID
     * @return array
     */
    public function readAddress($_contactID)
    {
        $addresses = new Addressbook_Addresses();
        if($rows = $addresses->find($_contactID)) {
            $result['results'] = $rows->toArray();
        }
        
        return $result;
    }
	
    /**
     * save one contact
     * 
     * if $_contactID is NULL the contact gets added, otherwise it gets updated
     *
     * @param int $_contactID
     * @return array
     */
    public function saveAddress($_contactID = NULL)
    {
        $input = new Zend_Filter_Input(Addressbook_Addresses::getFilter(), Addressbook_Addresses::getValidator(), $_POST);
        
        if ($input->isValid()) {
            $address = new Addressbook_Addresses();
            
            $data = $input->getUnescaped();
            if(isset($data['contact_bday'])) {
                $locale = Zend_Registry::get('locale');
                $dateFormat = $locale->getTranslationList('Dateformat');
                // convert bday back to yyyy-mm-dd
                try {
                    $date = new Zend_Date($data['contact_bday'], $dateFormat['long'], 'en');
                    $data['contact_bday'] = $date->toString('yyyy-MM-dd');
                } catch (Exception $e) {
                    unset($data['contact_bday']);
                }
            }
            
            if($_contactID > 0) {
                try {
                    $where = $address->getAdapter()->quoteInto('contact_id = ?', (int)$_contactID);
                    $address->update($data, $where);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry updated');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            } else {
                try {
                    $address->insert($data);
                    $result = array('success'           => true,
                                    'welcomeMessage'    => 'Entry saved');
                } catch (Exception $e) {
                    $result = array('success'           => false,
                                    'errorMessage'      => $e->getMessage());
                }
            }
        } else {
            foreach($input->getMessages() as $fieldName => $errorMessages) {
                $errors[] = array('id'  => $fieldName,
                                  'msg' => $errorMessages[0]);
            }
            
            $result = array('success'           => false,
                            'errors'            => $errors,
                            'errorMessage'      => 'filter NOT ok');
        }
        
        return $result;
    }
    
    /**
     * get data for overview
     * 
     * returns the data to be displayed in a ExtJS grid
     *
     * @param string $nodeid
     * @param string $_datatype
     * @param int $start
     * @param int $sort
     * @param string $dir
     * @param int $limit
     * @return array
     */
    public function getData($nodeid, $_datatype, $start, $sort, $dir, $limit)
    {
        $result = array();

        switch($nodeid) {
            case 'internaladdresses':
                $contacts = Addressbook_Contacts::factory(Addressbook_Contacts::SQL);
                if($rows = $contacts->getInternalContacts(NULL, $sort, $dir, $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $contacts->getInternalCount();
                }
                
                break;

            case 'myaddresses':
                $contacts = Addressbook_Contacts::factory(Addressbook_Contacts::SQL);
                if($rows = $contacts->getPersonalContacts(NULL, $sort, $dir, $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $contacts->getPersonalCount();
                }
                
                break;

            default:
                $contacts = Addressbook_Contacts::factory(Addressbook_Contacts::SQL);
                if($rows = $contacts->fetchAll(NULL, "$sort $dir", $limit, $start)) {
                    $result['results'] = $rows->toArray();
                    $result['totalcount'] = $contacts->getTotalCount();
                }
                
                break;
        }
        
        return $result;
    }
    
    /**
     * Returns the structure of the initial tree for this application.
     * 
     * This function returns the needed structure, to display the initial tree, after the the logoin.
     * Additional tree items get loaded on demand.
     *
     * @return array
     */
    public function getMainTree()
    {
        $treeNode = new Egwbase_Ext_Treenode('Addressbook', 'overview', 'addressbook', 'Addressbook', FALSE);
        $treeNode->setIcon('apps/kaddressbook.png');
        $treeNode->cls = 'treemain';
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'myaddresses', 'My Addresses', TRUE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'internaladdresses', 'My Fellows', TRUE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'fellowsaddresses', 'Fellows Addresses', FALSE);
        $treeNode->addChildren($childNode);
        
        $childNode = new Egwbase_Ext_Treenode('Addressbook', 'address', 'sharedaddresses', 'Shared Addresses', FALSE);
        $treeNode->addChildren($childNode);
        
        return $treeNode;
    }
}
?>