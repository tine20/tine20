<?php
/**
 * Expresso Lite Accessible
 * Message formatting routines.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @copyright Copyright (c) 2016 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Core;

class MessageUtils
{
    /**
     * Sanitize email message, getting only the content between the html <body>
     * tag if it's exists, otherwise retrieves the entire content.
     *
     * @param string $message Email message contained between <body> html tag
     * @return string The content between <body> html tag of whole message
     */
    public static function getSanitizedBodyContent($message)
    {
        if (!is_null($message)) {
            $found = preg_match("/<body[^>]*>(.*?)<\/body>/is", $message, $matches);
            return $found ? $matches[1] : $message;
        }

        return '';
    }
}