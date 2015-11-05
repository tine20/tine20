<?php
/**
 * Expresso Lite Accessible
 * Loads login template.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */

namespace Accessible\Login;

use Accessible\Handler;

class Main extends Handler
{
    /**
     * @see Accessible\Handler::execute
     */
    public function execute($params)
    {
        $lastLogin = isset($_COOKIE['user']) ? urldecode($_COOKIE['user']) : '';
        $this->showTemplate('MainTemplate', (object) array(
            'lastLogin' => $lastLogin
        ));
    }
}
