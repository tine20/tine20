<?php
/**
 * Tine 2.0
 *
 * @package     Felamimail
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 * 
 * @todo        add body of messages of last week (?) to cache?
 */

/**
 * cache controller for Felamimail messages
 *
 * @package     Felamimail
 * @subpackage  Controller
 */
class Felamimail_Controller_Cache_Message extends Tinebase_Controller_Abstract
{
    /**
     * application name (is needed in checkRight())
     *
     * @var string
     */
    protected $_applicationName = 'Felamimail';
    
    /**
     * @var default charset
     */
    protected $_encoding = 'UTF-8';
    
    /**
     * stepwidth of uid in one caching step
     *
     * @var integer
     */
    protected $_uidStepWidth = 10000;
    
    /**
     * number of imported messages in one caching step
     *
     * @var integer
     */
    protected $_importCountPerStep = 20;
    
    /**
     * message backend
     *
     * @var Felamimail_Backend_Cache_Sql_Message
     */
    protected $_backend = NULL;
    
    /**
     * holds the instance of the singleton
     *
     * @var Felamimail_Controller_Cache_Message
     */
    private static $_instance = NULL;
    
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton
     */
    private function __construct() {
        $this->_backend = new Felamimail_Backend_Cache_Sql_Message();
        $this->_currentAccount = Tinebase_Core::getUser();
    }
    
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone() 
    {        
    }
    
    /**
     * the singleton pattern
     *
     * @return Felamimail_Controller_Cache_Message
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Felamimail_Controller_Cache_Message();
        }
        
        return self::$_instance;
    }
    
    /***************************** public funcs *******************************/
    
    /**
     * update message cache
     * 
     * @param string|Felamimail_Model_Folder $_folder
     * @param integer $_time in seconds
     * @return Felamimail_Model_Folder folder status (in cache)
     */
    public function update($_folder, $_time = 10)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        // check fencing and invalid cache/imap status here
        if (! $this->_isUpdateAllowed($folder)) {
            return $folder;
        }
        
        try {
            ///////////////////////////// get imap backend and init stuff
            
            $imap = Felamimail_Backend_ImapFactory::factory($folder->account_id);

            // fill message cache (from the top) / init time / update folder (cache_timestamp, status, estimate)
            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_UPDATING;
            $folder = $this->_updateFolderStatus($folder);
                        
            $folder->cache_recentcount = 0;
            $timeLeft = TRUE;
            
            if ($folder->cache_job_lowestuid == 0) {
                $folder->cache_job_lowestuid = ($folder->imap_uidnext) ? $folder->imap_uidnext : $folder->imap_totalcount;
                $folder->cache_job_startuid = $folder->cache_job_lowestuid; 
            }
            // @todo this should be initialized by the model/clear()
            if (! $folder->cache_uidnext) {
                $folder->cache_uidnext = 1;
            }
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values before import: ' . print_r($folder->toArray(), TRUE));
            
            // remove old \Recent flag from cached messages
            $this->_backend->clearFlag($folder->getId(), Zend_Mail_Storage::FLAG_RECENT, 'folder');
            
            ///////////////////////////// get missing uids from imap
             
            // select folder and get all missing message uids from cache_job_lowestuid (imap_uidnext) to cache_uidnext
            $imap->selectFolder($folder->globalname);
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Try to get message uids from ' . $folder->cache_job_lowestuid . ' to ' . $folder->cache_uidnext . ' from imap server.'
            );

            ///////////////////////////// main message import loop
            
