<?php
/**
 * class to hold prepared message part data
 *
 * @package     Expressomail
 * @subpackage  Model
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Mário César Kolling <mario.kolling@serpro.gov.br>
 * @copyright   Copyright (c) 2011 Metaways Infosystems GmbH (http://www.metaways.de)
 * @copyright   Copyright (c) 2014 Serviço Fedral de Processamento de Dados - SERPRO
 */

class Expressomail_Model_DeliveryStatusNotificationMessagePart
{

    /***** CONSTANTS *****/
    /**
     * Status Class Success
     */
    const CLASS_SUCCESS_STATUS = '2';

    /**
     * Status Class Persistent Transient Failure
     */
    const CLASS_TRANSIENT_STATUS = '4';

    /**
     * Status Class Permanent Failure
     */
    const CLASS_PERMANENT_STATUS = '5';

    /**
     * X.0.0 Other or Undefined Status
     */
    const OTHER_STATUS = '0.0';

    /**
     * Other address status
     */
    const OTHER_ADDRESS_STATUS = '1.0';

    /**
     * X.1.1   Bad destination mailbox address status
     */
    const BAD_DESTINATION_MAILBOX_ADDRESS_STATUS = '1.1';

    /**
     * X.1.2   Bad destination system address
     */
    const BAD_DESTINATION_SYSTEM_ADDRESS_STATUS = '1.2';

    /**
     * X.1.3   Bad destination mailbox address syntax
     */
    const BAD_DESTINATION_MAILBOX_ADDRESS_SYNTAX_STATUS = '1.3';

    /**
     * X.1.4   Destination mailbox address ambiguous
     */
    const BAD_DESTINATION_MAILBOX_ADDRESS_AMBIGUOUS_STATUS = '1.4';

    /**
     * X.1.5   Destination address valid
     */
    const DESTINATION_ADDRESS_VALID_STATUS = '1.5';

    /**
     * X.1.6   Destination mailbox has moved, No forwarding address
     */
    const DESTINATION_MAILBOX_HAS_MOVED_STATUS = '1.6';

    /**
     * X.1.7   Bad sender's mailbox address syntax
     */
    const BAD_SENDER_MAILBOX_ADDRESS_SYNTAX_STATUS = '1.7';

    /**
     * X.1.8   Bad sender's system address
     */
    const BAD_SENDER_SYSTEM_ADDRESS_STATUS = '1.8';

    /**
     * X.2.0   Other or undefined mailbox status
     */
    const OTHER_MAILBOX_STATUS = '2.0';

    /**
     * X.2.1   Mailbox disabled, not accepting messages
     */
    const MAILBOX_DISABLED_STATUS = '2.1';

    /**
     * X.2.2   Mailbox full
     */
    const MAILBOX_FULL_STATUS = '2.2';

    /**
     * X.2.3   Message length exceeds administrative limit
     */
    const MESSAGE_LENGTH_EXCEEDED_STATUS = '2.3';

    /**
     * X.2.4   Mailing list expansion problem
     */
    const MAILING_LIST_EXPANSION_PROBLEM_STATUS = '2.4';

    /**
     * X.3.0   Other or undefined mail system status
     */
    const OTHER_MAIL_SYSTEM_STATUS = '3.0';

    /**
     * X.3.1   Mail system full
     */
    const MAIL_SYSTEM_FULL_STATUS = '3.1';

    /**
     * X.3.2   System not accepting network messages
     */
    const SYSTEM_NOT_ACCEPTING_MESSAGES_STATUS = '3.2';

    /**
     * X.3.3   System not capable of selected features
     */
    const SYSTEM_NOT_CAPABLE_STATUS = '3.3';

    /**
     * X.3.4   Message too big for system
     */
    const MESSAGE_TOO_BIG_STATUS = '3.4';

    /**
     * X.3.5 System incorrectly configured
     */
    const SYSTEM_CONFIGURATION_INCORRECT_STATUS = '3.5';

    /**
     * X.4.0   Other or undefined network or routing status
     */
    const OTHER_NETWORK_STATUS = '4.0';

    /**
     * X.4.1   No answer from host
     */
    const NO_ANSWER_FROM_HOST_STATUS = '4.1';

    /**
     * X.4.2   Bad connection
     */
    const BAD_CONNECTION_STATUS = '4.2';

    /**
     * X.4.3   Directory server failure
     */
    const DIRECTORY_SERVER_FAILURE_STATUS = '4.3';

    /**
     * X.4.4   Unable to route
     */
    const UNABLE_TO_ROUTE_STATUS = '4.4';

