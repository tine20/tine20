<?php
/**
 * class to hold Message Disposition Notification (MDN) part
 *
 * @package     Expressomail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Fernando Alberto Reuter Wendt <fernando-alberto.wendt@serpro.gov.br>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2016 Serviço Fedral de Processamento de Dados - SERPRO
 */

class Expressomail_Model_MessageDispositionNotificationPart
{

    /***** STATIC ATTRIBUTES : RFC CONFORMANCE *****/

    /**
     * Disposition Mode Automatic
     */
    const DISPOSITION_MODE_AUTOMATIC = 'automatic-action';

    /**
     * Disposition Mode Manual
     */
    const DISPOSITION_MODE_MANUAL = 'manual-action';

    /**
     * Disposition Mode Sent Automatic
     */
    const DISPOSITION_MODE_MDN_AUTOMATIC = 'MDN-sent-automatically';

    /**
     * Disposition Mode Sent Manually
     */
    const DISPOSITION_MODE_MDN_MANUAL = 'MDN-sent-manually';

    /**
     * Disposition Type Displayed
     */
    const DISPOSITION_TYPE_DISPLAYED = 'displayed';

    /**
     * Disposition Type Dispached
     */
    const DISPOSITION_TYPE_DISPACHED = 'dispatched';

    /**
     * Disposition Type Processed
     */
    const DISPOSITION_TYPE_PROCESSED = 'processed';

    /**
     * Disposition Type Deleted
     */
    const DISPOSITION_TYPE_DELETED = 'deleted';

    /**
     * Disposition Type Denied
     */
    const DISPOSITION_TYPE_DENIED = 'denied';

    /**
     * Disposition Type Failed
     */
    const DISPOSITION_TYPE_FAILED = 'failed';

    /**
     * Disposition Modifier Error
     */
    const DISPOSITION_MODIFIER_ERROR = 'error';

    /**
     * Disposition Modifier Warning
     */
    const DISPOSITION_MODIFIER_WARNING = 'warning';

    /**
     * Disposition Modifier Superseded
     */
    const DISPOSITION_MODIFIER_SUPERSEDED = 'superseded';

    /**
     * Disposition Modifier Expired
     */
    const DISPOSITION_MODIFIER_EXPIRED = 'expired';

    /**
     * Disposition Modifer Mailbox Terminated
     */
    const DISPOSITION_MODIFIER_MAILBOX_TERMINATED = 'mailbox-terminated';

    /***** Properties *****/
    protected $_parsed = FALSE;
    protected $_mdnBody;
    protected $_recipient;
    protected $_dispositionMode;
    protected $_dispositionType;
    protected $_customMessage;

    /**
     * Object class constructor
     *
     * @param type $_mdnBody
     */
    public function __construct($_mdnBody)
    {
        $this->_mdnBody = $_mdnBody;
    }

