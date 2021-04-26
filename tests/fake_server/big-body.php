<?php

$dataFromPost = file_get_contents('php://input');
$dataFromFolder = file_get_contents('noise.jpg');

if($dataFromPost === $dataFromFolder) {
    echo 'ok';
}