<?php
session_start();
include "../../utility/php/connection.php";
$connection = connection();
if (!$connection) {
    header('Location: ../../static/error/HTTP521.html');
    die();
}

$land_id = $_GET["land_id"];

$get_land_information = "SELECT * FROM land 
                        JOIN owns o on land.land_id = o.land_id
                        JOIN land_cost_info lci on land.land_id = lci.land_id
                        WHERE land.land_id = " . $land_id . ";";

$land_information = mysqli_query($connection, $get_land_information);
$land = mysqli_fetch_assoc($land_information);

$land_owner_id = $land["owner_id"];
$cost_per_sqft = $land["cost_per_sqft"];
$land_area = $land["area"];
$total_cost = $cost_per_sqft * $land_area;

if ($_SESSION['id'] == $land["owner_id"]) {
    $sql = "INSERT INTO sell_list (land_id, user_id, total_amount) VALUES (" . $land_id . ", " . $land_owner_id . ", " . $total_cost . ");";
    if (mysqli_query($connection, $sql)) {
        header("Location: ../../routes/user-dashboard/sale-list/");
    } else {
        echo "Error" . mysqli_error($connection);
    }

}