    /**
     * X.4.5   Mail system congestion
     */
    const MAIL_SYSTEM_CONGESTION_STATUS = '4.5';

    /**
     * X.4.6   Routing loop detected
     */
    const ROUTING_LOOP_DETECTED_STATUS = '4.6';

    /**
     * X.4.7   Delivery time expired
     */
    const DELIVERY_TIME_EXPIRED_STATUS = '4.7';

    /**
     * X.5.0   Other or undefined protocol status
     */
    const OTHER_PROTOCOL_STATUS = '5.0';

    /**
     * X.5.1   Invalid command
     */
    const INVALID_COMMAND_STATUS = '5.1';

    /**
     * X.5.2   Syntax error
     */
    const SYNTAX_ERROR_STATUS = '5.2';

    /**
     * X.5.3   Too many recipients
     */
    const TOO_MANY_RECIPIENTS_STATUS = '5.3';

    /**
     * X.5.4   Invalid command arguments
     */
    const INVALID_COMMAND_ARGUMENTS_STATUS = '5.4';

    /**
     * X.5.5   Wrong protocol version
     */
    const WRONG_PROTOCOL_VERSION_STATUS = '5.5';

    /**
     * X.6.0   Other or undefined media error
     */
    const OTHER_MEDIA_ERROR_STATUS = '6.0';

    /**
     * X.6.1   Media not supported
     */
    const MEDIA_NOT_SUPPORTED_STATUS = '6.1';

    /**
     * X.6.2   Conversion required and prohibited
     */
    const CONVERSION_PROHIBITED_STATUS = '6.2';

    /**
     * X.6.3   Conversion required but not supported
     */
    const CONVERSION_NOT_SUPPORTED_STATUS = '6.3';

    /**
     * X.6.4   Conversion with loss performed
     */
    const CONVERSION_WITH_LOSS_PERFORMED_STATUS = '6.4';

    /**
     * X.6.5   Conversion Failed
     */
    const CONVERSION_FAILED_STATUS = '6.5';

    /**
     * X.7.0   Other or undefined security status
     */
    const OTHER_SECURITY_STATUS = '7.0';

    /**
     * X.7.1   Delivery not authorized, message refused
     */
    const DELIVERY_NOT_AUTHORIZED_STATUS = '7.1';

    /**
     * X.7.2   Mailing list expansion prohibited
     */
    const MAILING_LIST_EXPANSION_PROHIBITED_STATUS = '7.2';

    /**
     * X.7.3   Security conversion required but not possible
     */
    const SECURITY_CONVERSION_NOT_POSSIBLE_STATUS = '7.3';

    /**
     * X.7.4   Security features not supported
     */
    const SECURITY_FEATURE_NOT_SUPPORTED_STATUS = '7.4';

    /**
     * X.7.5   Cryptographic failure
     */
    const CRYPTOGRAPHIC_FAILURE_STATUS = '7.5';

    /**
     * X.7.6   Cryptographic algorithm not supported
     */
    const CRYPTO_ALGORITHM_NOT_SUPPORTED_STATUS = '7.6';

    /**
     * X.7.7   Message integrity failure
     */
    const MESSAGE_INTEGRITY_FAILURE_STATUS = '7.7';

    /***** FIELDS *****/
    protected $_parsed = FALSE;
    protected $_dsnBody;
    protected $_recipients = array();

    /**
     *
     * @param type $_dsnBody
     */
    public function __construct($_dsnBody)
    {
        $this->_dsnBody = $_dsnBody;
    }

    /**
     * Get a status message for status value received
     *
     * @param string $statusValue
     * @return string Status message
     */
    protected function _getStatusMessage($statusValue)
    {

        $return = '';
        switch ($statusValue) {
            case self::OTHER_STATUS :
            case self::OTHER_ADDRESS_STATUS :
            case self::OTHER_MAILBOX_STATUS :
            case self::OTHER_MAIL_SYSTEM_STATUS :
            case self::OTHER_NETWORK_STATUS :
            case self::OTHER_PROTOCOL_STATUS :
            case self::OTHER_MEDIA_ERROR_STATUS :
            case self::OTHER_SECURITY_STATUS :
                $return = 'Unknown Status';
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Unknown Status: ' . $statusValue);
                break;
            case self::BAD_DESTINATION_MAILBOX_ADDRESS_STATUS :
            case self::BAD_DESTINATION_MAILBOX_ADDRESS_SYNTAX_STATUS :
            case self::BAD_DESTINATION_MAILBOX_ADDRESS_AMBIGUOUS_STATUS :
            case self::DESTINATION_MAILBOX_HAS_MOVED_STATUS :
                $return = 'Recipient Address Not Found';
                break;
            case self::BAD_DESTINATION_SYSTEM_ADDRESS_STATUS :
            case self::UNABLE_TO_ROUTE_STATUS :
                $return = 'Domain Not Found';
                break;
            case self::MAILBOX_FULL_STATUS :
                $return = 'Mailbox Full';
                break;
            default:
                $return = 'Unknown Status';
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' We got an untreated status Value: ' . $statusValue);
                break;
        }

