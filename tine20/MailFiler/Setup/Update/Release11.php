<?php
/**
 * Tine 2.0
 *
 * @package     MailFiler
 * @subpackage  Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL3
 * @copyright   Copyright (c) 2017 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Paul Mehrer <p.mehrer@metaways.de>
 */
class MailFiler_Setup_Update_Release11 extends Setup_Update_Abstract
{
    /**
     * update to 11.1
     *
     * @return void
     */
    public function update_0()
    {
        if (!$this->_backend->columnExists('to_flat', 'mailfiler_message')) {
            $this->_backend->addCol('mailfiler_message',
                new Setup_Backend_Schema_Field_Xml('<field>
                    <name>to_flat</name>
                    <type>text</type>
                </field>')
            );
            $this->setTableVersion('mailfiler_message', 2);
        }

        $mailFilerMessageController = MailFiler_Controller_Message::getInstance();
        $pagination = new Tinebase_Model_Pagination(array('start' => 0, 'limit' => 1000, 'sort' => 'id'));
        do {
            $result = $mailFilerMessageController->search(null, $pagination);
            $pagination->start += 1000;

            /** @var MailFiler_Model_Message $message */
            foreach ($result as $message) {
                $to = $message->to;
                sort($to);
                $message->to_flat = join(', ', $to);
                $mailFilerMessageController->update($message);
            }
        } while ($result->count() > 0);

        $this->setApplicationVersion('MailFiler', '11.1');
    }
}
