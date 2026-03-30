<?php
/*if(! array_key_exists('test', $_GET))
{*/
$__ltsinputcontent = file_get_contents("php://input");
if(! empty($__ltsinputcontent))
{
    $__ltsdata = json_decode($__ltsinputcontent, true);
    if(array_key_exists('gameid', $__ltsdata))
        $gameid = $__ltsdata['gameid'];
    else
        die('Error');
    if(array_key_exists('mesid', $__ltsdata))
        $min_id = $__ltsdata['mesid'];
    else
        die('Error');
    if(array_key_exists('userid', $__ltsdata))
        $gamerid = $__ltsdata['userid'];
    else
        die('Error');
}
else
    die('Error');

include_once 'connect.php';
date_default_timezone_set('Europe/Moscow');

// Database connection
$mysqli = new mysqli($databaseserver, $databaseuser, $databasepassword, $databasename);

// Connection check
if ($mysqli->connect_error)
    die("Error");
// Prepare SQL query
$stmt = $mysqli->prepare("SELECT * FROM `messages` WHERE `parent_games` = ? AND (`id` > ? OR (`type` > 94 AND `type` < 100)) ORDER BY id");
$stmt->bind_param("ii", $gameid, $min_id);
// Execute query
$stmt->execute();
// Get result
$result = $stmt->get_result();
$messages = [];
// Extract data into array
while ($row = $result->fetch_assoc()) {
    switch($row['type'])
    {
    case 95:
        // number of connected users
        $_stmtu = $mysqli->prepare("SELECT COUNT(`id`) AS `kol` FROM `clients` WHERE `parent_games` = ?");
        $ss = 'Connected';
        $type = 11;
        $_stmtu->bind_param("i", $gameid);
        $_stmtu->execute();
        $_result = $_stmtu->get_result();
        if($_row = $_result->fetch_assoc())
            $row['message'] = $_row['kol'];
        else
            $row['message'] = '0';
        $_stmtu->close();
        break;
    case 96:
        // Create DateTime object for end time
        $endGolosTime = new DateTime($row['message']);
        continue;
        break;
    case 97:
        // Get current time
        $now = new DateTime();
        $rest = "0:00";
        // Check if remainder is less than 0
        if ($endGolosTime > $now)
        {
            // Calculate difference
            $interval = $now->diff($endGolosTime);
            // Get hours, minutes and seconds
            $hours = $interval->h;
            $minutes = $interval->i;
            $seconds = $interval->s;
            // Format output
            if ($hours > 0)
                $rest = sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
            else
                $rest = sprintf("%d:%02d", $minutes, $seconds);
        }
        if($row['message'] != $rest)
        {
            $row['message'] = $rest;
            $stmt1 = $mysqli->prepare("UPDATE `messages` SET `message` = ? WHERE `id` = ?");
            $stmt1->bind_param("si", $rest, $row['id']);
            $stmt1->execute();
            $stmt1->close();
            if($rest == '0:00')
            {
                $stmt2 = $mysqli->prepare("INSERT INTO `messages` (`parent_games`, `type`, `message`) VALUES (?, ?, ?)");
                $ss = 'Voting finished';
                $type = 11;
                $stmt2->bind_param("iis", $gameid, $type, $ss);
                $stmt2->execute();
                $stmt2->close();
            }
        }
        break;
    case 98:
        // Create DateTime object for end time
        $endDateTime = new DateTime($row['message']);
        continue;
        break;
    case 99:
        // Get current time
        $now = new DateTime();
        $rest = "0:00";
        // Check if remainder is less than 0
        if ($endDateTime > $now)
        {
            // Calculate difference
            $interval = $now->diff($endDateTime);
            // Get hours, minutes and seconds
            $hours = $interval->h;
            $minutes = $interval->i;
            $seconds = $interval->s;
            // Format output
            if ($hours > 0)
                $rest = sprintf("%d:%02d:%02d", $hours, $minutes, $seconds);
            else
                $rest = sprintf("%d:%02d", $minutes, $seconds);
        }
        if($row['message'] != $rest)
        {
            $row['message'] = $rest;
            $stmt1 = $mysqli->prepare("UPDATE `messages` SET `message` = ? WHERE `id` = ?");
            $stmt1->bind_param("si", $rest, $row['id']);
            $stmt1->execute();
            $stmt1->close();
            if($rest == '0:00')
            {
                $stmt2 = $mysqli->prepare("INSERT INTO `messages` (`parent_games`, `type`, `message`) VALUES (?, ?, ?)");
                $ss = 'Time to start voting!';
                $type = 9;
                $stmt2->bind_param("iis", $gameid, $type, $ss);
                $stmt2->execute();
                $ss = date("Y-m-d H:i:s", strtotime("+1 minute"));
                $type = 96;
                $stmt2->bind_param("iis", $gameid, $type, $ss);
                $stmt2->execute();
                $ss = '0:00';
                $type = 97;
                $stmt2->bind_param("iis", $gameid, $type, $ss);
                $stmt2->execute();
                $stmt2->close();
            }
        }
        break;
    }
    $messages[] = $row;
}
// Close connection
$stmt->close();
$stmt3 = $mysqli->prepare("UPDATE `clients` SET `tik` = NOW() WHERE `id` = ?");
$stmt3->bind_param("i", $gamerid);
$stmt3->execute();
$stmt3->close();
$mysqli->close();
echo count($messages) == 0 ? 'no' : json_encode($messages);
?>