            $count = 0;
            $uids = array();
            if ($folder->imap_uidnext != $folder->cache_uidnext) {
                while (
                    // more messages to add ?
                    $folder->cache_job_lowestuid > $folder->cache_uidnext &&
                    $timeLeft &&
                    // initial import (this should not run until lowest uid reaches 1)
                    ($folder->cache_uidnext != 1 || $folder->imap_totalcount > $folder->cache_totalcount)
                ) {
                    // get summary and add messages
                    if (empty($uids)) {
                        if ($folder->imap_uidnext) {
                            $stepLowestUid = max($folder->cache_job_lowestuid - $this->_uidStepWidth, $folder->cache_uidnext);
                            $uids = $imap->getUidbyUid($folder->cache_job_lowestuid - 1, $stepLowestUid);
                            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($uids) 
                            //    . ' new uids from IMAP server: ' . ($folder->cache_job_lowestuid - 1) . ' - ' . $stepLowestUid);
                        } else {
                            // imap servers without uidnext
                            $stepLowestUid = $folder->cache_uidnext;
                            $uids = $imap->getUid($folder->cache_uidnext, $folder->cache_job_lowestuid);
                            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($uids) 
                            //    . ' new uids from IMAP server: ' . ($folder->cache_uidnext) . ' - ' . $folder->cache_job_lowestuid);
                        }
                        
                        rsort($uids, SORT_NUMERIC);
                    }
                    
                    if (! empty($uids)) {
                        $nextUids = array_splice($uids, 0, min($this->_importCountPerStep, count($uids)));
                        
                        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Importing uids: ' . print_r($nextUids, TRUE));
                        $messages = $imap->getSummary($nextUids);
                        $count += $this->_addMessages($messages, $folder);
                        
                        $folder->cache_job_lowestuid = ($folder->imap_uidnext) ? min($nextUids) : ($folder->imap_totalcount - $folder->cache_totalcount);
                    } else {
                        $folder->cache_job_lowestuid = $stepLowestUid;
                    }
                    
                    $timeLeft = ($folder->cache_timestamp->compare(Zend_Date::now()->subSecond($_time)) == 1);
                }
            }
            
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Finished import run  ... Time: ' . Zend_Date::now()->toString() 
                . ' Added messages to cache (new/recent): ' . $count . ' / ' . $folder->cache_recentcount);
            
            ///////////////////////////// sync deleted messages or start again to add recent messages
                
            if ($timeLeft) {
                if ($folder->cache_totalcount > $folder->imap_totalcount) {
                    // sync deleted if we still got time left and totalcounts mismatch
                    $this->_syncDeletedMessages($folder, $_time, $imap);
                } else {
                    $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
                }
            }
            
            // need to start again, we did not import some messages -> invalidate cache
            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE && $folder->cache_totalcount < $folder->imap_totalcount) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Need to start import again ... :(');
                $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INVALID;
            }
                
            ///////////////////////////// finished with import run
            
            if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_COMPLETE) {
                $folder->cache_uidnext = $folder->imap_uidnext;
                $folder->cache_job_lowestuid = 0;
            } else if ($folder->cache_status == Felamimail_Model_Folder::CACHE_STATUS_UPDATING) {
                $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
            }
            
        } catch (Zend_Mail_Protocol_Exception $zmpe) {
            // some error with the imap server
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getMessage());
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . $zmpe->getTraceAsString());
            $folder->imap_status = Felamimail_Model_Folder::IMAP_STATUS_DISCONNECT;
            $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE;
        }

        // update and return folder
        $folder = $this->_updateFolderStatus($folder);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Folder values after import: ' . print_r($folder->toArray(), TRUE));
        return $folder;
    }
    
    /**
     * remove all cached messages for this folder and reset folder values / folder status is updated in the database
     *
     * @param string|Felamimail_Model_Folder $_folder
     * @return Felamimail_Model_Folder
     */
    public function clear($_folder)
    {
        $folder = ($_folder instanceof Felamimail_Model_Folder) ? $_folder : Felamimail_Controller_Folder::getInstance()->get($_folder);
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Clearing cache of ' . $folder->globalname);
        
        $this->_backend->deleteByFolderId($folder->getId());
        
        // set folder uidnext + uidvalidity to 0 and reset cache status
        $folder->imap_uidnext       = 0;
        $folder->imap_uidvalidity   = 0;
        $folder->imap_totalcount    = 0;
        $folder->imap_recentcount   = 0;
        $folder->imap_timestamp     = NULL;
        $folder->cache_timestamp    = NULL;
        $folder->cache_uidnext      = 1;
        $folder->cache_totalcount   = 0;
        $folder->cache_recentcount  = 0;
        $folder->cache_unreadcount  = 0;
        $folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_EMPTY;
        $folder->cache_job_lowestuid = 0;
        $folder->cache_job_startuid = 0;
        $folder->cache_job_actions_estimate = 0;
        $folder->cache_job_actions_done = 0;
        $folder = Felamimail_Controller_Folder::getInstance()->update($folder);
        
        return $folder;
    }
    
    /**
     * check if folder cache is updating atm
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean
     * 
     * @todo we should check the time of the last update to dynamically decide if process could have died
     */
    public function isUpdating(Felamimail_Model_Folder $_folder)
    {
        if (! $_folder->cache_timestamp instanceof Zend_Date 
                || $_folder->cache_status != Felamimail_Model_Folder::CACHE_STATUS_UPDATING
        ) {
            return FALSE;
        }
        
        if ($_folder->cache_timestamp->compare(Zend_Date::now()->subMinute(5)) == -1) {
            // it seems that the old import process ended (timestamp is older than 5 mins) -> commence
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ 
                . ' Old initial import process ended without finishing: '
                . $_folder->cache_timestamp->toString() . ' / ' . Zend_Date::now()->subMinute(5)->get() 
                . ' Starting new import for folder ' 
                . $_folder->globalname . ' ... '
            );
            $result = FALSE;
            
        } else {
            // do nothing
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ 
                . ' Caching of folder ' . $_folder->globalname 
                . ' is running since ' . $_folder->cache_timestamp->toString());
                
            $result = TRUE;
        }
        
        return $result;
    }
    
    /***************************** protected funcs *******************************/

    /**
     * check fencing and invalid cache/imap status here
     * 
     * @param Felamimail_Model_Folder $_folder
     * @return boolean TRUE if update can begin / FALSE if no update required or not possible
     */
    protected function _isUpdateAllowed(Felamimail_Model_Folder $_folder)
    {
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Checking folder status of ' . $_folder->globalname . ' ...');
        
        // check imap connection
        if ($_folder->imap_status == Felamimail_Model_Folder::IMAP_STATUS_DISCONNECT) {
            Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Lost imap connection. Do not update the cache.');
            return FALSE;
        }
        
        switch ($_folder->cache_status) {
            case Felamimail_Model_Folder::CACHE_STATUS_UPDATING:
                $result = ! $this->isUpdating($_folder);
                break;
            case Felamimail_Model_Folder::CACHE_STATUS_COMPLETE:
                Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Caching of folder ' . $_folder->globalname . ' is already complete.');
                $result = FALSE;
                break;
            case Felamimail_Model_Folder::CACHE_STATUS_INVALID:
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Folder cache of ' . $_folder->globalname . ' is invalid. Clearing cache ...');
                $this->clear($_folder);
                $result = TRUE;
                break;
            default:
                $result = TRUE;
        }
        
        return $result;
    }
    
    /**
     * updates the folder status in the folder cache database and calculate estimate
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param Zend_Date $_timestamp
     * @return Felamimail_Model_Folder
     * @throws Felamimail_Exception
     * 
     * @todo we should save the time of update process
     */
    protected function _updateFolderStatus(Felamimail_Model_Folder $_folder, $_timestamp = NULL)
    {
        $_folder->cache_timestamp = ($_timestamp !== NULL) ? $_timestamp : Zend_Date::now();
        
        switch ($_folder->cache_status) {
            case Felamimail_Model_Folder::CACHE_STATUS_UPDATING:
                if ($_folder->cache_job_actions_done == $_folder->cache_job_actions_estimate) {
                    // calc new estimate
                    $_folder->cache_job_actions_done = 0;
                    $_folder->cache_job_actions_estimate = abs($_folder->imap_totalcount - $_folder->cache_totalcount);
                    if ($_folder->cache_job_actions_estimate == 0) {
                        // messages have been deleted and new messages have been added
                        if ($_folder->cache_uidnext < $_folder->imap_uidnext) {
                            $_folder->cache_job_actions_estimate = abs($_folder->imap_uidnext - $_folder->cache_uidnext);
                        } else {
                            $_folder->cache_uidnext = $_folder->imap_uidnext;
                        }
                    }
                    Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Actions estimate: ' . $_folder->cache_job_actions_estimate);
                    
                } else if ($_folder->cache_job_actions_done > $_folder->cache_job_actions_estimate) {
                    // sanitize actions
                    $_folder->cache_job_actions_estimate = $_folder->imap_totalcount;
                    $_folder->cache_job_actions_done = $_folder->cache_totalcount;
                }
                $message = ' Starting cache update.';
                break;
            case Felamimail_Model_Folder::CACHE_STATUS_COMPLETE:
                $_folder->cache_job_actions_done = $_folder->cache_job_actions_estimate;
                $message = ' Finished update.';
                break;
            case Felamimail_Model_Folder::CACHE_STATUS_INCOMPLETE:
                $message = ' Update incomplete.';
                break;
            case Felamimail_Model_Folder::CACHE_STATUS_INVALID:
                $message = ' Update broken. Invalidating cache.';
                break;
        }
        
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . $message . ' Updating folder status now. Time: ' . $_folder->cache_timestamp);
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($_folder->toArray(), TRUE));
        
        return Felamimail_Controller_Folder::getInstance()->update($_folder);
    }
    
    /**
     * add messages to cache and increase folder counts
     *
     * @param array $_messages
     * @param Felamimail_Model_Folder $_folder
     * @return integer count
     * 
     * @todo use this or _addMessagesPrepared?
     * @todo get replyTo & inReplyTo?
     */
    protected function _addMessages($_messages, $_folder)
    {
        // set fields with try / catch blocks
        $exceptionFields = array('subject', 'to', 'cc', 'bcc', 'content_type', 'from', 'sent');
        
        $count = 0;
        foreach ($_messages as $uid => $value) {
            $message = $value['message'];
            $subject = '';
            
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($message, true));
            //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' caching message ' . $message->subject);
            
            try {
                $cachedMessage = new Felamimail_Model_Message(array(
                    'messageuid'    => $uid,
                    'folder_id'     => $_folder->getId(),
                    'timestamp'     => Zend_Date::now(),
                    'received'      => $this->_convertDate($value['received'], Felamimail_Model_Message::DATE_FORMAT_RECEIVED),
                    'size'          => $value['size'],
                    'flags'         => $message->getFlags(),
                ));
                
                // try to get optional fields
                foreach ($exceptionFields as $field) {
                    try {
                        switch ($field) {
                            case 'subject':
                                $cachedMessage->subject = Felamimail_Message::convertText($message->subject);
                                $subject = $cachedMessage->subject;
                                break;
                            case 'content_type':
                                $cachedMessage->content_type = $message->contentType;
                                break;
                            case 'from':
                                $cachedMessage->from = Felamimail_Message::convertText($message->from, TRUE, 256);
                                // unquote meta chars
                                $cachedMessage->from = preg_replace("/\\\\([\[\]\*\?\+\.\^\$\(\)]+)/", "$1", $cachedMessage->from);
                                break;
                            case 'sent':
                                $cachedMessage->sent = $this->_convertDate($message->date);
                                break;
                            default:
                                if (in_array($field, array('to', 'cc', 'bcc'))) {
                                    // need to check if field is set in message first
                                    $cachedMessage->{$field} = (isset($message->{$field})) ? $this->_convertAddresses($message->{$field}) : array();
                                }
                        }
                    } catch (Zend_Mail_Exception $zme) {
                        // no 'subject', 'to', 'cc', 'bcc', from, sent or content_type available
                        if (in_array($field, array('to', 'cc', 'bcc'))) {
                            $cachedMessage->{$field} = array();
                        } else if ($field == 'sent') {
                            $cachedMessage->{$field} = new Zend_Date(0);
                        } else {
                            $cachedMessage->{$field} = '';
                        }
                    }
                }
                
                //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($cachedMessage->toArray(), true));
                
                $createdMessage = $this->_backend->create($cachedMessage);
                
                // count unseen and Zend_Mail_Storage::FLAG_RECENT 
                if (! in_array(Zend_Mail_Storage::FLAG_SEEN, $cachedMessage->flags)) {
                    $_folder->cache_recentcount++;
                    $_folder->cache_unreadcount++;
                    $this->_backend->addFlag($createdMessage, Zend_Mail_Storage::FLAG_RECENT);
                }
                
                $count++;
                $_folder->cache_job_actions_done++;
                
            } catch (Zend_Mail_Exception $zme) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zme->getMessage()
                );
            } catch (Zend_Db_Statement_Exception $zdse) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . 
                    ' Failed to create cache entry for msg ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zdse->getMessage()
                );
            } catch (Zend_Date_Exception $zde) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                    ' Could not parse message ' . $uid . ' | ' . $subject .
                    '. Error: ' . $zde->getMessage()
                );
            }
        }
        
        $_folder->cache_totalcount += $count;
        //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Added ' . $count . ' messages to DB.');
        
        return $count;
    }
    
    /**
     * sync deleted messages
     * 
     * @param Felamimail_Model_Folder $_folder
     * @param integer $_time
     * @param Felamimail_Backend_Imap $_imap
     * @return void
     */
    protected function _syncDeletedMessages(Felamimail_Model_Folder $_folder, $_time, Felamimail_Backend_Imap $_imap)
    {
        Tinebase_Core::getLogger()->info(__METHOD__ . '::' . __LINE__ . ' Syncing deleted messages in folder ' . $_folder->globalname);
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' before deleted sync: ' . print_r($_folder->toArray(), TRUE));
        
        // update folder with estimate / actions
        $_folder->cache_job_actions_estimate += abs($_folder->cache_totalcount - $_folder->imap_totalcount);
        
        $timeLeft = TRUE;
        $start = 0;
        $stepSize = 50;
        while ($timeLeft && $_folder->cache_totalcount > $_folder->imap_totalcount) {
            
            // get next 50 messages from cache job lowest uid
            $messages = Felamimail_Controller_Message::getInstance()->search(new Felamimail_Model_MessageFilter(array(
                array('field' => 'folder_id',   'operator' => 'equals', 'value' => $_folder->getId()),
                array('field' => 'account_id',  'operator' => 'equals', 'value' => $_folder->account_id),
                array('field' => 'messageuid',  'operator' => 'less',   'value' => $_folder->cache_job_lowestuid),
            )), new Tinebase_Model_Pagination(array('start' => $start, 'limit' => $stepSize, 'sort' => 'messageuid')));
            
            Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Got ' . count($messages) 
                . ' message(s) from cache beginning with uid ' . $_folder->cache_job_lowestuid);
            
            // get messageuids
            if (count($messages) > 0) {
                $cacheUids = $messages->messageuid;
                
                // query mailserver
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Query uids from imap server.');
                $imapUids = $_imap->getUidbyUid($cacheUids);
                
                // delete uids not on mailserver from cache
                $toDeleteUids = array_diff($cacheUids, $imapUids);
                if (! empty($toDeleteUids)) {
                    $this->_backend->deleteByProperty($toDeleteUids, 'messageuid', 'in');
                }
                $numberDeleted = count($toDeleteUids);
                
                // update folder values
                $_folder->cache_job_actions_done += $numberDeleted;
                $_folder->cache_totalcount -= $numberDeleted;
                $_folder->cache_job_lowestuid = min($cacheUids);
                
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Deleted ' . $numberDeleted . ' messages from cache.');
            }
            
            // check if we are done
            if (count($messages) < $stepSize) {
                Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No more messages in cache with uid lower than ' . $_folder->cache_job_lowestuid);
                $_folder->cache_status = Felamimail_Model_Folder::CACHE_STATUS_COMPLETE;
                break;
            }
            
            // check time and increase start for pagination
            $timeLeft = ($_folder->cache_timestamp->compare(Zend_Date::now()->subSecond($_time)) == 1);
            $start += $stepSize;
        }
    }
    
    /**
     * convert date from sent/received
     *
     * @param string $_dateString
     * @param string $_format default: 'Thu, 21 Dec 2000 16:01:07 +0200' (Zend_Date::RFC_2822)
     * @return Zend_Date
     */
    protected function _convertDate($_dateString, $_format = Zend_Date::RFC_2822)
    {
        if ($_format == Zend_Date::RFC_2822) {

            // strip of timezone information for example: (CEST)
            $dateString = preg_replace('/( [+-]{1}\d{4}) \(.*\)$/', '${1}', $_dateString);
            
            // append dummy weekday if missing
            if(preg_match('/^(\d{1,2})\s(\w{3})\s(\d{4})\s(\d{2}):(\d{2}):{0,1}(\d{0,2})\s([+-]{1}\d{4})$/', $dateString)) {
                $dateString = 'xxx, ' . $dateString;
            }
            
            try {
                // Fri,  6 Mar 2009 20:00:36 +0100
                $date = new Zend_Date($dateString, Zend_Date::RFC_2822, 'en_US');
            } catch (Zend_Date_Exception $e) {
                // Fri,  6 Mar 2009 20:00:36 CET
                $date = new Zend_Date($dateString, Felamimail_Model_Message::DATE_FORMAT, 'en_US');
            }

        } else {
            
            $date = new Zend_Date($_dateString, $_format, 'en_US');
            
            if ($_format == Felamimail_Model_Message::DATE_FORMAT_RECEIVED) {
                
                if (preg_match('/ ([+-]{1})(\d{2})\d{2}$/', $_dateString, $matches)) {
                    // add / sub from zend date ?
                    if ($matches[1] == '+') {
                        $date->subHour($matches[2]);
                    } else {
                        $date->addHour($matches[2]);
                    }
                    
                    //Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . print_r($matches, true));
                }
            }
        }
        
        return $date;
    }

    /**
     * convert addresses into array with name/address
     *
     * @param string $_addresses
     * @return array
     */
    protected function _convertAddresses($_addresses)
    {
        $result = array();
        if (!empty($_addresses)) {
            $addresses = Felamimail_Message::parseAdresslist($_addresses);
            if (is_array($addresses)) {
                foreach($addresses as $address) {
                    $result[] = array('email' => $address['address'], 'name' => $address['name']);
                }
            }
        }
        return $result;
    }
}
