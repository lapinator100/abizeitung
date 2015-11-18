<?php
    class Model {

        const WHATSAPP_MESSAGE_START = "Dein Code: ";
        const WHATSAPP_MESSAGE_END = "\n\nDieser Code muss innerhalb einer Stunde eingelöst werden. Solltest Du keinen Code angefordert haben, kannst du diese Nummer ruhigen Gewissens blockieren.";

        protected $notificationTypes = array('pupils' => 'Neuer Kommentar bei dir', 'pupils_nicknames' => 'Neuer Spitzname für dich', 'pupils_futureProfessions', 'Neuer wird-später-mal-Eintrag für dich');
        protected $db;


        function __construct() {
            if (PRODUCTION_ENVIRONMENT) {
                require_once($_SERVER['DOCUMENT_ROOT'].'/inc/db.php');
            } else {
                require_once('../db.php');
            }

            $this->db = getDatabaseConnection();
        }

        function __destruct() {
            $this->db->close();
        }


        function log($action, $item=null, $status=null, $context=null) {
            $logQuery = $this->db->prepare("INSERT INTO log (action, item, status, context) VALUES (?, ?, ?, ?);");
            $logQuery->bind_param('siss', $action, $item, $status, $context);

            $result = $logQuery->execute();
            $logQuery->close();

            return true;
        }


        function loginWithCode($code) {
            $loginCode = md5($code);

            if ($loginQuery = $this->db->prepare("SELECT id, name FROM users WHERE loginCode = ?;")) {
                $loginQuery->bind_param('s', $loginCode);
                $loginQuery->bind_result($user_id, $user_name);

                $result = $loginQuery->execute();
                $loginQuery->store_result();

                if ($loginQuery->num_rows === 1) {
                    if ($loginQuery->fetch()) {
                        $_SESSION['id'] = $user_id;
                        $_SESSION['name'] = utf8_encode($user_name);

                        $this->log('loginWithCode', $user_id, 'successful');

                        return true;
                    }
                }

                $loginQuery->close();
            }

            $this->log('loginWithCode', null, 'failed');

            return false;
        }

        function loginViaWhatsApp($input) {
            $input = $this->db->real_escape_string($input);
            $name = preg_replace('/,/', ' ', trim($input));
            $name = preg_replace('/\s+/', ' ', $name);

            $phone_number = preg_replace('/[^0-9]/', '', $input);
            if ($phone_number !== '' && $phone_number[0] == '0') {
                $phone_number = '49'.ltrim($phone_number, '0');
            }

            if ($loginQuery = $this->db->prepare("SELECT id, phone_number FROM users WHERE name = ? OR phone_number = ?;")) {
                $loginQuery->bind_param('ss', $name, $phone_number);
                $loginQuery->bind_result($user_id, $phone_number);

                $loginQuery->execute();
                $loginQuery->store_result();

                if ($loginQuery->num_rows === 1) {
                    if ($loginQuery->fetch()) {
                        if (strlen($phone_number) >= 8) {
                            $loginCode = $this->generateLoginCode($user_id);
                            $text = self::WHATSAPP_MESSAGE_START.$loginCode.self::WHATSAPP_MESSAGE_END;
                            $this->sendWhatsAppMessage($phone_number, $text);

                            $this->log('loginViaWhatsApp', $user_id, 'successful', $input);

                            return true;
                        }
                    }
                }

                $loginQuery->close();
            }

            $this->log('loginViaWhatsApp', null, 'failed', $input);

            return false;
        }

        function loginViaWhatsAppCode($code) {
            $code = $this->db->real_escape_string($code);

            if ($loginQuery = $this->db->prepare("SELECT user_id FROM whatsApp_loginCodes WHERE code = ? AND NOW() < expires;")) {
                $loginQuery->bind_param('s', $code);
                $loginQuery->bind_result($user_id);

                $result = $loginQuery->execute();
                $loginQuery->store_result();

                if ($loginQuery->num_rows === 1) {
                    if ($loginQuery->fetch()) {
                        $this->deleteLoginCodesForUser($user_id);
                        $user = $this->getUser($user_id);

                        $_SESSION['id'] = $user_id;
                        $_SESSION['name'] = $user['name'];

                        $this->log('loginViaWhatsAppCode', $user_id, 'successful', $code);

                        return true;
                    }
                }

                $loginQuery->close();
            }

            $this->log('loginViaWhatsAppCode', null, 'failed', $code);

            return false;
        }

        function generateRandomCode($length) {
            $characters = '0123456789';
            $charactersLength = strlen($characters);
            $randomCode = '';

            for ($i = 0; $i < $length; $i++) {
                $randomCode .= $characters[rand(0, $charactersLength - 1)];
            }

            return $randomCode;
        }

        function deleteLoginCodesForUser($user_id) {
            if ($codeQuery = $this->db->prepare("DELETE FROM whatsApp_loginCodes WHERE user_id = ?;")) {
                $codeQuery->bind_param('i', $user_id);

                if ($codeQuery->execute()) {
                    return true;
                }

                $codeQuery->close();
            }

            return false;
        }

        function generateLoginCode($user_id) {
            $code = $this->generateRandomCode(4);

            $this->deleteLoginCodesForUser($user_id);
            $codeQuery = $this->db->prepare("INSERT INTO whatsApp_loginCodes (user_id, code, expires) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR));");
            $codeQuery->bind_param('is', $user_id, $code);

            $result = $codeQuery->execute();
            $codeQuery->close();

            $this->log('generateLoginCode', $user_id, 'successful', $code);

            return $code;
        }

        function getUser($id) {
            $user = array('name' => '');
            $id = $this->db->real_escape_string($id);

            if ($userQuery = $this->db->prepare("SELECT name, phone_number, notifications FROM users WHERE id = ?;")) {
                $userQuery->bind_param('i', $id);
                $userQuery->bind_result($user_name, $phone_number, $notifications);

                $result = $userQuery->execute();
                $userQuery->store_result();

                if ($userQuery->num_rows === 1) {
                    if ($userQuery->fetch()) {
                        $user['name'] = utf8_encode($user_name);
                        $user['phone_number'] = $phone_number;
                        $user['notifications'] = $notifications === 1;
                    }
                }

                $userQuery->close();
            }

            return $user;
        }


        function getDestinations() {
            $destinations = array(
                array(
                    'id' => 0,
                    'text' => 'Bulgarien',
                    'score' => $this->getVoteScore('destination', 0),
                    'ownVote' => $this->getVote('destination', 0, $_SESSION['id'])
                ), array(
                    'id' => 1,
                    'text' => 'Mallorca',
                    'score' => $this->getVoteScore('destination', 1),
                    'ownVote' => $this->getVote('destination', 1, $_SESSION['id'])
                )
            );

            uasort($destinations, array($this, 'compareVoteScore'));

            return $destinations;
        }


        function getMottos() {
            $mottos = array();

            if ($mottoQuery = $this->db->prepare("SELECT id, author, text FROM mottos;")) {
                $mottoQuery->bind_result($motto_id, $author_id, $motto_text);

                $result = $mottoQuery->execute();
                $mottoQuery->store_result();

                while ($mottoQuery->fetch()) {
                    $author = $this->getUser($author_id);
                    $ownVote = $this->getVote('motto', $motto_id, $_SESSION['id']);

                    $motto = array();
                    $motto['id'] = $motto_id;
                    $motto['own'] = $_SESSION['id'] === $author_id;
                    $motto['author'] = $author['name'];
                    $motto['text'] = utf8_encode($motto_text);
                    $motto['upvoted'] = $ownVote === 'up';
                    $motto['downvoted'] = $ownVote === 'down';
                    $motto['score'] = $this->getVoteScore('motto', $motto_id);

                    array_push($mottos, $motto);
                }

                $mottoQuery->close();
            }

            uasort($mottos, array($this, 'compareVoteScore'));

            return $mottos;
        }

        function submitMotto($motto, $author_id) {
            $motto = $this->db->real_escape_string(htmlentities($motto, ENT_QUOTES));
            $author_id = $this->db->real_escape_string($author_id);

            if ($submitQuery = $this->db->prepare("INSERT INTO mottos (author, text) VALUES (?, ?);")) {
                $submitQuery->bind_param('is', $author_id, $motto);

                $result = $submitQuery->execute();
                $this->vote('motto', $submitQuery->insert_id, $author_id, 'up');

                $submitQuery->close();

                $this->log('submitMotto', $author_id, 'successful', $motto);

                return $result;
            }

            $this->log('submitMotto', $author_id, 'failed', $motto);

            return false;
        }

        function removeMottoAndVotes($motto_id) {
            $motto_id = $this->db->real_escape_string($motto_id);

            if ($voteQuery = $this->db->prepare("DELETE FROM votes WHERE type = 'motto' AND item = ?;")) {
                $voteQuery->bind_param('i', $motto_id);
                $voteQuery->execute();
                $voteQuery->close();

                if ($mottoQuery = $this->db->prepare("DELETE FROM mottos WHERE id = ?;")) {
                    $mottoQuery->bind_param('i', $motto_id);

                    if ($mottoQuery->execute()) {
                        $this->log('removeMottoAndVotes', $motto_id, 'successful');

                        return true;
                    }

                    $mottoQuery->close();
                }
            }

            $this->log('removeMottoAndVotes', $motto_id, 'failed');

            return false;
        }

        function isMottoOwner($motto_id) {
            $motto_id = $this->db->real_escape_string($motto_id);

            if ($mottoQuery = $this->db->prepare("SELECT author FROM mottos WHERE id = ?;")) {
                $mottoQuery->bind_param('i', $motto_id);
                $mottoQuery->bind_result($user_id);

                $result = $mottoQuery->execute();

                if ($mottoQuery->fetch()) {
                    return $_SESSION['id'] === $user_id;
                }

                $mottoQuery->close();
            }

            return false;
        }


        function vote($for, $item, $user_id, $vote) {
            $for = $this->db->real_escape_string($for);
            $item = $this->db->real_escape_string($item);
            $user_id = $this->db->real_escape_string($user_id);
            $vote = $this->db->real_escape_string($vote);

            if ($voteQuery = $this->db->prepare("DELETE FROM votes WHERE type = ? AND item = ? AND user_id = ?;")) {
                $voteQuery->bind_param('sii', $for, $item, $user_id);
                $voteQuery->execute();
                $voteQuery->close();
            }

            if ($vote === 'no') {
                return true;
            }

            if ($voteQuery = $this->db->prepare("INSERT INTO votes (user_id, type, item, vote) VALUES (?, ?, ?, ?);")) {
                $voteQuery->bind_param('isis', $user_id, $for, $item, $vote);

                $result = $voteQuery->execute();
                $voteQuery->close();

                $this->log('vote', $user_id, $vote, $for.','.$item);

                return $result;
            }

            return false;
        }

        function getVote($for, $item, $user_id) {
            $vote = 'no';

            $for = $this->db->real_escape_string($for);
            $item = $this->db->real_escape_string($item);
            $user_id = $this->db->real_escape_string($user_id);

            if ($voteQuery = $this->db->prepare("SELECT vote FROM votes WHERE type = ? AND item = ? AND user_id = ?;")) {
                $voteQuery->bind_param('sii', $for, $item, $user_id);
                $voteQuery->bind_result($vote);

                $result = $voteQuery->execute();
                $voteQuery->fetch();

                $voteQuery->close();
            }

            return $vote;
        }

        function getVoteScore($for, $item) {
            $score = 0;

            $for = $this->db->real_escape_string($for);
            $item = $this->db->real_escape_string($item);

            if ($voteQuery = $this->db->prepare("SELECT vote FROM votes WHERE type = ? AND item = ?;")) {
                $voteQuery->bind_param('si', $for, $item);
                $voteQuery->bind_result($vote);

                $result = $voteQuery->execute();

                while ($voteQuery->fetch()) {
                    if ($vote === 'up') {
                        $score++;
                    } elseif ($vote === 'down') {
                        $score--;
                    }
                }

                $voteQuery->close();
            }

            return $score;
        }

        function compareVoteScore($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }

            return ($a['score'] > $b['score']) ? -1 : 1;
        }


        function getPupils() {
            $pupils = array();

            if ($pupilsQuery = $this->db->prepare("SELECT id, name FROM users ORDER BY name;")) {
                $pupilsQuery->bind_result($pupil_id, $pupil_name);

                $result = $pupilsQuery->execute();
                $pupilsQuery->store_result();

                while ($pupilsQuery->fetch()) {
                    $pupil = array();
                    $pupil['id'] = $pupil_id;
                    $pupil['name'] = utf8_encode($pupil_name);

                    array_push($pupils, $pupil);
                }

                $pupilsQuery->close();
            }

            return $pupils;
        }

        function getTeachers() {
            $teachers = array();

            if ($teachersQuery = $this->db->prepare("SELECT id, name, class FROM teachers ORDER BY name;")) {
                $teachersQuery->bind_result($teacher_id, $teacher_name, $teacher_class);

                $result = $teachersQuery->execute();
                $teachersQuery->store_result();

                while ($teachersQuery->fetch()) {
                    $teacher = array();
                    $teacher['id'] = $teacher_id;
                    $teacher['name'] = utf8_encode($teacher_name);
                    $teacher['class'] = utf8_encode($teacher_class);

                    array_push($teachers, $teacher);
                }

                $teachersQuery->close();
            }

            return $teachers;
        }

        function getPerson($type, $id) {
            switch ($type) {
                case 'pupils':
                    return $this->getPupil($id);
                case 'teachers':
                    return $this->getTeacher($id);
                default:
                    return false;
            }
        }

        function getPupil($id) {
            $id = $this->db->real_escape_string($id);

            $pupil = array();

            if ($pupilQuery = $this->db->prepare("SELECT id, name FROM users WHERE id = ?;")) {
                $pupilQuery->bind_param('i', $id);
                $pupilQuery->bind_result($pupil_id, $pupil_name);

                $result = $pupilQuery->execute();
                $pupilQuery->store_result();

                if ($pupilQuery->fetch()) {
                    $pupilQuery->close();

                    $pupil['id'] = $pupil_id;
                    $pupil['name'] = utf8_encode($pupil_name);
                    $pupil['comments'] = $this->getComments('pupils', $pupil_id);
                    $pupil['nicknames'] = $this->getComments('pupils_nicknames', $pupil_id);
                    $pupil['futureProfessions'] = $this->getComments('pupils_futureProfessions', $pupil_id);
                    $pupil['statistics'] = $this->getStatistics($pupil_id, 'pupils');
                    $pupil['type'] = 'pupils';

                    return $pupil;
                }

                $pupilQuery->close();
            }

            return false;
        }

        function getTeacher($id) {
            $id = $this->db->real_escape_string($id);

            $teacher = array();

            if ($teacherQuery = $this->db->prepare("SELECT id, name, class FROM teachers WHERE id = ?;")) {
                $teacherQuery->bind_param('i', $id);
                $teacherQuery->bind_result($teacher_id, $teacher_name, $teacher_class);

                $result = $teacherQuery->execute();
                $teacherQuery->store_result();

                if ($teacherQuery->fetch()) {
                    $teacherQuery->close();

                    $teacher['id'] = $teacher_id;
                    $teacher['name'] = utf8_encode($teacher_name);
                    $teacher['class'] = utf8_encode($teacher_class);
                    $teacher['comments'] = $this->getComments('teachers', $teacher_id);
                    $teacher['nicknames'] = $this->getComments('teachers_nicknames', $teacher_id);
                    $teacher['futureProfessions'] = array();
                    $teacher['statistics'] = $this->getStatistics($teacher_id, 'teachers');
                    $teacher['type'] = 'teachers';

                    return $teacher;
                }

                $teacherQuery->close();
            }

            return false;
        }

        function getStatistics($user_id, $type) {
            $statistics = '';

            if ($statisticsQuery = $this->db->prepare("SELECT text, content FROM statistics WHERE user_id = ? AND type = ?;")) {
                $statisticsQuery->bind_param('is', $user_id, $type);
                $statisticsQuery->bind_result($statistic_text, $statistic_content);

                $result = $statisticsQuery->execute();
                $statisticsQuery->store_result();

                while ($statisticsQuery->fetch()) {
                    $statistics .= $statistic_text.': <b>'.$statistic_content."</b><br>";
                }

                $statisticsQuery->close();
            }

            return $statistics;
        }

        function updateStatistic($id, $content) {
            $id = $this->db->real_escape_string($id);
            $content = $this->db->real_escape_string($content);

            if ($statisticsQuery = $this->db->prepare("UPDATE statistics SET content = ? WHERE id = ?;")) {
                $statisticsQuery->bind_param('ii', $content, $id);
                $result = $statisticsQuery->execute();
                $statisticsQuery->close();

                if ($result !== false) {
                    $game = '';
                    if ($id == 1) {
                        $game = 'Tetris';
                    } else if ($id == 2) {
                        $game = 'Snake';
                    } else if ($id == 3) {
                        $game = '2048';
                    }

                    $text = "Neuer $game-Highscore auf Philips Tasche: ".$content;

                    $this->sendWhatsAppMessage('4915752755968', $text);
                    $this->sendWhatsAppMessage('4915775980333', $text);
                    $this->sendWhatsAppMessage('4915776417028', $text);
                }

                return $result;
            }

            return false;
        }


        function getComments($type, $id) {
            $type = $this->db->real_escape_string($type);
            $id = $this->db->real_escape_string($id);

            $comments = array();

            if ($commentsQuery = $this->db->prepare("SELECT id, author, text, class FROM comments WHERE type = ? AND item = ?;")) {
                $commentsQuery->bind_param('si', $type, $id);
                $commentsQuery->bind_result($comment_id, $comment_author_id, $comment_text, $comment_class);

                $result = $commentsQuery->execute();
                $commentsQuery->store_result();

                while ($commentsQuery->fetch()) {
                    $comment = array();
                    $comment['id'] = $comment_id;
                    $comment['text'] = utf8_encode($comment_text);
                    $comment['class_'] = utf8_encode($comment_class);
                    $comment['own'] = $_SESSION['id'] == $comment_author_id;

                    array_push($comments, $comment);
                }

                $commentsQuery->close();
            }

            return $comments;
        }

        function submitComment($type, $id, $comment, $class) {
            $type = $this->db->real_escape_string($type);
            $id = $this->db->real_escape_string($id);
            $comment = $this->db->real_escape_string(htmlentities($comment, ENT_QUOTES));
            $class = $this->db->real_escape_string(htmlentities($class, ENT_QUOTES));
            $user_id = $_SESSION['id'];

            if ($commentQuery = $this->db->prepare("INSERT INTO comments (type, item, author, text, class) VALUES (?, ?, ?, ?, ?);")) {
                $commentQuery->bind_param('siiss', $type, $id, $user_id, $comment, $class);

                $result = $commentQuery->execute();

                if ($result) {
                    $this->log('submitComment', $user_id, 'successful', $type.','.$id.','.$class.','.$comment);

                    if (array_key_exists($type, $this->notificationTypes)) {
                      $user = $this->getUser($id);

                      if ($user['notifications']) {
                        $text = $this->notificationTypes[$type].': "'.$comment.'"';
                        $this->sendWhatsAppMessage($user['phone_number'], $text);
                      }
                    }

                    return $commentQuery->insert_id;
                }

                $commentQuery->close();
            }

            $this->log('submitComment', $user_id, 'failed', $type.','.$id.','.$class.','.$comment);

            return false;
        }

        function isCommentOwner($comment_id) {
            $comment_id = $this->db->real_escape_string($comment_id);

            if ($commentQuery = $this->db->prepare("SELECT author FROM comments WHERE id = ?;")) {
                $commentQuery->bind_param('i', $comment_id);
                $commentQuery->bind_result($user_id);

                $result = $commentQuery->execute();

                if ($commentQuery->fetch()) {
                    return $_SESSION['id'] === $user_id;
                }

                $commentQuery->close();
            }

            return false;
        }

        function removeComment($comment_id) {
            $comment_id = $this->db->real_escape_string($comment_id);

            if ($commentQuery = $this->db->prepare("DELETE FROM comments WHERE id = ?;")) {
                $commentQuery->bind_param('i', $comment_id);

                if ($commentQuery->execute()) {
                    $this->log('removeComment', $comment_id, 'successful');

                    return true;
                }

                $commentQuery->close();
            }

            $this->log('removeComment', $comment_id, 'failed');

            return false;
        }



        function getQuotes() {
            $quotes = $this->getComments('quotes', 0);

            foreach ($quotes as &$quote) {
                $quote['text'] = '<b>'.$quote['class_'].':</b> '.$quote['text'];
            }

            return array_reverse($quotes);
        }


        function getRumours() {
            return array_reverse($this->getComments('rumours', 0));
        }


        function getMyths() {
            return array_reverse($this->getComments('myths', 0));
        }


        function sendWhatsAppMessage($phone_number, $text) {
            $whatsAppQuery = $this->db->prepare("INSERT INTO whatsApp_messagesPending (phone_number, text) VALUES (?, ?);");
            $whatsAppQuery->bind_param('ss', $phone_number, $text);

            $result = $whatsAppQuery->execute();
            $whatsAppQuery->close();

            $this->log('sendWhatsAppMessage', null, $phone_number, $text);

            return true;
        }


    }


    $model = new Model();
?>
