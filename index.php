<?php
    const PRODUCTION_ENVIRONMENT = false;

    if (PRODUCTION_ENVIRONMENT) {
        error_reporting(0);
        ini_set('display_errors', 'Off');
    } else {
        error_reporting(-1);
        ini_set('display_errors', 'On');
    }

    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/model.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/inc/render.php');


    header('Content-Type: text/html; charset=utf-8');

    ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 7);
    session_set_cookie_params(60 * 60 * 24 * 7);
    session_start();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        render();
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include($_SERVER['DOCUMENT_ROOT'].'/inc/api.php');
    }
?>
