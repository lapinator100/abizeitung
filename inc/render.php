<?php
    $alerts = array();

    $base = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/html/base.html');
    $base = explode('{{content}}', $base);


    function renderHeader() {
        global $base;

        echo $base[0];
        echo getBaseUrl();
        echo $base[1];
    }

    function renderFooter() {
        global $base;

        echo $base[2];
    }


    function getLegibleAlertType($type) {
        switch ($type) {
            case 'success':
                return 'Erfolg';
            case 'info':
                return 'Info';
            case 'warning':
                return 'Warnung';
            case 'danger':
                return 'Fehler';
        }

        return '';
    }

    function renderAlerts() {
        global $alerts;

        foreach ($alerts as $alert) {
            $type = $alert['type'];
            $type_legible = $alert['type_legible'];
            $text = $alert['text'];

            include($_SERVER['DOCUMENT_ROOT'].'/html/alert.html');
        }
    }

    function addAlert($type, $text) {
        global $alerts;

        array_push($alerts, array('type' => $type,
                                  'type_legible' => getLegibleAlertType($type),
                                  'text' => $text));
    }


    function getBaseUrl() {
        if (!PRODUCTION_ENVIRONMENT) {
            return 'http://localhost/';
        }

        if (empty($_SERVER['HTTPS'])) {
            return 'http://abizeitung.ddns.net/';
        }

        return 'https://abizeitung.ddns.net/';
    }


    function render() {
        global $model;

        renderHeader();

        if (in_array('loggedIn', $_SESSION)
            && $_SESSION['loggedIn'] === true) {
            include($_SERVER['DOCUMENT_ROOT'].'/inc/home.php');
        } else {
            include($_SERVER['DOCUMENT_ROOT'].'/inc/login.php');
        }

        renderFooter();
    }

    function renderAsJson($object) {
        global $alerts;

        $object['alerts'] = $alerts;
        echo json_encode($object);
    }


    if (isset($_GET['error'])) {
        $error = $_GET['error'];

        switch ($error) {
            case '403':
                addAlert('danger', 'Zugriff verweigert');
                break;
            case '404':
                addAlert('danger', 'Seite konnte nicht gefunden werden');
                break;
            case '500':
                addAlert('danger', 'Interner Fehler');
                break;
        }
    }
?>
