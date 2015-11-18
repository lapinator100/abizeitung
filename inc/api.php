<?php
    const SECRET = '9w384z5ev7to8ce4tzow784xeozr8fzuoet89gzosr87t';

    const ERROR_UNKNOWN_ACTION = 'Unbekannter Befehl';
    const ERROR_NOT_LOGGED_IN = 'Nicht angemeldet';
    const ERROR_INVALID_CODE = 'Login-Code inkorrekt';
    const ERROR_NO_PERMISSIONS = 'Sie besitzen nicht die nötigen Zugriffsrechte';
    const ERROR_WHATSAPP_LOGIN_NO_PHONE_NUMBER = 'Für Deinen Account ist keine Handynummer hinterlegt. Wende dich an Steffen, um dies zu tun.';
    const ERROR_WHATSAPP_LOGIN_INVALID_CODE = 'Ungültiger Code';

    const ERROR_SUBMISSION_FAILED = 'Einreichen fehlgeschlagen';
    const INFO_SUBMISSION_SUCCESSFUL = 'Einreichen erfolgreich';

    const ERROR_REMOVAL_FAILED = 'Löschen fehlgeschlagen';
    const INFO_REMOVAL_SUCCESSFUL = 'Löschen erfolgreich';

    const ERROR_VOTING_FAILED = 'Bewerten fehlgeschlagen';

    const ERROR_GETPERSON_FAILED = 'Person konnte nicht gefunden werden';


    $actions = array('login', 'requestWhatsAppLoginCode', 'whatsAppLogin', 'logout',
                     'updateStatistic',
                     'submitMotto', 'removeMotto', 'vote',
                     'getPerson', 'submitComment', 'removeComment',
                     'submitQuote', 'removeQuote',
                     'submitRumour', 'removeRumour',
                     'submitMyth', 'removeMyth');


    function extractFromArray($array, $attribute) {
        $result = array();

        foreach ($array as $array_item) {
            array_push($result, $array_item[$attribute]);
        }

        return $result;
    }


    function handleRequest() {
        global $actions;

        $action = $_POST['action'];

        if (!in_array($action, $actions)
            || !is_callable($action)) {
            addAlert('danger', ERROR_UNKNOWN_ACTION);

            render();
            return;
        }

        if ($action !== 'login'
            && $action !== 'requestWhatsAppLoginCode'
            && $action !== 'whatsAppLogin'
            && $action !== 'updateStatistic'
            && (!in_array('loggedIn', $_SESSION)
                || $_SESSION['loggedIn'] !== true)) {
            addAlert('danger', ERROR_NOT_LOGGED_IN);

            render();
            return;
        }

        call_user_func($action);
    }


    function updateStatistic() {
        global $model;

        if ($_POST['sekret'] === SECRET) {
            if (!empty($_POST['id']) && !empty($_POST['content'])) {
                die($model->updateStatistic($_POST['id'], $_POST['content']));
            }
        }

        addAlert('danger', ERROR_UNKNOWN_ACTION);
        render();
    }


    function login() {
        global $model;

        if ($model->loginWithCode($_POST['loginCode']) === true) {
            $_SESSION['loggedIn'] = true;
            $_SESSION['code'] = $_POST['loginCode'];
        } else {
            $_SESSION['loggedIn'] = false;

            addAlert('danger', ERROR_INVALID_CODE);
        }

        usleep(250);
        render();
    }

    function requestWhatsAppLoginCode() {
        global $model;

        $output = array('success' => false);
        if ($model->loginViaWhatsApp($_POST['input']) === true) {
            $output['success'] = true;
        } else {
            addAlert('danger', ERROR_WHATSAPP_LOGIN_NO_PHONE_NUMBER);
        }

        usleep(250);
        renderAsJson($output);
    }

    function whatsAppLogin() {
        global $model;

        $output = array('success' => false);
        if ($model->loginViaWhatsAppCode($_POST['code']) === true) {
            $_SESSION['loggedIn'] = true;

            $output['success'] = true;
        } else {
            addAlert('danger', ERROR_WHATSAPP_LOGIN_INVALID_CODE);
        }

        usleep(250);
        renderAsJson($output);
    }

    function logout() {
        session_destroy();
        session_start();

        render();
    }


    function submitMotto() {
        global $model;

        $motto = $_POST['motto'];
        $author_id = $_SESSION['id'];

        if ($model->submitMotto($motto, $author_id)) {
            addAlert('success', INFO_SUBMISSION_SUCCESSFUL);
        } else {
            addAlert('danger', ERROR_SUBMISSION_FAILED);
        }

        render();
    }

    function removeMotto() {
        global $model;

        $motto_id = $_POST['motto_id'];

        if ($model->isMottoOwner($motto_id)) {
            if ($model->removeMottoAndVotes($motto_id)) {
                addAlert('success', INFO_REMOVAL_SUCCESSFUL);
            } else {
                addAlert('danger', ERROR_REMOVAL_FAILED);
            }
        } else {
            addAlert('danger', ERROR_NO_PERMISSIONS);
        }

        render();
    }

    function vote() {
        global $model;

        $user_id = $_SESSION['id'];
        $for = $_POST['for'];
        $item = $_POST['item'];
        $vote = $_POST['vote'];

        if ($vote !== 'up') {
          $vote = 'no';
        }

        if (!$model->vote($for, $item, $user_id, $vote)) {
            addAlert('danger', ERROR_VOTING_FAILED);
        }

        render();
    }


    function getPerson() {
        global $model;

        $type = $_POST['type'];
        $id = $_POST['id'];

        $output = array();
        $output['person'] = array('comments' => array(),
                                  'nicknames' => '',
                                  'futureProfessions' => '',
                                  'statistics' => '',
                                  'type' => $type);

        $result = $model->getPerson($type, $id);
        if ($result) {
            $output['person'] = $result;

            $nicknames = extractFromArray($output['person']['nicknames'], 'text');
            $futureProfessions = extractFromArray($output['person']['futureProfessions'], 'text');

            $output['person']['nicknames'] = implode(', ', $nicknames);
            $output['person']['futureProfessions'] = implode(', ', $futureProfessions);
        } else {
            addAlert('danger', ERROR_GETPERSON_FAILED);
        }

        renderAsJson($output);
    }


    function submitComment() {
        global $model;

        $type = $_POST['type'];
        $id = $_POST['id'];
        $comment = $_POST['comment'];

        $output = array();
        $result = $model->submitComment($type, $id, $comment, '');

        if ($result) {
            $output['comment'] = array('id' => $result,
                                       'text' => $comment,
                                       'type' => $type,
                                       'own' => true);
        } else {
            $output['comment'] = false;
        }

        renderAsJson($output);
    }

    function removeComment() {
        global $model;

        $comment_id = $_POST['comment_id'];

        $output = array('success' => false);

        if ($model->isCommentOwner($comment_id)) {
            if ($model->removeComment($comment_id)) {
                $output['success'] = true;

                addAlert('success', INFO_REMOVAL_SUCCESSFUL);
            } else {
                addAlert('danger', ERROR_REMOVAL_FAILED);
            }
        } else {
            addAlert('danger', ERROR_NO_PERMISSIONS);
        }

        renderAsJson($output);
    }


    function submitQuote() {
        global $model;

        $quote = $_POST['quote'];
        $class = $_POST['class_'];

        $output = array();
        $result = $model->submitComment('quotes', 0, $quote, $class);

        if ($result) {
            $output['quote'] = array('id' => $result,
                                     'text' => $quote,
                                     'class_' => $class,
                                     'own' => true);
        } else {
            $output['quote'] = false;
        }

        renderAsJson($output);
    }

    function removeQuote() {
        global $model;

        $quote_id = $_POST['quote_id'];

        $output = array('success' => false);

        if ($model->isCommentOwner($quote_id)) {
            if ($model->removeComment($quote_id)) {
                $output['success'] = true;

                addAlert('success', INFO_REMOVAL_SUCCESSFUL);
            } else {
                addAlert('danger', ERROR_REMOVAL_FAILED);
            }
        } else {
            addAlert('danger', ERROR_NO_PERMISSIONS);
        }

        renderAsJson($output);
    }


    function submitRumour() {
        global $model;

        $rumour = $_POST['rumour'];
        $output = array();
        $result = $model->submitComment('rumours', 0, $rumour, '');

        if ($result) {
            $output['rumour'] = array('id' => $result,
                                     'text' => $rumour,
                                     'own' => true);
        } else {
            $output['rumour'] = false;
        }

        renderAsJson($output);
    }

    function removeRumour() {
        global $model;

        $rumour_id = $_POST['rumour_id'];
        $output = array('success' => false);

        if ($model->isCommentOwner($rumour_id)) {
            if ($model->removeComment($rumour_id)) {
                $output['success'] = true;

                addAlert('success', INFO_REMOVAL_SUCCESSFUL);
            } else {
                addAlert('danger', ERROR_REMOVAL_FAILED);
            }
        } else {
            addAlert('danger', ERROR_NO_PERMISSIONS);
        }

        renderAsJson($output);
    }


    function submitMyth() {
        global $model;

        $myth = $_POST['myth'];
        $output = array();
        $result = $model->submitComment('myths', 0, $myth, '');

        if ($result) {
            $output['myth'] = array('id' => $result,
                                    'text' => $myth,
                                    'own' => true);
        } else {
            $output['myth'] = false;
        }

        renderAsJson($output);
    }

    function removeMyth() {
        global $model;

        $myth_id = $_POST['myth_id'];
        $output = array('success' => false);

        if ($model->isCommentOwner($myth_id)) {
            if ($model->removeComment($myth_id)) {
                $output['success'] = true;

                addAlert('success', INFO_REMOVAL_SUCCESSFUL);
            } else {
                addAlert('danger', ERROR_REMOVAL_FAILED);
            }
        } else {
            addAlert('danger', ERROR_NO_PERMISSIONS);
        }

        renderAsJson($output);
    }


    handleRequest();
?>
