<?php
/**
 * Expresso Lite Accessible
 * Manipulates data for the application main screen, loading some
 * especific email folder information and headlines. Also provides
 * access to others functionalities as write a new email and do
 * system logoff.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\LiteRequestProcessor;
use ExpressoLite\Backend\TineSessionRepository;
use Accessible\Core\DateUtils;
use Accessible\Mail\ProcessMessageAction;

class Main extends Handler
{
    /**
     * @var REQUEST_LIMIT Max number of requested items.
     */
    const REQUEST_LIMIT = 50;

    /**
     * @var TRASH_FOLDER Global name of trash folder.
     */
    const TRASH_FOLDER = 'INBOX/Trash';

    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $folderId = isset($params->folderId) ? $params->folderId : null;
        $curFolder = $this->getFolder($folderId);

        if (!isset($params->page)) { // one-based index of current email pagination
            $params->page = 1; // if no page is specified, start listing from page 1
        }
        $headlines = $this->retrieveHeadlines($curFolder, $params->page);
        $start = (($params->page - 1) * self::REQUEST_LIMIT) + 1 ;

        $noEmail = (count($headlines) === 0);

        $limit = $start + count($headlines) - 1;
        $this->showTemplate('MainTemplate', (object) array(
            'noEmail'   => $noEmail,
            'curFolder' => $curFolder,
            'headlines' => $headlines,
            'page' => $params->page,
            'start' => $start,
            'limit' => $limit,
            'requestLimit' => self::REQUEST_LIMIT,
            'lnkRefreshFolder' => $this->makeUrl('Mail.Main', array(
                'folderId' => $curFolder->id,
                'page' => $params->page
            )),
            'lnkChangeFolder' => $this->makeUrl('Mail.OpenFolder', array(
                'folderId' => $curFolder->id,
                'folderName' => $curFolder->localName,
                'page' => $params->page
            )),
            'lnkComposeMessage' => $this->makeUrl('Mail.ComposeMessage', array(
                'folderId' => $curFolder->id,
                'folderName' => $curFolder->localName,
                'page' => $params->page
            )),
            'lnkLogoff' => $this->makeUrl('Login.Logoff'),
            'lnkPrevPage' => $this->makeUrl('Mail.Main', array(
                'folderId' => $curFolder->id,
                'page' => $params->page - 1
            )),
            'lnkNextPage' => $this->makeUrl('Mail.Main', array(
                'folderId' => $curFolder->id,
                'page' => $params->page + 1
            )),
            'action_mark_unread' => ProcessMessageAction::ACTION_MARK_UNREAD,
            'lnkConfirmMessageAction' => $this->makeUrl('Mail.ConfirmMessageAction', array(
                'folderId' => $curFolder->id,
                'folderName' => $curFolder->localName,
                'page' => $params->page
            )),
            'lnkCalendar' => $this->makeUrl('Calendar.Main'),
            'action_delete' => ProcessMessageAction::ACTION_DELETE,
            'lnkEmptyTrash' => $this->makeUrl('Mail.EmptyTrashConfirm', array(
                'actionProcess' => ProcessMessageAction::ACTION_EMPTY_TRASH,
                'folderId' => $curFolder->id,
                'folderName' => $curFolder->localName
            )),
            'isTrashCurrentFolder' => $this->isTrashCurrentFolder($curFolder)
        ));
    }

    /**
     * Get folder from Folder ID
     *
     * @param int $folderId
     * @return array of current folder
     */
    private function getFolder($folderId)
    {
        $folders = TineSessionRepository::getTineSession()->getAttribute('folders');
        if ($folderId === null || $folders === null) {
            $liteRequestProcessor = new LiteRequestProcessor();
            $response = $liteRequestProcessor->executeRequest('SearchFolders', (object) array());
            $curFolder = (object) array(
                'id' => $response[0]->id,
                'localName' => $response[0]->localName,
                'globalName' => $response[0]->globalName,
                'totalMails' => $response[0]->totalMails,
                'unreadMails' => $response[0]->unreadMails
            );

            if (!isset($folders)) {
                $folders[] = (object) array(
                    'id' => $curFolder->id,
                    'title' => 'Esta pasta contém ' . $curFolder->totalMails . ' emails' . ' e ' . $curFolder->unreadMails . ' emails não lido',
                    'localName' => $curFolder->localName,
                    'globalName' => $curFolder->globalName,
                    'totalMails' => $curFolder->totalMails,
                    'unreadMails' => $curFolder->unreadMails
                );
                TineSessionRepository::getTineSession()->setAttribute('folders', $folders);
            }
        } else {
            $liteRequestProcessor = new LiteRequestProcessor();
            $response = $liteRequestProcessor->executeRequest('UpdateMessageCache', (object) array(
                'folderId' => $folderId
            ));

            foreach ($folders as $tmp) {
                if ($tmp->id === $folderId) {
                    $curFolder = (object) array(
                        'id' => $folderId,
                        'localName' => $tmp->localName,
                        'globalName' => $tmp->globalName,
                        'totalMails' => $response->totalMails,
                        'unreadMails' => $response->unreadMails
                    );
                    break;
                }
            }
        }
        return $curFolder;
    }

    /**
     * Returns an array containing headlines information and links for actions
     *
     * @param string $curFolder
     * @param int $curPage The current page of headlines pagination
     * @return array of headlines
     */
    private function retrieveHeadlines($curFolder, $curPage)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $headlines = $liteRequestProcessor->executeRequest('SearchHeadlines', (object) array(
            'folderIds' => $curFolder->id,
            'start' => ($curPage - 1) * self::REQUEST_LIMIT,
            'limit' => self::REQUEST_LIMIT
        ));

        foreach ($headlines as &$headl) {
            $headl->received = DateUtils::getFormattedDate($headl->received);
            $headl->subject = trim($headl->subject);
            if ($headl->subject === '') {
                $headl->subject = '(sem assunto)';
            }

            $headl->lnkOpen = $this->makeUrl('Mail.OpenMessage', array(
                'messageId' => $headl->id,
                'folderId' => $curFolder->id,
                'folderName' => $curFolder->localName,
                'page' => $curPage
            ));
        }
        return $headlines;
    }

    /**
     * Verify if the current email folder is the trash.
     *
     * @param stdClass $currFolder The current folder object
     * @return boolean True if the current folder is the trash folder,
     *                 false otherwise
     */
    private function isTrashCurrentFolder($currFolder)
    {
        return $currFolder->globalName === DeleteMessage::TRASH_FOLDER;
    }
}
