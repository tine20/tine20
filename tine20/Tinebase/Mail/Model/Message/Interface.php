<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Flávio Gomes da Silva Lisboa <flavio.lisboa@serpro.gov.br>
 * @copyright   Copyright (c) 2009-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */


/**
 * @package     Tinebase
 * @subpackage  Mail
 */
interface Tinebase_Mail_Model_Message_Interface
{
    //function fixToListModel();
    function hasSeenFlag();
    function hasReadFlag();
    function sendReadingConfirmation();
    //function parseSmime(array $_structure);
    function parseHeaders(array $_headers);
    function parseStructure($_structure = NULL);
    function getPartStructure($_partId, $_useMessageStructure = TRUE);
    function getBodyParts($_structure = NULL, $_preferedMimeType = Zend_Mime::TYPE_HTML);
    /**
     * @deprecated should be replaced by getBodyParts
     * @see 0007742: refactoring: replace parseBodyParts with getBodyParts
     */
    function parseBodyParts();
    function getPlainTextBody();
    static function convertHTMLToPlainTextWithQuotes($_html, $_eol = "\n");
    static function addQuotesAndStripTags($_node, $_quoteIndent = 0, $_eol = "\n");
}
