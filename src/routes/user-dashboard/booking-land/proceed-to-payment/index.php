<?php
session_start();
if (!isset($_GET['land_id'])) {
    $_SESSION['redirect_url'] = "http" .
        (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 's' : '') .
        "://" . $_SERVER['HTTP_HOST'] .
        $_SERVER['REQUEST_URI'];
    header('Location: ../../../../static/error/HTTP404.html');
    die();
}
$land_id = $_GET['land_id'];

include "../../../../utility/php/connection.php";
$connection = connection();
if (!$connection) {
    header('Location: ../../../static/error/HTTP521.html');
    die();
}

if (isset($_POST["sign_out"])) {
    $delete_token_sql = "UPDATE login SET token = NULL WHERE user_nid = " . $_SESSION['id'] . ";";
    $delete_token = mysqli_query($connection, $delete_token_sql);
    session_destroy();
    header("Location: ../../../../");
}

$get_land_information = "SELECT * FROM land 
                        JOIN owns o on land.land_id = o.land_id
                        JOIN land_cost_info lci on land.land_id = lci.land_id
                        WHERE land.land_id = " . $land_id . ";";
$get_land_information_result = mysqli_query($connection, $get_land_information);
$land = mysqli_fetch_assoc($get_land_information_result);

$cost_per_sqft = $land["cost_per_sqft"];
$land_area = $land["area"];
$total_cost = $cost_per_sqft * $land_area;
$landsphere_income = $total_cost * 0.05;
$service_charge = 1600;
$tax = $total_cost * 0.15;
$registration_charge = 2100;
$verification_charge = 300;
$document_charge = 120;
$grand_total = $total_cost + $landsphere_income + $service_charge + $tax + $registration_charge + $verification_charge + $document_charge;
$grand_total = number_format($grand_total, 2);

$fetch_owner_name_sql = "SELECT * FROM user WHERE nid = " . $land["owner_id"] . ";";
$fetch_owner_name = mysqli_query($connection, $fetch_owner_name_sql);
$owner_name_row = mysqli_fetch_assoc($fetch_owner_name);
$owner_name = $owner_name_row["full_name"];
$owner_email = $owner_name_row["email"];

$fetch_buyer_name_sql = "SELECT * FROM user WHERE nid = " . $_SESSION["id"] . ";";
$fetch_buyer_name = mysqli_query($connection, $fetch_buyer_name_sql);
$buyer_name_row = mysqli_fetch_assoc($fetch_buyer_name);
$buyer_name = $buyer_name_row["full_name"];
$buyer_email = $buyer_name_row["email"];

$fetch_owner_payment_information_sql = "SELECT * FROM payment_method WHERE user_id = " . $_SESSION['id'] . ";";
$fetch_owner_payment_information = mysqli_query($connection, $fetch_owner_payment_information_sql);
$owner_payment_information = mysqli_fetch_assoc($fetch_owner_payment_information);
$card_number = $owner_payment_information["card_number"];
$card_holder_name = $owner_payment_information["name_on_card"];
$expiry_date = $owner_payment_information["expire_date"];
$cvc = $owner_payment_information["cvc"];
$billing_address = $owner_payment_information["billing_address"];

// divide card number into 4 parts, with hyphen in between, 4 digits each
$card_number = substr($card_number, 0, 4) . "-" . substr($card_number, 4, 4) . "-" . substr($card_number, 8, 4) . "-" . substr($card_number, 12, 4);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../../../../../dist/output.css" rel="stylesheet">
    <link rel="icon" href="../../../../resource/ico.svg">
    <title>LandSphere | Your Personal Land Manager</title>
</head>