    /**
     * Set the type of disposition
     *
     * @param string $_foundType
     */
    protected function _setDispositionType($_foundType)
    {
        preg_match('/[[:alnum:]-_]+$/', $_foundType, $type);

        if(empty($type) || count($type) != 1){
            $return = 'Unknown Action';
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' MDN action unhandled: ' . print_r($type,true));
        }
        else{
            $return = '';
            switch ($type[0]) {
                case self::DISPOSITION_TYPE_DELETED :
                    $return = 'Message was deleted';
                    break;
                default:
                    $return = 'Unknown Type';
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' MDN type not mapped error: ' . $type[0]);
                    break;
            }
        }

        $this->_dispositionType = $return;
    }

    /**
     * Set the mode of the disposition
     *
     * @param string $_foundDisposition
     */
    protected function _setDispositionMode($_foundDisposition)
    {
        preg_match('/[[:alnum:]-_]+\/[[:alnum:]-_]+;/', $_foundDisposition, $mode);

        $return = '';

        if(empty($mode) || count($mode) != 1){
            $return = 'Unknown Mode';
            Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . ' MDN mode unhandled: ' . print_r($mode,true));
        }
        else{
            switch ($mode[0]) {
                case self::DISPOSITION_MODE_AUTOMATIC.'/'.self::DISPOSITION_MODE_MDN_AUTOMATIC.';' :
                    $return = 'Automatic Action Performed';
                    break;
                case self::DISPOSITION_MODE_MANUAL :
                    $return = 'Manual Action Performed';
                    break;
                default:
                    $return = 'Unknown Mode';
                    Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                        . ' MDN Mode value mapping error: ' . $mode[0]);
                    break;
            }
        }
        $this->_dispositionMode = $return;
    }

    /**
     * Parse MDN Message
     */
    public function parse()
    {
        // Break per message and per recipient fields
        $out = preg_split('/(?<=\x0A)\x0D\x0A/', $this->_mdnBody);

        $errors = array();

        $this->_recipients = array();
        foreach ($out as $perRecipients) {
            $recipient = array();

            //Final-Recipient (Required)
            preg_match('/Final-Recipient:\s*[[:alnum:]-_]+;\s*(.+?)\x0D\x0A/', $perRecipients, $matches);

            if (empty($matches) || count($matches) != 2){
                $errors[] = 'Error Parsing per Message Block: Final-Recipient Field';
            }

            //Switch Sieve '^' identifyer delimiter for '.' email address notation
            $matches = str_replace('^', '.', $matches);

            $finalRecipient = $matches[1];

            $recipient['finalRecipient'] = $finalRecipient;
            unset($matches);

            $this->_recipients[] = $recipient;

            //Disposition action type mapping
            preg_match('/Disposition:\s[[:alnum:]-_]+\/[[:alnum:]-_]+;\s[[:alnum:]-_]+/', $perRecipients, $foundDisposition);

            if (empty($foundDisposition) || count($foundDisposition != 1)){
                $errors[] = 'Error Parsing per Message Block: Disposition Field';
            }

            $this->_setDispositionMode($foundDisposition[0]);
            $this->_setDispositionType($foundDisposition[0]);
        }

        $this->_parsed = TRUE;
    }

    /*
     * Defaults body message content to be displayed for user
     *
     * @param Tinebase_Translation $_translateIt
     * @return string $htmlOut
     */
    protected function _MessageBodyTemplate($_translateIt){
        $htmlOut = "";

        // Final message recipient: most will have just one
        $htmlOut .= '<b>' . $_translateIt->_('Recipient') . ':</b> ';
        $htmlOut .= $this->_recipients[0]['finalRecipient'];
        $htmlOut .= '<br />';

        // How it happend: most will be SIEVE automatic action
        $htmlOut .= '<b>' . $_translateIt->_('Action Type') . ':</b> ';
        $htmlOut .= $_translateIt->_($this->_dispositionMode);
        $htmlOut .= '<br />';

        // What happend: by now, most will be deleted action
        $htmlOut .= '<b>' . $_translateIt->_('Status') . ':</b> ';
        $htmlOut .= $_translateIt->_($this->_dispositionType);
        $htmlOut .= '<br /><br />';

        // Has a custom message to show? Display user creation SIEVE filter data here
        if (strlen(trim($this->_customMessage)) > 0){
            $htmlOut .= '<b>' . $_translateIt->_('Custom User Message') . ':</b> ';
            $htmlOut .= '<i>&quot;' . trim($this->_customMessage) . '&quot;</i>';
            $htmlOut .= '<br />';
        }
        return $htmlOut;
    }

    /**
     * Get the user friendly HTML content message
     *
     * @param string $userMessage
     * @return string $html
     */
    public function getHTMLFormatted($userMessage)
    {
        $this->_customMessage = $userMessage;

        if (!$this->_parsed) {
            $this->parse();
        }

        $translate = Tinebase_Translation::getTranslation('Expressomail');
        $html = '<html>'
                    . '<head>'
                        . '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'
                        . '<style type="text/css">'
                            . '.expressomail-body-blockquote {'
                                    . 'margin: 5px 10px 0 3px;'
                                    . 'padding-left: 10px;'
                                    . 'border-left: 2px solid #000088;'
                            . '}'
                        . '</style>'
                    . '</head>'
                    . '<body>';

        $html .= '<h3>' . $translate->_('Message Disposition Notification') . '</h3>';
        $html .= '<br /><br />';
        $html .= $this->_MessageBodyTemplate($translate);
        $html .= '</body></html>';
        return $html;
    }

    /**
     * Get original SIEVE filter message created
     *
     * @return string $findMessage
     */
    public function getCustomMessage(){
        preg_match('/The following reason was given:\x0D\x0A\w.{0,255}/', $this->_mdnBody, $matches);

        //If there is no custom message, returns nothing
        if (empty($matches)) {
            return '';
        }

        $findMessage = explode(':',$matches[0]);
        return $findMessage[1];
    }
}