<?php
/**
 * Expresso Lite
 * Handler for searchFolders calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class SearchFolders extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $recursive = $this->isParamSet('recursive') ? $this->param('recursive') == 1 : false;
        $parentFolder = $this->isParamSet('parentFolder') ? $this->param('parentFolder') : '';
        $accountId = $this->getSessionAttribute('Expressomail.accountId');

        return $this->searchFolders($parentFolder, $recursive, $accountId);
    }

    /**
     * Searches all folders belonging to a parent folder
     *
     * @param $parentFolder The parent folder id
     * @param $recursive Indicates if subfolders should be searched recursively and returned as well
     * @param $accountId The account id of the folder's owner
     *
     * @return An array containing the folders.
     */
    public function searchFolders($parentFolder, $recursive, $accountId)
    {
        $response = $this->jsonRpc('Expressomail.searchFolders', (object) array(
            'filter' => array(
                (object) array(
                    'field' => 'account_id',
                    'operator' => 'equals',
                    'value' => $accountId
                ),
                (object) array(
                    'field' => 'globalname',
                    'operator' => 'equals',
                    'value' => $parentFolder
                )
            )
        ));

        $enToPtTrans = array(
            'INBOX' => 'Caixa de entrada',
            'Drafts' => 'Rascunhos',
            'Sent' => 'Enviados',
            'Templates' => 'Modelos',
            'Trash' => 'Lixeira'
        );

        $fldrs = array();

        foreach ($response->result->results as $result) {
            if (array_key_exists($result->localname, $enToPtTrans)) {
                $result->localname = $enToPtTrans[$result->localname];
            }

            $fldrs[] = (object) array(
                'id' => $result->id,
                'globalName' => $result->globalname,
                'localName' => $result->localname,
                'hasSubfolders' => $result->has_children,
                'subfolders' => $recursive && $result->has_children ? $this->searchFolders($result->globalname, true, $accountId) : array(),
                'totalMails' => (int) $this->coalesce($result, 'cache_totalcount', 0),
                'unreadMails' => (int) $this->coalesce($result, 'cache_unreadcount', 0),
                'recentMails' => (int) $this->coalesce($result, 'cache_recentcount', 0),
                'quotaLimit' => (int) $this->coalesce($result, 'quota_limit', 0),
                'quotaUsage' => (int) $this->coalesce($result, 'quota_usage', 0),
                'systemFolder' => $result->system_folder,
                'messages' => array(),
                'threads' => array()
            );
        }
        return $fldrs;
    }
}