<body class="bg-beige-default">
<nav id="navbar"
     class="bg-beige-dark flex gap-6 justify-between pl-24
    pr-24 pt-4 pb-4 rounded-b-2xl fixed w-full bg-opacity-60
    backdrop-blur-lg items-center top-0 mb-12 z-50">
    <div class="flex gap-5 items-center">

        <a href="../../../../index.php" class="flex select-none">
            <img alt="" src="../../../../resource/icons/landSphere.svg">
        </a>

        <div class="flex gap-2 items-center">
            <a href="../../../about-us"
               class="hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-6 pr-6 transition-colors">
                About</a>
            <a href="../../../projects"
               class="transition-colors hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-6 pr-6">
                Projects</a>
            <a href="../../../on-sale"
               class="transition-colors hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-6 pr-6">
                On Sale
            </a>
            <a href="../../../news"
               class="transition-colors hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-6 pr-6">
                News</a>
            <a href="../../../contact-us"
               class="transition-colors hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-6 pr-6">
                Contact</a>
        </div>
    </div>

    <button id="search_button" type="button" data-modal-target="defaultModal" data-modal-toggle="defaultModal"
            class="transition-colors hover:bg-beige-darkest rounded-3xl pt-[0.60rem] pb-[0.60rem] pl-3 pr-3
                    flex gap-12 items-center">
        <span class="flex items-center gap-2">
            <img src="../../../../resource/icons/search-navbar.svg" alt=" ">
            <span class="text-xs font-medium text-gray-800">Search</span>
        </span>
        <span class="flex gap-1 select-none">
            <kbd id="keyboard_shortcut" class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100
                rounded-lg">⌘</kbd><kbd class="px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100
                rounded-lg">K</kbd>
        </span>
    </button>

    <?php
    $full_name = $_SESSION["name"];
    // count how many words in the name
    $name_count = str_word_count($full_name);
    // if the name has more than one word
    if ($name_count > 1) {
        $first_name = explode(" ", $_SESSION["name"])[0];
        $last_name = explode(" ", $_SESSION["name"])[1];
    } else {
        $first_name = $_SESSION["name"];
        $last_name = "";
    }
    $email = $_SESSION["email"];

    $rnd = rand(0, 1000000);
    $loggedIn = <<<HTML
    <div class="flex gap-6 items-center">
        <button id="dropdownAvatarNameButton" data-dropdown-toggle="dropdownAvatarName"
                class="flex items-center text-sm font-bold text-gray-900 rounded-full"
                type="button">
            <span class="sr-only">Open user menu</span>
            <img class="w-8 h-8 mr-2 rounded-full"
                 src="https://api.dicebear.com/6.x/avataaars/svg?seed=$rnd%20Hill&backgroundColor=b6e3f4,c0aede,d1d4f9"
                 alt="user photo" height="32px" width="32px">
                    {$_SESSION["name"]}
            <svg class="w-4 h-4 mx-1.5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                 xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd"
                      d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                      clip-rule="evenodd"></path>
            </svg>
        </button>

        <!-- Dropdown menu -->
