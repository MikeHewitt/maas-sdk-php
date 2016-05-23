<?php

require "./vendor/autoload.php";

$miracl = new MiraclClient(
    'CLIENT_ID',
    'CLIENT_SECRET',
    'REDIRECT_URL');

//Display data for tempalte
$data = array();

if (isset($_REQUEST['logout'])) {
    flashMessage("info", "User logged out!");
    $miracl->logout();
    header("Location: .");
    die();
} else {
    if ($miracl->validateAuthorization()) {
        //Redirect if authorization happened just now
        flashMessage("success", "Successfully logged in!");
        header("Location: .");
        die();
    }

    if ($miracl->isLoggedIn()) {
        $data['isAuthorized'] = true;
        $data['email'] = $miracl->getEmail();
        $data['userID'] = $miracl->getEmail();
    } else {
        $data["authURL"] = $miracl->getAuthURL();
    }
}

//Show user messages saved in session
if (isset($_SESSION["messages"])) {
    $data["messages"] = $_SESSION["messages"];
    $_SESSION["messages"] = array();
}

echo renderTemplate("main", $data);

//save message to session for showing it later
function flashMessage($category, $message)
{
    $_SESSION["messages"][] = array(
        "category" => $category,
        "text" => $message
    );
}

function renderTemplate($name, $vars)
{

    ob_start();
    extract($vars);
    include "templates/$name.php";
    return ob_get_clean();
}