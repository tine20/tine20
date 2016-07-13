<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry page for accessible module.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Login/Template/MainTemplate.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/ComposeMessageTemplate.css" />
    <title>Login - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" class="onlyForScreenReaders">
    <div id="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#credent" accesskey="1">Ir para o formulário [1]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="credent">
    <h2><img id="logo_top" src="../img/logo-expressobr-top.png" alt="Logotipo do ExpressoBr Acessível"/></h2>
    <form action="." id="frmLogin" name="frmLogin"  class="form" method="post">
        <input type="hidden" id="r" name="r" value="Login.Login">
        <div class="frmLoginFields">
            <label for="user">Usuário: </label>
            <input id="user" name="user" type="text" placeholder="Digite o email do usuário" value="<?= $VIEW->lastLogin ?>" tabindex="1" required="required" />
        </div>

        <div class="frmLoginFields">
             <label for="pwd">Senha: </label>
             <input id="pwd" name="pwd" type="password" placeholder="Digite a senha" tabindex="2" required="required" />
        </div>

        <div id="frmLoginSubmit">
             <input type="submit" value="login" tabindex="3"/>
        </div>
    </form>
</div>

<div id="expressoBrAccess">
    <a title="Ir para o ExpressoBr" accesskey="e" href="../">Ir para o ExpressoBr [e]</a>
</div>

</body>
</html>