<div id="dropdownAvatarName"
             class="z-10 hidden bg-white divide-y divide-gray-100 rounded-2xl shadow-2xl w-64">
            <div class="px-4 py-3 text-lg text-gray-900 bg-beige-dark rounded-t-2xl">
                <div class="font-semibold">
                        $first_name <span class="text-green-600">$last_name</span>
                </div>
                <div class="truncate text-sm font-mono font-bold text-gray-500">
                    {$_SESSION["email"]}
                </div>
            </div>
            <ul class="py-2 text-sm text-gray-700"
                aria-labelledby="dropdownInformdropdownAvatarNameButtonationButton">
                
                <li>
                   <a href="../../owned-land" class="flex px-4 py-2 hover:bg-gray-100 gap-3 w-full items-center">
                        <span class="font-bold pl-1">Owned Lands</span>
                    </a>
                </li>
                
                <li>
                    <a href="../../sale-list" class="flex px-4 py-2 hover:bg-gray-100 gap-3 w-full items-center">
                        <span class="font-bold pl-1">Sale List</span>
                    </a>
                </li>

                <li>
                    <a href="../../successors" class="flex px-4 py-2 hover:bg-gray-100 gap-3 w-full items-center">
                        <span class="font-bold pl-1">Successor</span>
                    </a>
                </li>
                
                <li>
                    <a href="../../payment-list" class="flex px-4 py-2 hover:bg-gray-100 gap-3 w-full items-center">
                        <span class="font-bold pl-1">Payment</span>
                    </a>
                </li>
                
                <li>
                    <a href="../../booking-land" class="flex mb-2 px-4 py-2 hover:bg-gray-100 gap-3 w-full items-center">
                        <span class="font-bold pl-1">Bookings</span>
                    </a>
                </li>
                <hr class="w-full h-1 mx-auto my-1 bg-gray-300 border-0 rounded-full">
                
                <li>
                    <a href="#" class="flex px-4 py-2 hover:bg-gray-100 gap-2 w-full items-center">
                        <span>
                            <img src="../../../../resource/icons/dashboard/settings.svg" alt="">
                        </span>
                        <span class="font-medium text-primary">Landsphere</span><span>Settings</span>
                    </a>
                </li>
                <hr>
                <li>
                    <a href="../../account-settings" class="flex px-4 py-2 hover:bg-gray-100 gap-2 w-full items-center">
                        <span>
                            <img src="../../../../resource/icons/dashboard/account.svg" alt="">
                        </span>
                        <span>Manage your Account</span>
                    </a>
                </li>
                <hr>
                <li>
                    <form method="post" action="" class="flex px-4 mb-1.5 py-2 hover:bg-gray-100 gap-2 w-full items-center">
                        <button name="sign_out" class="w-full flex gap-2 items-center text-red-600 rounded-2xl">
                            <span>
                                <img src="../../../../resource/icons/dashboard/cancel.svg" alt="">
                            </span>
                            Sign out
                        </button>
                    </form>
                </li>
            </ul>

        </div>
HTML;
    echo $loggedIn;
    ?>


</nav>
<div id="breadcrumb"
     class="group fixed w-full top-0 mt-24 flex justify-center z-50">
    <div class="flex px-5 py-2 bg-beige-dark rounded-3xl shadow-md
    justify-center group-hover:shadow-lg transition-all duration-300"
         aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1">
            <li class="inline-flex items-center">
                <a href="../../../../index.php"
                   class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
                    <svg aria-hidden="true" class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"
                         xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                    </svg>
                    Home
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                         xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                              clip-rule="evenodd"></path>
                    </svg>
                    <a href="../index.php"
                       class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-green-600">
                        Your Bookings
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20"
                         xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                              clip-rule="evenodd"></path>
                    </svg>
                    <a href="#"
                       class="ml-1 text-sm font-medium text-gray-400 group-hover:text-gray-800 md:ml-2">
                        Payment of <span class="text-primary"><?php echo $land['title'] ?></span>
                    </a>
                </div>
            </li>
        </ol>
    </div>

</div>

