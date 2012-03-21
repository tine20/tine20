<?php
/**
 * user registration email text (html)
 * 
 * @package     Tinebase
 * @subpackage  Views
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */
?>

<h1><?php echo $this->mailTextWelcome ?></h1>
<p>You have successfully registered to the new groupware system Tine 2.0. </p>
<?php if (isset($this->mailActivationLink)) { ?>
    <p>Please click on the activation link below to activate your account.</p>
    <p><a href="<?php echo $this->mailActivationLink ?>"><?php echo $this->mailActivationLink ?></a></p>
<?php } ?>
<p>Your username and password are: <?php echo $this->username ?> / <?php echo $this->password ?></p>
<p>Sincerly yours,<br/>
   The Tine 2.0 Team</p>

