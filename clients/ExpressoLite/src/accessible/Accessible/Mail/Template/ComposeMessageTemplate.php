<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Template for email composing.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/ComposeMessageTemplate.css" />
    <title><?= $VIEW->actionText ?> mensagem - ExpressoBr Acessível</title>
    <title>Seleção de pasta - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top">
    <div id="logomark"></div>
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#compose" accesskey="1">Ir para preencher campos [1]</a></li>
                <li><a href="#emailAttachments" accesskey="2">Ir para anexos de email [2]</a></li>
                <li><a href="<?= $VIEW->lnkBackUrl ?>" accesskey="v">Voltar para <?= $VIEW->lnkBackText ?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<form action="<?= $VIEW->lnkSendMessageAction ?>" method="post" enctype="multipart/form-data">

<div id="compose" name="compose">
    <h2 class="anchorsTitle"><?= $VIEW->actionText ?> mensagem</h2>
    <div class="dialogEmail contentAlign">
        <input type="hidden" name="folderId" value="<?= $VIEW->folderId ?>" />
        <input type="hidden" name="folderName" value="<?= $VIEW->folderName ?>" />
        <input type="hidden" name="page" value="<?= $VIEW->page ?>" />
        <input type="hidden" name="replyToId" value="<?= $VIEW->replyToId ?>" />
        <input type="hidden" name="forwardFromId" value="<?= $VIEW->forwardFromId ?>" />

        <div>
            <label for="subject">Assunto:</label>
            <input type="text" name="subject" id="subject" pattern="(?=.*\S).{1,}" title="O email esta sem assunto" required="required" value="<?= $VIEW->subject ?>" />
        </div>

        <div>
            <label for="addrTo">Destinatário:</label>
            <input type="email" multiple="multiple" name="addrTo" title="Informe um endereço de email válido e utilize a virgula como separador" id="addrTo" required="required" value="<?= $VIEW->to ?>"/>
        </div>

        <div>
            <label for="addrCc">Destinatário em cópia:</label>
            <input type="email" multiple="multiple" name="addrCc" id="addrCc" value="<?= $VIEW->cc ?>" />
        </div>

        <div>
            <label for="addrBcc">Destinatário em cópia oculta:</label>
            <input type="email" multiple="multiple" name="addrBcc" id="addrBcc" />
        </div >

        <div>
            <label for="messageBody">Mensagem:</label>
            <textarea name="messageBody" id="messageBody"></textarea>
        </div>

        <?php IF ($VIEW->signature !== '') : ?>
        <div class="composeSign"><?= $VIEW->signature ?></div>
        <?php ENDIF; ?>

        <?php IF ($VIEW->quotedBody !== '') : ?>
            <label for="quotedBody">Mensagem citada:</label>
            <div class="messageText" name="quotedBody" id="quotedBody">
                <?= $VIEW->quotedBody ?>
            </div>
        <?php ENDIF; ?>
    </div>
</div>

<!--
 * This element is a top page anchor link, it can be repeated on template files.
 * But only one link element, of these repeated, should contain 'accesskey="t"' attribute.
 -->
<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top" accesskey="t">voltar ao topo [t]</a></li>
    </ul>
</div>

<div id="emailAttachments" name="emailAttachments" >
    <h2 class="anchorsTitle">Anexos</h2>
    <?php IF (!EMPTY($VIEW->existingAttachments)) : ?>
        <div id="attachments" name="attachments" class="links systemLinks">
            <ul>
                <?php FOREACH ($VIEW->existingAttachments as $ATTACH) : ?>
                    <div class ="existingAttachsForExhibition">
                        <li>
                            <a href="<?= $ATTACH->lnkDownload ?>">
                                 Anexo <?= $ATTACH->accessibleFileName ?>
                                 (formato <?= $ATTACH->accessibleExtension ?>, tamanho <?= $ATTACH->accessibleFileSize ?>)
                            </a>
                        </li>
                        <p>
                            <input type="checkbox"  id="checkAttach_<?php ECHO $ATTACH->filename ?>" name="checkAttach_<?php ECHO $ATTACH->filename ?>" value="<?php ECHO $ATTACH->filename?>" title="Marcar este anexo para não ser enviado" />
                            <label for="checkAttach_<?php ECHO $ATTACH->filename ?>">Remover anexo</label>
                        </p>
                    </div>
                <? ENDFOREACH; ?>
            </ul>
        </div>
    <?php ENDIF; ?>

    <div class="contentAlign">
        <p class="attach">
            <label for="attach0">Anexar 1º arquivo:</label>&nbsp;
            <input type="file" name="attach0" id="attach0" />
        </p>
        <p class="attach">
            <label for="attach1">Anexar 2º arquivo:</label>&nbsp;
            <input type="file" name="attach1" id="attach1" />
        </p>
        <p class="attach">
            <label for="attach2">Anexar 3º arquivo:</label>&nbsp;
            <input type="file" name="attach2" id="attach2" />
        </p>
    </div>
</div>

<div class="composeFooter contentAlign">
    <p>
        <label for="important">Esta mensagem é importante</label>
        <input type="checkbox" name="important" id="important" title="Marcar essa mensagem como importante" />
    </p>
    <p>
        <input type="submit" value="Enviar" />
    <p>
</div>

</form>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>

</body>
</html>
