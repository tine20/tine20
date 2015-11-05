<?php
/**
 * Expresso Lite Accessible
 * Shows all folders for the user to choose one and move message to another folder.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Edgar de Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Mail;

use Accessible\Handler;
use ExpressoLite\Backend\TineSessionRepository;
use ExpressoLite\Backend\LiteRequestProcessor;

class OpenFolder extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $liteRequestProcessor = new LiteRequestProcessor();
        $response = $liteRequestProcessor->executeRequest('SearchFolders', (object) array(
            'recursive' => true
        ));

        $folders = $this->flatFolderTree($response, $params);
        TineSessionRepository::getTineSession()->setAttribute('folders', $folders);

        $this->showTemplate('OpenFolderTemplate', (object) array(
            'folders' => $folders,
            'folderName' => $params->folderName,
            'lnkRefreshFolder' => $this->makeUrl('Mail.Main', array(
                'folderName' => $params->folderName,
                'folderId' =>  $params->folderId,
                'page' => $params->page
            )),
            // Link to back to the message that will no longer be moved
            // only visible from the message view
            'lnkRefreshMessage' => $this->makeUrl('Mail.OpenMessage', array(
                'folderName' => $params->folderName,
                'folderId' => $params->folderId,
                'page' => $params->page,
                'messageId' => isset($params->messageIds) ? $params->messageIds : ''
            )),
            'isMsgBeingMoved' => isset($params->isMsgBeingMoved) ? true : false
        ));
    }

    /**
     * Flat folder tree
     *
     * @param array $arrFolders
     * @param string $parents
     * @return array of folder tree
     */
    private function flatFolderTree($arrFolders, $params, $parents = '')
    {
        $retFolders = array();
        $handler = isset($params->isMsgBeingMoved) ? 'Mail.MoveMessage' : 'Mail.Main';
        foreach ($arrFolders as $fol) {
            $writtenParents = ($parents === '') ? '' : $parents . ' / ';

            $retFolders[] = (object) array(
                'id' => $fol->id,
                'lnkOpenFolder' => $this->makeUrl($handler, array(
                    'folderId' => $fol->id,
                    'folderName' => $fol->localName,
                    'currentFolderName' => $params->folderName,
                    'currentFolderId' => $params->folderId,
                    // only necessary for moving messages to any folder
                    'messageIds' => isset($params->messageIds) ? $params->messageIds : ''
                )),
                'title' => 'Abrir pasta ' . $fol->localName .', contém ' . $fol->totalMails . ' emails' . ' e ' . $fol->unreadMails . ' emails não lido',
                'localName' => $writtenParents . $fol->localName,
                'globalName' => $fol->globalName,
                'totalMails' => $fol->totalMails,
                'unreadMail' => $fol->unreadMails
            );
            if (count($fol->subfolders) > 0) {
                $retFolders = array_merge($retFolders, $this->flatFolderTree($fol->subfolders, $params ,$writtenParents . $fol->localName));
            }
        }
        return $retFolders;
    }
}
