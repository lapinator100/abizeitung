<?php
    $destinations = $model->getDestinations();
    $mottos = $model->getMottos();
    $votings = $model->getVotings();
    $quotes = $model->getQuotes();
    $rumours = $model->getRumours();
    $myths = $model->getMyths();

    include($_SERVER['DOCUMENT_ROOT'].'/html/home.html');
?>
