<?php
    $mottos = $model->getMottos();
    $quotes = $model->getQuotes();
    $rumours = $model->getRumours();
    $myths = $model->getMyths();

    include($_SERVER['DOCUMENT_ROOT'].'/html/home.html');
?>