        return $return;
    }

    /**
     * Parse dsn Messages
     */
    public function parse()
    {
        // Break per message and per recipient fields
        $out = preg_split('/(?<=\x0A)\x0D\x0A/', $this->_dsnBody);

        // Removing per message attributes
        array_shift($out); // TODO: form a header with per message attributes
        $errors = array();

        $this->_recipients = array();
        foreach ($out as $perRecipients) {
            $recipient = array();

            //Final-Recipient (Required)
            preg_match('/Final-Recipient:\s*[[:alnum:]-_]+;\s*(.+?)\x0D\x0A/', $perRecipients, $matches);
            if (empty($matches) || count($matches) != 2) {
                $errors[] = 'Error Parsing per Message Block: Final-Recipient Field';
            }
            $finalRecipient = $matches[1];
            $recipient['finalRecipient'] = $finalRecipient;
            unset($matches);

            // Status (Required)
            $classSelector = self::CLASS_SUCCESS_STATUS . self::CLASS_TRANSIENT_STATUS
                . self::CLASS_PERMANENT_STATUS;
            preg_match("/Status:\s*([$classSelector])\.(\d{1,3})\.(\d{1,3})\x0D\x0A/",
                $this->_dsnBody, $matches);
            if (empty($matches) || count($matches) != 4) {
                $errors[] = 'Error Parsing per Message Block: Status Field';
            } else {
                list(,$statusClass, $statusSubject, $statusDetail) = $matches;
                $statusValue = $statusSubject . '.' . $statusDetail;
                $recipient['status'] = array(
                    'class' => $statusClass,
                    'value' => $statusValue,
                    'msg'   => $this->_getStatusMessage($statusValue),
                );
            }
            unset($matches);

            //Original-Recipient (Optional)
            preg_match('/Original-Recipient:\s*[[:alnum:]-_]+;\s*(.+?)\x0D\x0A/',
                $perRecipients, $matches);
            if (empty($matches) || count($matches) != 2) {
                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                    . ' Wrong Delivery Notification Part: Original-Recipient Field not Found');
            }
            $recipient['originalRecipient'] = $matches[1];

            $this->_recipients[] = $recipient;
        }

        if (!empty($errors)) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__
                . ' Errors parsing Delivery Notification Status Message: ' . print_r($errors, true));
        }
        $this->_parsed = TRUE;
    }

    /**
     * Get the user friendly html formatted body
     *
     * @return string user friendly html formatted body
     */
    public function getHTMLFormatted()
    {
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

        // Body Content
        $html .= '<h3>' . $translate->_('Delivery Status Notification Message') . '</h3>';
        $html .= '<br /><br />';

        // Per Recipient Notification Status
        foreach ($this->_recipients as $recipient) {
            // Recipient Address
            $html .= '<b>' . $translate->_('Recipient') . ':</b> ';
            $html .= isset($recipient['originalRecipient']) ? $recipient['originalRecipient']
                        : $recipient['finalRecipient'];
            $html .= '<br />';

            // Delivery Status Notification Class
            $html .= '<b>' . $translate->_('Type') . ':</b> ';
            switch ($recipient['status']['class']) {
                case self::CLASS_TRANSIENT_STATUS :
                    $html .= $translate->_('Persistent Transient Failure');
                    break;
                case self::CLASS_PERMANENT_STATUS :
                    $html .= $translate->_('Permanent Failure');
                    break;
                case self::CLASS_SUCCESS_STATUS :
                    $html .= $translate->_('Successful Delivery');
                    break;
                default :
                    break;
            }
            $html .= '<br />';

            // Failure Message (Cause)
            $html .= '<b>' . $translate->_('Cause') . ':</b> ';
            $html .= $translate->_($recipient['status']['msg']);
            $html .= '<br /><br />';
        }

        $html .= '</body></html>';
        return $html;
    }
}