<section id="main-section" class="container mx-auto my-auto mt-48 mb-16 pl-36 pr-36">
    <main class="flex flex-col gap-2 items-center justify-center">
        <h1 class="text-3xl font-bold text-center text-gray-700">
            Payment Process of
        </h1>
        <h1 class=" font-bold text-center text-4xl text-primary">
            <?php echo $land['title'] ?>
        </h1>
        <div class="bg-beige-darkest text-zinc-600 w-fit font-mono align-middle p-1 rounded-xl font-sm px-3">
            <?php echo $land['land_id'] ?>
        </div>
        <p class="p-1 rounded-xl px-3 text-zinc-400 font-bold font-mono">
            <?php echo $land['address'] ?>
        </p>
    </main>

    <main class="mt-12 grid grid-cols-2 gap-4 ">
        <div class="flex-col flex gap-3 pb-4">
            <div class="rounded-2xl pt-6 transition-all duration-300 flex-col flex gap-4">
                <div class="rounded-full flex gap-2 items-center justify-between
                    bg-beige-dark hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center">
                        <?php
                        $random = rand(1, 1000);
                        echo <<< HTML
                        <img class="w-16 h-16 rounded-full"
                             src="https://api.dicebear.com/6.x/avataaars/svg?seed=$random%20Hill&backgroundColor=b6e3f4,c0aede,d1d4f9"
                             alt="">
                        HTML;
                        ?>

                        <div class="flex-col items-end ">
                            <h1 class="text-lg font-semibold  pl-4 "><?php echo $owner_name ?></h1>
                            <h1 class="font-semibold text-zinc-500 pl-4 "><?php echo $owner_email ?></h1>
                        </div>
                    </div>
                    <h1 class="font-semibold text-zinc-500 pr-8">Owner</h1>
                </div>

                <div class="rounded-full flex gap-2 items-center justify-between
                    bg-beige-dark hover:shadow-lg transition-all duration-300">
                    <div class="flex items-center">
                        <?php
                        $random = rand(1, 1000);
                        echo <<< HTML
                        <img class="w-16 h-16 rounded-full"
                             src="https://api.dicebear.com/6.x/avataaars/svg?seed=$random%20Hill&backgroundColor=b6e3f4,c0aede,d1d4f9"
                             alt="">
                        HTML;
                        ?>
                        <div class="flex-col items-end ">
                            <h1 class="text-lg font-semibold  pl-4 "><?php echo $buyer_name ?>
                            </h1>
                            <h1 class="font-semibold text-zinc-500 pl-4 "><?php echo $buyer_email ?>
                            </h1>
                        </div>
                    </div>
                    <h1 class="font-semibold text-zinc-500 pr-8">Buyer</h1>
                </div>
            </div>

            <h1 class="pl-4 pt-3 text-lg text-gray-500 font-bold">
                Select Installment Plan and Deadline
            </h1>

            <form method="post" action="" class="flex flex-col h-full justify-between gap-2">

                <!-- action="../../../../utility/php/confirm_payment.php" -->


                <div class="flex justify-between items-center">
                    <div class="flex flex-col gap-1">
                        <label for="installment" class="text-sm font-bold pl-4">Select Installment Period</label>
                        <label for="installment" class="text-sm text-gray-500 pl-4 ">(Select 0 for no installment
                            plan)</label>
                    </div>
                    <input type="number" name="installment" id="installment"
                           min="0" max="36" value="0"
                           placeholder="Email address or Phone Number"
                           class="rounded-xl text-right w-24
                           bg-white py-3 px-6 text-base font-medium text-[#6B7280]
                           outline-none focus:shadow-md font-mono mr-4"
                    />
                </div>

                <div class="flex justify-between items-center pb-3">
                    <div class="flex flex-col gap-1">
                        <label for="deadline" class="text-sm font-bold pl-4">Deadline</label>
                    </div>
                    <input type="date" name="deadline" id="deadline"
                           class="rounded-xl text-right
                           bg-white py-3 px-6 text-base font-medium text-[#6B7280]
                           outline-none focus:shadow-md font-mono mr-4"
                    />
                </div>

                <div
                    class="group hover:shadow-lg flex-col gap-1 p-4 bg-zinc-800 text-white rounded-2xl font-mono tracking-widest">
                    <div class="flex justify-between">
                        <h1 class="font-bold">Card Number</h1>
                        <p><?php echo $card_number ?></p>
                    </div>
                    <div class="flex justify-between">
                        <h1 class="font-bold">Expiry Date</h1>
                        <p><?php echo $expiry_date ?></p>
                    </div>
                    <div class="flex justify-between">
                        <h1 class="font-bold">CVC</h1>
                        <p><?php echo $cvc ?></p>
                    </div>
                    <div class="flex justify-between">
                        <h1 class="font-bold">Cardholder Name</h1>
                        <p><?php echo $card_holder_name ?></p>
                    </div>
                    <div class="flex justify-between">
                        <h1 class="font-bold">Billing Address</h1>
                        <p><?php echo $billing_address ?></p>
                    </div>
                </div>

                <button name="submit" type="submit"
                        class="hover:shadow-form bg-green-700
                        py-3 px-8 text-center text-base
                        font-bold text-white outline-none items-center
                        col-span-2 rounded-full hover:bg-green-800
                        hover:shadow-lg">
                    Confirm Payment
                </button>


            </form>
        </div>
        <div class="m-6 flex-col items-center align-middle my-auto">
            <div
                class="bg-beige-dark rounded-2xl p-6 shadow-md hover:shadow-xl transition-all duration-300 font-mono flex-col gap-2">
                <h1 class=" text-end pb-3 text-xl font-bold">
                    Payment Summary
                    <hr class="border-4 border-gray-400 rounded-full my-2">
                </h1>

                <div class="flex justify-between">
                    <p>Cost Per SQFT</p>
                    <p class="font-bold text-zinc-600 tracking-widest">$<?php echo $land['cost_per_sqft'] ?></p>
                </div>

                <div class="flex justify-between">
                    <p>Total Area</p>
                    <p class="font-bold text-zinc-600 tracking-widest">
                        SQFT <?php echo $land['area'] ?>
                    </p>
                </div>
                <hr class="border-2 border-gray-300 rounded-full my-2">

                <div class="flex justify-between">
                    <p>Price of Land</p>
                </div>

                <div class="flex justify-between">
                    <p>Cost Per SQFT * Total Area</p>
                    <p class="font-bold tracking-widest">$<?php echo $total_cost ?></p>
                </div>

                <hr class="border-2 border-gray-300 rounded-full my-2">

                <div class="flex justify-between">
                    <p>5% LandSphere Charge</p>
                    <p class="font-bold tracking-widest">$<?php echo $landsphere_income ?></p>
                </div>

                <hr class="border-2 border-gray-300 rounded-full my-2">
                <div class="flex justify-between">
                    <p>15% TAX</p>
                    <p class="font-bold tracking-widest">$<?php echo $tax ?></p>
                </div>

                <div class="flex justify-between">
                    <p>Registration Charge</p>
                    <p class="font-bold tracking-widest">$<?php echo $registration_charge ?></p>
                </div>

                <div class="flex justify-between">
                    <p>Verification Charge</p>
                    <p class="font-bold tracking-widest">$<?php echo $verification_charge ?></p>
                </div>

                <div class="flex justify-between">
                    <p>Document Charge</p>
                    <p class="font-bold tracking-widest">$<?php echo $document_charge ?></p>
                </div>

                <hr class="border-4 border-gray-400 rounded-full my-2">
                <div class="flex justify-between">
                    <p class="font-black text-lg">Grand Total</p>
                    <p class="font-bold text-lg tracking-widest text-primary"> $<?php echo $grand_total ?></p>
                </div>


            </div>
            <p class=" text-center w-full mt-4 text-sm font-mono text-zinc-500"> Percentages are calculated on the
                total
                cost of land
            </p>
        </div>
    </main>
</section>

<footer id="index_footer"
        class="container mx-auto my-auto mb-12 bg-green-900 rounded-xl pl-24 pr-24 pt-12
                                 pb-12 drop-shadow-xl">

    <div class="grid grid-cols-4 text-white gap-x-12 gap-y-3">
        <div class="flex flex-col">
            <h1 class="font-black pb-3 text-xl">
                For Land Owners
            </h1>
            <div class=" flex flex-col gap-2">
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Community </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Rules and Regulations </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Volunteers </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Option </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Opt Out </a>


            </div>
            <a></a>
        </div>

        <div class="flex flex-col">
            <h1 class="font-black pb-3 text-xl">
                For Visitors
            </h1>
            <div class=" flex flex-col gap-2">
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Guides </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Office Locations </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Benefits </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> History </a>
            </div>
        </div>

        <div class="flex flex-col">
            <h1 class="font-black pb-3 text-xl">
                Resources
            </h1>
            <div class=" flex flex-col gap-2">
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Help and Support </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Blog </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Careers </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> News Archive </a>
            </div>
        </div>

        <div class="flex flex-col">
            <h1 class="font-black pb-3 text-xl">
                Company
            </h1>
            <div class=" flex flex-col gap-2">
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> About Us </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Leadership </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Careers </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Press </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Trust, Safety &
                    Security </a>
            </div>
        </div>

        <div class="col-span-4 pt-3 flex gap-4 items-center">
            <h1 class="text-lg font-bold"> Follow us </h1>
            <a href="../../../../static/error/HTTP501.html">
                <img src="../../../../resource/icons/footer/icon-facebook.svg" alt="">
            </a>
            <a href="../../../../static/error/HTTP501.html">
                <img src="../../../../resource/icons/footer/icon-twitter.svg" alt="">
            </a>
            <a href="../../../../static/error/HTTP501.html">
                <img src="../../../../resource/icons/footer/icon-linkedin.svg" alt="">
            </a>
            <a href="../../../../static/error/HTTP501.html">
                <img src="../../../../resource/icons/footer/icon-youtube.svg" alt="">
            </a>
        </div>

        <hr class="col-span-4">

        <div class="col-span-4 flex align-middle items-center justify-between pt-3">
            <h1 class="font-bold"> &copy; 2023 <a href="#" class="text-green-400">LandSphere </a> Inc.</h1>
            <div class="flex gap-6 pt-1">
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Terms of Service </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Privacy Policy </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Cookie Settings </a>
                <a href="../../../../static/error/HTTP501.html" class="hover:text-green-300"> Accessibility </a>
            </div>

        </div>

    </div>

</footer>

<div id="defaultModal"
     tabindex="-1"
     aria-hidden="true"
     class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto
     md:inset-0 h-[calc(100%-1rem)] md:h-full bg-opacity-60 bg-beige-light
    backdrop-blur-md transition-all">


    <div class="relative w-full h-full max-w-2xl md:h-auto">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow">
            <!-- Modal header -->
            <div class="flex justify-between p-4 border-b rounded-t items-center">
                <img src="../../../../resource/icons/modal-search-icon.svg" alt="">
                <input type="text"
                       name="search_box"
                       id="search_text-field"
                       placeholder="Type anything to search"
                       class="w-full rounded-md
                               bg-white px-3 text-base font-medium text-[#6B7280]
                               outline-none"
                />
                <label for="search_text-field"></label>
                <button type="button"
                        class="text-gray-400 bg-transparent rounded-lg text-sm p-1.5 ml-auto inline-flex items-center"
                        data-modal-hide="defaultModal">
                    <kbd class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100
                rounded-lg">Esc</kbd>
                </button>
            </div>
            <!-- Modal body -->
            <div class="p-6 space-y-6">

            </div>
            <!-- Modal footer -->
            <div class="flex items-center p-6 space-x-2 border-t border-gray-200 rounded-b">
            </div>
        </div>
    </div>

</div>
</body>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.js"></script>
<script>
    document.addEventListener('keydown', function (event) {
        if (event.metaKey && event.keyCode === 75) {
            document.getElementById('search_button').click();
        }
        if (event.ctrlKey && event.keyCode === 75) {
            document.getElementById('search_button').click();
        }
    });

    const os = navigator.platform;
    if (os === "Win32" || os === "Win64" || os === "Windows" || os === "WinCE") {
        document.getElementById('keyboard_shortcut').innerHTML = "Ctrl";
    }
</script>
</html>
