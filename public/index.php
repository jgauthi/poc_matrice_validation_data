<?php
use Jgauthi\Tools\Matrice\MatriceFields;

require_once __DIR__.'/../vendor/autoload.php';

$file_ini = __DIR__.'/../config/matrice-example.ini';
if (!empty($_FILES['ini']['tmp_name']) && filesize($_FILES['ini']['tmp_name']) > 0) {
    $file_ini = $_FILES['ini']['tmp_name'];
} elseif (!empty($_GET['file']) && is_readable($_GET['file'])) {
    $file_ini = $_GET['file'];
} elseif (PHP_SAPI === 'cli' && !empty($argv[1]) && is_readable($argv[1])) {
    $file_ini = $argv[1];
}

$rules = parse_ini_file($file_ini, true);
$matrice = new MatriceFields($rules);
$matrice->check_maxlength = true;

// Vérification des champs
if ('matrice-example.ini' === basename($file_ini)) {
    $load_data = json_decode(file_get_contents(__DIR__ . '/asset/random-data-test.json'), true);

    $check_fields = [];
    foreach ($load_data as $data_check) {
        $check_fields[] = $matrice->check_fields($data_check);
    }
}

// Export sous forme de table pour de la documentation
$table = $matrice->export_fields_to_user();

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title>POC Matrice Fields</title>

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <style>
        tbody th:nth-child(1), tbody td:nth-child(1) 	{ background-color: #F25769; color: white; }
        td.required 									{ text-align: center; }
    </style>

</head>
<body>
<main role="main" class="<?=((!empty($GLOBALS['class_main'])) ? $GLOBALS['class_main'] : 'container')?>">
    <h1>Matrice fields</h1>

    <form action="<?=$_SERVER['PHP_SELF']?>" method="POST" enctype="multipart/form-data" style="float: right; margin-top: -50px;">
        <strong style="margin-right: 10px;">Uploader un .ini</strong>
        <input type="file" name="ini" style="display: inline" accept=".ini">
        <input type="submit" value="Envoyer">
    </form>

    <?php if (isset($check_fields) && !empty($load_data)): ?>
        <?php /*
        <h3>Données présentes</h3>
        <?=array_to_html_table($load_data)?>
        */ ?>

        <h3>Vérification des données</h3>
        <?php foreach ($load_data as $data_check): ?>
        <div class="row">
            <div class="col-sm-7">
            <?php $check_fields = $matrice->check_fields($data_check); ?>

            <?php if (true !== $check_fields): ?>
                <p>Erreurs détectés lors du parsage pour la ligne de csv en cours: </p>
                <ul>
                    <?php foreach ($check_fields as $field_name => $error): ?>
                        <li class="<?=$field_name; ?>"><?=$error; ?></li>
                    <?php endforeach; ?>
                </ul>

            <?php else: ?>
                <p>Aucune erreur trouvé dans les data, conversion des données chargés au format voulu: </p>
                <?php var_dump($matrice->export_fields_to_array()); ?>

            <?php endif; ?>
            </div>
            <div class="col-sm-5"><?php var_dump($data_check); ?></div>
        </div>
        <hr>
        <?php endforeach; ?>


    <?php endif; ?>

    <h3>Documentation gestion des champs</h3>
    <p>Fichier de matrice: "<em><?=$file_ini?></em>"</p>
    <?=$table?>
    <hr>

    <p>Colonnes CSV: </p>
    <ul>
    <?php
    $keys = array_keys($rules);
    foreach ($keys as $key) {
        echo "<li>{$key}</li>";
    }
    ?>
    </ul>
</main>
</body>
</html>