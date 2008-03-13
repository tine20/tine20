<?php
/**
 * user registration email text (plain)
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 *
 */
?>

<?php echo $this->mailTextWelcome ?>

You have successfully registered to the new groupware system Tine 2.0. Please click on the activation link below to activate your account.

<?php echo $this->mailActivationLink ?>

Your username and password are: <?= $this->username ?> / <?= $this->password ?>

Sincerly yours,
   The Tine 2.0 Team
