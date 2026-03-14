<?php
header('Content-type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

$method = $_SERVER['REQUEST_METHOD'];

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
                echo json_encode(["error" => 1, "message" => "Email already exists: " . $participant['email']]);
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