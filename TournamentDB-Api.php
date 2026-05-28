<?php
header('Content-type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

define('JWT_SECRET', 'change_this_secret');
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'change_this_password');

$method = $_SERVER['REQUEST_METHOD'];

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function generate_jwt() {
    $header = base64url_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64url_encode(json_encode(['exp' => time() + 3600]));
    $sig = base64url_encode(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
    return "$header.$payload.$sig";
}

function verify_jwt($token) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return false;
    [$h, $p, $s] = $parts;
    $expected = base64url_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return false;
    $data = json_decode(base64_decode(strtr($p, '-_', '+/')), true);
    return $data['exp'] >= time();
}

try
{
    $db = new PDO('mysql:host=127.0.0.1;dbname=tournament', 'api', 'Qwerty4!api');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $e)
{
    echo json_encode(["message" => "Connection failed: " . $e->getMessage()]);
    exit();
}

switch ($method) {

    case 'POST':

        $in_data = file_get_contents("php://input");
        $arr = json_decode($in_data, true);

        if (isset($arr['action']) && $arr['action'] === 'login') {
            if ($arr['username'] === ADMIN_USERNAME && $arr['password'] === ADMIN_PASSWORD) {
                echo json_encode(["token" => generate_jwt()]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Invalid credentials"]);
            }
            exit();
        }

        $query = $db->prepare("INSERT INTO teams ( name) VALUES (:name)");

        $CheckEmailStmt = $db->prepare("SELECT COUNT(*) FROM participants WHERE email = :email");
        $CheckNicknameStmt = $db->prepare("SELECT COUNT(*) FROM participants WHERE nickname = :nickname");
        $CheckTeamNameStmt = $db->prepare("SELECT COUNT(*) FROM teams WHERE name = :name");

        $CheckTeamNameStmt->execute([':name' => $arr['team_name']]);

        if ($CheckTeamNameStmt->fetchColumn() > 0) {
            echo json_encode(["error" => 1, "message" => "Team name '" . $arr['team_name'] . "' is already taken!"]);
            exit();
        }

        foreach ($arr['participants'] as $participant) {
            $CheckEmailStmt->execute([':email' => $participant['email']]);
            $CheckNicknameStmt->execute([':nickname' => $participant['nickname']]);

            if ($CheckEmailStmt->fetchColumn() > 0) {
                echo json_encode(["error" => 1, "message" => $participant['email']]);
                exit();
            }

            if ($CheckNicknameStmt->fetchColumn() > 0) {
                echo json_encode(["error" => 1, "message" => "Nickname already exists: " . $participant['nickname']]);
                exit();
            }
        }

        $query->execute([
            ':name' => $arr['team_name']
        ]);

        $new_id = $db->lastInsertId();

        $stmt = $db->prepare("INSERT INTO participants (nickname, email, team_id) VALUES (:nickname, :email, :team_id)");

        foreach ($arr['participants'] as $participant) {
            $stmt->execute([
                ':nickname' => $participant['nickname'],
                ':email' => $participant['email'],
                ':team_id' => $new_id
            ]);
        }

        echo json_encode(["error" => 0, "message" => "Participants and team added successfully"]);
        break;

    case 'DELETE':

        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $auth);

        if (!verify_jwt($token)) {
            http_response_code(401);
            echo json_encode(["message" => "Unauthorized"]);
            exit();
        }

        $del_data = file_get_contents("php://input");
        $arr = json_decode($del_data, true);

        $stmt = $db->prepare("DELETE FROM participants WHERE team_id = :id; DELETE FROM teams WHERE id = :id");
        $query = $db->prepare("SELECT team_id FROM participants WHERE id = :id");

        $query->execute([
            ':id' => $arr['id']
        ]);

        $team_id = $query->fetchColumn();

        if (!$team_id) {
            echo json_encode(["message" => "Participant not found"]);
            exit();
        } else {
            $stmt->execute([
                ':id' => $team_id
            ]);
        }

        echo json_encode(["message" => "Participants and team deleted successfully"]);
        break;

    default:

        echo json_encode(["message" => "Method not supported"]);
        break;
}
?>
