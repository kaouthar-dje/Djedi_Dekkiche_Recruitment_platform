<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include 'config.php';

$sql = "SELECT 
            job_id AS id,
            job_id,
            title,
            department,
            numberofpositions,
            minexperience,
            enddate
        FROM joboffer";
$stmt = $conn->prepare($sql);
$stmt->execute();
$joboffer = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($joboffer);
?>
