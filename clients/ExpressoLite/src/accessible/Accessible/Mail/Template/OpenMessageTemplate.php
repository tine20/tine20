<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Displays an email message.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Edgar Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/OpenMessageTemplate.css" />
    <title>Leitura de mensagem - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top">
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#emailHeader" accesskey="1">Ir para o cabeçalho do email [1]</a></li>
                <li><a href="#emailContent" accesskey="2">Ir para o corpo da mensagem [2]</a></li>
                <?php IF (count($VIEW->attachmentsForExhibition) > 0) : ?>
                <li><a href="#emailAttachments" accesskey="3">Ir para anexos de email [3]</a></li>
                <?php ENDIF?>
                <li><a href="#emailActions" accesskey="4">Ir para ações de email [4]</a></li>
                <li><a href="<?= $VIEW->lnkBack ?>" accesskey="v">Voltar para <?= $VIEW->folderName?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="emailHeader" name="emailHeader">
    <h2 class="anchorsTitle">Cabeçalho</h2>
    <div class="contentAlign">
        <div><span class="fieldName">Assunto:</span> <?= $VIEW->message->subject ?></div>

        <div><span class="fieldName">Remetente:</span> <?= $VIEW->message->from_name ?> (<?= $VIEW->message->from_email ?>)</div>

        <div><span class="fieldName">Data:</span> <?= $VIEW->formattedDate ?></div>

        <?php IF (!EMPTY($VIEW->message->to[0])) : ?>
            <div><span class="fieldName">Destinatário:</span> <?= implode(', ', $VIEW->message->to) ?></div>
        <?php ENDIF; ?>

        <?php IF (!EMPTY($VIEW->message->cc[0])) : ?>
            <div><span class="fieldName">Com cópia para:</span> <?= implode(', ', $VIEW->message->cc) ?></div>
        <?php ENDIF; ?>

        <?php IF (!EMPTY($VIEW->message->bcc[0])) : ?>
            <div><span class="fieldName">Com cópia oculta para:</span> <?= implode(', ', $VIEW->message->bcc) ?></div>
        <?php ENDIF; ?>

        <?php IF ($VIEW->message->importance) : ?>
            <div><span class="fieldName">Observação:</span> Esta mensagem foi marcada como importante.</div>
        <?php ENDIF; ?>
        <?php IF ($VIEW->message->has_attachment) : ?>
            <div class="onlyForScreenReaders" ><span class="fieldName">Observação:</span> Esta mensagem possui anexo.</div>
        <?php ENDIF; ?>
    </div>
</div>

<div id="emailContent" name="emailContent">
    <h2 class="anchorsTitle">Mensagem</h2>
    <div id="composePanelBody" name="composePanelBody" class="contentAlign">
        <?= $VIEW->message->body->message ?>
        <br/><br/>
        <blockquote><?= $VIEW->message->body->quoted ?></blockquote>
    </div>
</div>

<?php IF ($VIEW->message->has_attachment) : ?>
<div id="emailAttachments" name="emailAttachments" >
    <h2 class="anchorsTitle">Anexos</h2>
    <div id="attachments" name="attachments" class="links systemLinks">
        <ul>
            <?php FOREACH ($VIEW->attachmentsForExhibition as $ATTACH) : ?>
                <li>
                    <a href="<?= $ATTACH->lnkDownload ?>">
                         Abrir anexo <?= $ATTACH->accessibleFileName ?>
                         (formato <?= $ATTACH->accessibleExtension ?>, tamanho <?= $ATTACH->accessibleFileSize ?>)
                    </a>
                </li>
            <? ENDFOREACH; ?>
        </ul>
    </div>
</div>
<?php ENDIF; ?>

<div id="emailActions" name="emailActions">
    <h2 class="anchorsTitle">Ações</h2>
    <div class="links linkAsButton">
        <ul>
            <li><a href="<?= $VIEW->lnkReply ?>">Responder</a></li>
            <li><a href="<?= $VIEW->lnkReplyAll ?>">Responder a todos</a></li>
            <li><a href="<?= $VIEW->lnkForward ?>">Encaminhar</a></li>
            <li><a href="<?= $VIEW->lnkMark ?>">Marcar como não lida</a></li>
            <li><a href="<?= $VIEW->lnkDelete ?>">Apagar</a></li>
            <li><a href="<?= $VIEW->lnkMoveMsgToFolder ?>">Mover mensagem</a></li>
        </ul>
    <div>
</div>

</body>
</html>