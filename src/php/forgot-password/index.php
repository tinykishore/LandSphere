<?php
session_start();

if (isset($_SESSION["id"])) {
    header("Location: ./reset-password");
}

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_NAME = 'cse3522';
$DB_PASS = '';

$connection = mysqli_connect($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if (!$connection) {
    die("Connection failed: " . mysqli_connect_error());
}

$account_not_found = false;
$input_error = false;

if (isset($_POST['submit'])) {
    $key = $_POST['key'];
    $nid = $_POST['nid'];

    if (empty($key) || empty($nid)) {
        $input_error = true;
    } else {
        if (isEmail($key) || isPhone($key) || isNID($key)) {
            $key = mysqli_real_escape_string($connection, $key);
            $nid = mysqli_real_escape_string($connection, $nid);

            $sql = "SELECT * FROM user WHERE (email = '$key' OR phone_number = '$key' OR nid = '$key') AND nid = '$nid'";
            $result = mysqli_query($connection, $sql);
            $row = mysqli_fetch_array($result);

            if (is_array($row)) {
                $_SESSION["id"] = $row['nid'];
                $_SESSION["email"] = $row['email'];
                $_SESSION["name"] = $row['_name_'];
                header("Location: ./reset-password");
            } else {
                $account_not_found = true;
            }
        } else {
            $input_error = true;

        }
    }
}

function isEmail($keyword): bool
{
    return filter_var($keyword, FILTER_VALIDATE_EMAIL);
}

function isPhone($keyword): bool
{
    return preg_match('/^[0-9]{11}$/', $keyword);
}

function isNID($keyword): bool
{
    return preg_match('/^[0-9]{10}$/', $keyword);
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../dist/output.css" rel="stylesheet">
    <title>Sign In</title>
</head>

<body class="bg-sign-in-background-light bg-cover filter dark:bg-sign-in-background-dark">

<div class="flex items-center justify-center h-screen">

    <div class="mx-auto w-[550px] p-12 flex flex-col justify-between rounded-xl
    bg-opacity-60 backdrop-blur-md border border-gray-400 bg-white shadow-2xl
    animate-fadeIn overflow-y-auto dark:bg-black dark:bg-opacity-30
    dark:border-gray-800">

        <h2 class="align-middle pb-12 text-center font-black text-2xl dark:text-white select-none">
            Okay, Let's find your account
        </h2>

        <form action="" method="POST">
            <div class="mb-5">
                <div class="mb-5">
                    <input type="text" name="key" id="key"
                           placeholder="Search By Email or Phone Number"
                           class="w-full rounded-md border border-[#e0e0e0]
                           bg-white py-3 px-6 text-base font-medium text-[#6B7280]
                           outline-none focus:border-[#6A64F1] focus:shadow-md
                           dark:border-gray-900 dark:bg-[#393939] dark:text-white dark:placeholder-gray-400"
                            <?php if ($account_not_found || $input_error) {
                                 echo "value='$key'";
                            } ?>
                    />
                    <label for="key" class="text-sm">
                </div>
                <div class="mb-5">
                    <input type="text" name="nid" id="nid"
                           placeholder="Provide NID Number"
                           class="w-full rounded-md border border-[#e0e0e0]
                           bg-white py-3 px-6 text-base font-medium text-[#6B7280]
                           outline-none focus:border-[#6A64F1] focus:shadow-md
                           dark:border-gray-900 dark:bg-[#393939] dark:text-white dark:placeholder-gray-400"
                           <?php if ($account_not_found || $input_error) {
                               echo "value='$nid'";
                           } ?>
                    />
                    <label for="nid" class="text-sm">
                        <?php if ($account_not_found) {
                            echo "<div class='mt-2 text-red-600 text-center select-none dark:text-red-300'>Account not found, please contact for support</div>";
                        } ?>

                        <?php if ($input_error) {
                            echo "<div class='mt-2 text-red-600 text-center select-none dark:text-red-300'>Please provide all fields</div>";
                        } ?>

                </div>
            </div>

            <p class="text-xs text-gray-500 text-center dark:text-gray-300 select-none">
                An authentication code will be sent to your email address or phone number <br>
                You will need to enter the code to reset your password
            </p>

            <div class="flex flex-col items-center justify-center gap-2 align-middle mt-12">
                <div class="flex items-center justify-center gap-24">
                    <button name="submit"
                            class="hover:shadow-form rounded-md bg-primary py-3 px-8
                            text-center text-base font-semibold text-white outline-none
                            items-center select-none">
                        Search
                    </button>
                </div>
            </div>
        </form>

    </div>
</div>


</body>
</html>
