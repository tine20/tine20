<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Entry index page for mail module and change messages to another folder.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Edgar de Lucca <edgar.lucca@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
 */
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Mail/Template/OpenFolderTemplate.css" />
    <title>Seleção de pasta - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top">
    <div id="logomark"></div>
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="#folders" accesskey="1">Ir para listagem de pastas [1]</a></li>
                <?php IF ($VIEW->isMsgBeingMoved) : ?>
                    <li><a href="<?= $VIEW->lnkRefreshMessage ?>" accesskey="m">Voltar para mensagem de origem [m]</a></li>
                <?php ENDIF; ?>
                <li><a href="<?= $VIEW->lnkRefreshFolder ?>" accesskey="v">Voltar para <?= $VIEW->folderName ?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<div id="folders" name="folders">
<h2 class="anchorsTitle">Pastas</h2>
    <div class="links systemLinks">
        <ul >
            <?php FOREACH ($VIEW->folders AS $FOLDER) : ?>
                <li><a href="<?= $FOLDER->lnkOpenFolder ?> " title="<?= $FOLDER->title ?>"><?= $FOLDER->localName ?></a></li>
            <?php ENDFOREACH ?>
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

</body>
</html>
