<!DOCTYPE html>
<!--
 * Expresso Lite Accessible
 * Template for feedback and confirm actions.
 *
 * @package   Lite
 * @license   http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author    Diogo Santos <diogo.santos@serpro.gov.br>
 * @author    Rodrigo Dias <rodrigo.dias@serpro.gov.br>
 * @copyright Copyright (c) 2015 Serpro (http://www.serpro.gov.br)
-->
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,user-scalable=no,initial-scale=1" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/general.css" />
    <link type="text/css" rel="stylesheet" href="./Accessible/Core/Template/ShowFeedbackTemplate.css" />
    <link rel="icon" type="image/png" href="../img/favicon.png" />
    <title>Aviso - ExpressoBr Acessível</title>
</head>
<body>

<div id="top" name="top">
    <div id="anchors" name="anchors" class="links systemLinks">
        <nav class="contentAlign">
            <ul>
                <li><a href="<?= $VIEW->destinationUrl ?>" accesskey="v"><?= $VIEW->destinationText ?> [v]</a></li>
            </ul>
        </nav>
    </div>
</div>

<h2 class="anchorsTitle">Mensagens</h2>
<div id="feedback" name="feedback" class="<?= $VIEW->typeMsg ?>" >
    <p id="feedbackMessage" name="feedbackMessage"> <?= $VIEW->message ?> </p>

    <div id="buttons" name="buttons" class="links linkAsButton">
        <hr />
        <ul>
            <?php FOREACH ($VIEW->buttons AS $BUTTON) : ?>
                <li><a href="<?= $BUTTON->url ?>"><?= $BUTTON->value ?></a></li>
            <?php ENDFOREACH; ?>
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