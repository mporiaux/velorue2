<?php
switch($_SERVER['REQUEST_METHOD'])
{
    case 'GET':
        if(!empty($_GET["infos"]))
        {
            $response=$_GET["infos"];
            $str = str_replace("_", ":", $response, $count);
            $file = fopen("infos.txt", "w"); // ouvre le fichier en mode écriture
            fwrite($file, $str); // écrit le texte dans le fichier
            fclose($file); // ferme le fichier
            header('Content-Type: application/json');
            echo json_encode($str, JSON_PRETTY_PRINT);
            break;

        }

    default:
        // Requête invalide
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}
?>
