<?php
switch($_SERVER['REQUEST_METHOD'])
{
    case 'GET':
        header("Cache-Control: no-cache, must-revalidate");
        $file = fopen("infos.txt", "r");
        $response = fgets($file);
        fclose($file);
        header('Content-Type: application/json');
        echo json_encode($response, JSON_PRETTY_PRINT);
        break;
        default:
        // Requête invalide
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}
?>
