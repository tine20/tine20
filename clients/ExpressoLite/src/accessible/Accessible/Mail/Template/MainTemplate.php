<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for mail module.
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
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/MainTemplate.css" />
    <title><?= $VIEW->curFolder->localName ?> - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top" >
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#menu" accesskey="1">Ir para o menu [1]</a></li>
                <li><a href="#headlines" accesskey="2">Ir para lista de emails [2]</a></li>
                <?php IF ($VIEW->curFolder->totalMails > 0) : ?>
                <li><a href="#actions" accesskey="3">Ir para ações de emails [3]</a></li>
                <?php ENDIF; ?>
                <?php IF ($VIEW->curFolder->totalMails > $VIEW->requestLimit) : ?>
                <li><a href="#pagination" accesskey="4">Ir para paginação de emails [4]</a></li>
                <?php ENDIF; ?>
            </ul>
        </nav>
    </div>
</div>

<div id="menu" name="menu">
    <h2 class="anchorsTitle">Menu</h2>
    <div class="links systemLinks">
        <ul>
            <li><a href="<?= $VIEW->lnkRefreshFolder ?>" accesskey="a">Atualizar lista de emails da pasta <?= $VIEW->curFolder->localName ?> [a]</span></a></li>
            <li><a href="<?= $VIEW->lnkChangeFolder ?>" accesskey="p">Selecionar outra pasta [p]</a></li>
            <li><a href="<?= $VIEW->lnkComposeMessage ?>" accesskey="n">Escrever novo email [n]</span></a></li>
            <li><a href="<?= $VIEW->lnkLogoff ?>" title="Sair do expressobr acessível" accesskey="s">Sair do sistema [s]</a></li>
        </ul>
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

<form action="<?= $VIEW->lnkConfirmMessageAction ?>" method="post">
<div id="headlines" name="headlines">
    <h2 class="anchorsTitle">Lista de emails</h2>
    <?php IF ($VIEW->curFolder->totalMails > 0) : ?>
        <table id="headlinesTable" border="1" class="clickableCell contentAlign">
            <caption>
                A pasta <?= $VIEW->curFolder->localName ?> contém <?= $VIEW->curFolder->totalMails ?> emails,
                <?php IF ($VIEW->curFolder->unreadMails > 0) : ?>
                    sendo <?= $VIEW->curFolder->unreadMails ?> não lido,
                <?php ENDIF; ?>
                listando de <?= $VIEW->start ?> a <?= $VIEW->limit ?>.
            </caption>
            <thead>
                <tr>
                    <th id="id">Número</th>
                    <th id="senderSubject">Remetente / Assunto</th>
                    <th id="date">Data</th>
                    <th id="observations">Observações</th>
                    <th id="mark" aria-hidden="true">Marcar</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $SEQ =  $VIEW->start;
                FOREACH ($VIEW->headlines AS $HEADLINE) :
                ?>
                <tr class="<?= $HEADLINE->unread ? 'markUnread' : '' ?>">
                    <td headers="id" class="alignCenter">
                        <span><?= $SEQ ?></span>
                    </td>
                    <td headers="senderSubject" class="alignLeft">
                        <a href="<?= $HEADLINE->lnkOpen ?>" title="Abrir mensagem <?= $SEQ ?>">
                            De: <span> <?= $HEADLINE->from->name ?></span>
                            <br />
                            <span ><?= $HEADLINE->subject ?></span>
                        </a>
                    </td>
                    <td headers="date" class="alignCenter">
                        <span><?= $HEADLINE->received ?></span>
                    </td>
                    <td headers="observations" class="alignLeft">
                        <?php IF ($HEADLINE->hasAttachment) : ?>
                        <div class="flags flag-attach" title="Este email contém anexo">&nbsp;</div>
                        <?php ENDIF; ?>
                        <?php IF ($HEADLINE->important) : ?>
                        <div class="flags flag-important" title="O remetente deste email o marcou como importante">&nbsp;</div>
                        <?php ENDIF; ?>
                    </td>
                    <td headers="mark" class="alignCenter">
                        <input type="checkbox" title="mensagem <?php ECHO $SEQ ?>" id="check_<?php ECHO $SEQ ?>" name="check_<?php ECHO $SEQ ?>" value="<?= $HEADLINE->id ?>" >
                    </td>
                </tr>
                <?php ++$SEQ; ?>
                <?php ENDFOREACH; ?>
            </tbody>
        </table>
    <?php ELSE : ?>
        <p class="alignCenter emptyHeadlines">A pasta <?= $VIEW->curFolder->localName ?> está vazia.</p>
    <?php ENDIF;?>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>

<?php IF ($VIEW->curFolder->totalMails > 0) : ?>
<div id="actions" name="actions">
    <h2 class="anchorsTitle">Ações</h2>
    <div class="contentAlign">
        <button type="submit" name="actionProcess" value="<?= $VIEW->action_mark_unread ?>">Marcar como não lido</button>
    </div>
</div>

<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>
<?php ENDIF;?>
</form>

<?php IF ($VIEW->curFolder->totalMails > $VIEW->requestLimit) : ?>
<div id="pagination" name="pagination">
    <h2 class="anchorsTitle">Paginação</h2>
    <div class="links linkAsButton">
        <ul>
            <?php IF ($VIEW->page > 1) : ?>
                <li><a href="<?= $VIEW->lnkPrevPage ?>">Página Anterior</a></li>
            <?php ENDIF; ?>
            <?php IF ($VIEW->page * $VIEW->requestLimit < $VIEW->curFolder->totalMails) : ?>
                <li><a href="<?= $VIEW->lnkNextPage ?>">Próxima Página</a></li>
            <?php ENDIF; ?>
        </ul>
    </div>
</div>
<div class="backToTop links systemLinks contentAlign">
    <ul>
        <li><a href="#top">voltar ao topo [t]</a></li>
    </ul>
</div>
<?php ENDIF; ?>

</body>
</html>
