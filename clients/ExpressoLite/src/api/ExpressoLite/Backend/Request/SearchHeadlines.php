<?php
/**
 * Expresso Lite
 * Handler for searchHeadlines calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014-2015 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class SearchHeadlines extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $folderIds = explode(',', $this->param('folderIds'));
        $start = (int) $this->param('start');
        $limit = (int) $this->param('limit');
        $searchTerm = $this->isParamSet('what') ? $this->param('what') : '';

        $accountId = $this->getSessionAttribute('Expressomail.accountId');
        foreach ($folderIds as &$fid) {
            $fid = "/$accountId/$fid";
        }

        $tineMessages = $this->getTineMessages($searchTerm, $folderIds, $start, $limit);
        $liteHeadlines = $this->convertTineMessagesToLiteHeadlines($tineMessages);
        return ($searchTerm === '') ?
            $liteHeadlines->headlines : $this->createVirtualSearchFolder($searchTerm, $liteHeadlines);
    }

    /**
     * Connects to tine to return an array of messages that match a specified criteria.
     *
     * @param string $what      A text that is part of the message
     * @param array  $folderIds The ids of the folders in which the message should be searched
     * @param int    $start     The index of the first result to be returned (used for paging purposes)
     * @param int    $limit     The max number of results to be returned (used for paging purposes)
     *
     * @return Array of messages as returned by Tine
     */
    private function getTineMessages($what, array $folderIds, $start, $limit)
    {
        $response = $this->jsonRpc('Expressomail.searchMessages', (object) array(
            'filter' => array(
                (object) array(
                    'condition' => 'AND',
                    'filters' => array(
                        (object) array(
                            'field'    => 'query',
                            'operator' => 'contains',
                            'value'    => $what
                        ),
                        (object) array(
                            'field'    => 'path',
                            'operator' => 'in',
                            'value'    => $folderIds
                        )
                    )
                )
            ),
            'paging' => (object) array(
                'sort'  => 'received',
                'dir'   => 'DESC',
                'start' => $start,
                'limit' => $limit
            )
        ));
        return (object) array(
            'headlines'  => $response->result->results,
            'totalCount' => $response->result->totalcount // for paging, if needed
        );
    }

    /**
     * Search results are returned within a virtual folder, this method creates this folder.
     *
     * @param string   $searchTerm   Text being searched
     * @param stdClass $searchResult Result of the search, with headlines array
     *
     * @return Virtual folder object
     */
    private function createVirtualSearchFolder($searchTerm, $searchResult)
    {
        return (object)array( // return an artificial folder
            'id'            => null, // ID and globalName are set to null
            'globalName'    => null,
            'localName'     => $what,
            'hasSubfolders' => false,
            'subfolders'    => array(),
            'totalMails'    => count($searchResult->headlines) ? $searchResult->totalCount : 0,
            'unreadMails'   => 0,
            'recentMails'   => 0,
            'quotaLimit'    => 0,
            'quotaUsage'    => 0,
            'systemFolder'  => false,
            'messages'      => $searchResult->headlines,
            'threads'       => array()  // not populated here
        );
    }

    /**
     * Converts an array of messages (as returned by Tine) to the format expected by Lite.
     *
     * @param array $tineMessages Then array of messages as returned by Tine
     *
     * @return Object with array of messages as expected by Lite
     */
    private function convertTineMessagesToLiteHeadlines($tineMessages)
    {
        $headlines = array();
        foreach ($tineMessages->headlines as $mail) {
            $headlines[] = (object) array(
                'id'      => $mail->id,
                'subject' => ($mail->subject !== null) ? $mail->subject : '',
                'to'      => ($mail->to !== null) ? $mail->to : array(),
                'cc'      => ($mail->cc !== null) ? $mail->cc : array(),
                'bcc'     => ($mail->bcc !== null) ? $mail->bcc : array(), // brings only 1 email
                'from'    => (object) array(
                    'name'  => $mail->from_name,
                    'email' => $mail->from_email
                ),
                'unread'        => ! in_array("\\Seen", $mail->flags),
                'draft'         => in_array("\\Draft", $mail->flags),
                'flagged'       => in_array("\\Flagged", $mail->flags),
                'replied'       => in_array("\\Answered", $mail->flags),
                'forwarded'     => in_array("Passed", $mail->flags),
                'important'     => $mail->importance,
                'signed'        => $mail->structure->contentType === 'multipart/signed',
                'wantConfirm'   => $mail->reading_conf,
                'received'      => strtotime($mail->received), // timestamp
                'size'          => (int) $mail->size, // bytes
                'hasAttachment' => $mail->has_attachment,
                'attachments'   => null, // not populated here
                'body'          => null
            );
        }
        return (object) array(
            'headlines'  => $headlines,
            'totalCount' => $tineMessages->totalCount // for paging, if needed
        );
    }
}
