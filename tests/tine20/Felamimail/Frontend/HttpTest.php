<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2020 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test class for Felamimail_Frontend_Http
 * 
 * @package     Felamimail
 */
class Felamimail_Frontend_HttpTest extends Felamimail_TestCase
{
    public function testDownloadSingleAttachment()
    {
        $message = $this->_sendMessageWithAttachments(1);
        self::assertTrue(isset($message['attachments'][0]['partId']), 'no attachments found: '
            . print_r($message, true));
        $partIds = $message['attachments'][0]['partId'];
        $out = $this->_fetchDownload($message['id'], $partIds);
        self::assertEquals('some content', $out);
    }

    protected function _sendMessageWithAttachments($numberOfAttachments)
    {
        $message = $this->_sendMessage(
            'INBOX',
            [],
            '',
            'test download attachment',
            null,
            $numberOfAttachments
        );
        // fetch message again to get attachments
        $json = new Felamimail_Frontend_Json();
        return $json->getMessage($message['id']);
    }

    protected function _fetchDownload($id, $partIds = [], $model = 'Felamimail_Model_Message')
    {
        /* @var $uit Felamimail_Frontend_Http */
        $uit = $this->_getUit();
        ob_start();
        $partIds = is_array($partIds) ? implode(',', $partIds) : $partIds;
        $uit->downloadAttachments($id, $partIds, $model);
        return ob_get_clean();
    }

    public function testDownloadMultipleAttachments()
    {
        $message = $this->_sendMessageWithAttachments(2);
        $partIds = [];
        foreach ($message['attachments'] as $attachment) {
            $partIds[] = $attachment['partId'];
        }
        $out = $this->_fetchDownload($message['id'], $partIds);

        $content = $this->_unzipContent($out);
        self::assertEquals('some content', $content);
    }

    public function testDownloadNodeAttachment()
    {
        $message = $this->_sendMessageWithAttachments(1);

        $personalFilemanagerContainer = $this->_getPersonalContainerNode();
        $filter = array(array(
            'field' => 'id', 'operator' => 'in', 'value' => array($message['id'])
        ));
        $path = $this->_getPersonalFilemanagerPath($personalFilemanagerContainer);
        $location = $this->_getTestLocation('path', $personalFilemanagerContainer, $path);
        $json = new Felamimail_Frontend_Json();
        $json->fileMessages($filter, [$location]);
        $nodes = $this->_getTestNodes($path);
        $emlNode = $nodes->getFirstRecord();
        $message = $json->getMessageFromNode($emlNode['id']);
        $partIds = (string) $message['attachments'][0]['partId'];
        $out = $this->_fetchDownload($emlNode['id'], $partIds, 'Filemanager_Model_Node');
        self::assertEquals('some content', $out);
    }
}
