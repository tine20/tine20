<?php
/**
 * Expresso Lite
 * Handler for getMessage calls.
 * Originally avaible in Tine.class (prior to the backend refactoring).
 *
 * @package   ExpressoLite\Backend
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @author    Charles Wust <charles.wust@serpro.gov.br>
 * @copyright Copyright (c) 2014 Serpro (http://www.serpro.gov.br)
 */
namespace ExpressoLite\Backend\Request;

class GetMessage extends LiteRequest
{

    /**
     * @see ExpressoLite\Backend\Request\LiteRequest::execute
     */
    public function execute()
    {
        $response = $this->jsonRpc('Expressomail.getMessage', (object) array(
            'id' => $this->param('id')
        ));

        if (isset($response->result->headers->to) && $response->result->headers->to !== null) {
            $mailsTo = explode(',', $response->result->headers->to);
        } else {
            $mailsTo = array();
        }

        for ($i = 0, $countMailsTo = count($mailsTo); $i < $countMailsTo; ++ $i) {
            $mailsTo[$i] = htmlspecialchars(trim($mailsTo[$i]));
        }

        return (object) array(
            'id' => $response->result->id,
            'received' => $response->result->received,
            'subject' => $response->result->subject,
            'from_name' => $response->result->from_name,
            'from_email' => $response->result->from_email,
            'to' => $response->result->to,
            'cc' => $response->result->cc,
            'bcc' => $response->result->bcc,
            'body' => $this->breakQuotedMessage($response->result->body),
            'importance' => $response->result->importance,
            'has_attachment' => $response->result->has_attachment,
            'attachments' => $response->result->attachments,
            'folder_id' => $response->result->folder_id
        );
    }

    /**
     * Returns an array of regular expressions that identify quoted messages.
     *
     */
    private function getQuotedMessagePatterns() {
        return array(
                '/--+\s?((Mensagem encaminhada)|(Mensagem original)|(Original message)|(Originalnachricht))\s?--+/',
                '/(<((div)|(font))(.{1,50})>)?Em \d+(\/|\.|-)\d+(\/|\.|-)\d+,? (a|à)(s|\(s\)) \d+:\d+( horas)?, (.{1,256})escreveu:/',
                '/(<((div)|(font))(.{1,50})>)?Em \d+(\/|\.|-)\d+(\/|\.|-)\d+ \d+:\d+(:\d+)?, (.{1,256})escreveu:/',
                '/(<((div)|(font))(.{1,50})>)?Em \d+ de (.{1,9}) de \d+ \d+:\d+(:\d+)?, (.{1,256})escreveu:/',
                '/((On)|(Am)) \d{1,2}(\/|\.|-)\d{1,2}(\/|\.|-)\d{4} \d\d:\d\d(:\d\d)?, (.{1,256})((wrote)|(schrieb)):?/'
        );
    }

    /**
     * Separates the last message content from previously quoted messages
     *
     * @param The complete, unseparated message content
     *
     * @return object An object with the current message content (->message) and
     *                quoted messages (->quoted).
     */
    private function breakQuotedMessage($message)
    {
        $idx = array();
        foreach ($this->getQuotedMessagePatterns() as $pattern) {
            if (preg_match($pattern, $message, $matches, PREG_OFFSET_CAPTURE))
                $idx[] = $matches[0][1]; // append index of 1st occurrence of regex match
        }
        if (!empty($idx)) {
            $idx = min($idx); // isolate index of earliest occurrence
            $topMsg = $this->fixInlineAttachmentImages($this->trimLinebreaks(substr($message, 0, $idx)));
            $quotedMsg = $this->fixInlineAttachmentImages($this->trimLinebreaks(substr($message, $idx)));
            return (object) array(
                'message' => $topMsg,
                'quoted' => ($quotedMsg != '') ? $this->fixBrokenDiv($quotedMsg) : null
            );
        } else {
            return (object) array(
                'message' => $this->fixInlineAttachmentImages($this->trimLinebreaks($message)),
                'quoted' => null
            ); // no quotes found
        }
    }

    /**
     * Replaces inline attachment links in a message body so they may work within Lite.
     *
     * @param string $text the original message body from Tine
     *
     * @return the message body with replaced links
     */
    private function fixInlineAttachmentImages($text)
    {
        return preg_replace(
            '/src="index\.php\?method=Expressomail\.downloadAttachment/',
            'src="' . $this->param('ajaxUrl') . '?r=downloadAttachment&fileName=inlineAttachment',
            $text);
    }

    /**
     * Replaces inline attachment links in a message body so they may work within Lite.
     *
     * @param string $text the original message body from Tine
     *
     * @return the message body with replaced links
     */
    private function trimLinebreaks($text)
    {
        $text = trim($text);
        if ($text != '') {
            $text = preg_replace('/^(<(BR|br)\s?\/?>)+/', '', $text); // ltrim
            $text = preg_replace('/(<(BR|br)\s?\/?>)+$/', '', $text); // rtrim
        }
        return $text;
    }

    /**
     * If a </div> is found before a <div>, this would break Lite screen layout exhibition.
     * In these cases, these function removes the first </div> in order to
     * avoid this.
     *
     * @param string $text the message body
     *
     * @return the message body without a broken div if that was the case
     */
    private function fixBrokenDiv($text)
    {
        $firstClose = stripos($text, '</div>'); // an enclosing div before an opening one
        $firstOpen = stripos($text, '<div');

        if (($firstClose !== false && $firstOpen !== false && $firstClose < $firstOpen) ||
            ($firstClose !== false && $firstOpen === false)) {
            return preg_replace('/<\/div>/i', '', $text, 1);
        } else {
            return $text;
        }
    }
}
