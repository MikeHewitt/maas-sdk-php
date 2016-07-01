<?php
// Autoload for MiraclClient and dependencies
require "./vendor/autoload.php";

// Read config for Miracl client
$config = json_decode(file_get_contents("miracl.json"),true);

// Initialize client
$miracl = new MiraclClient(
    $config['client_id'],
    $config['secret'],
    $config['redirect_uri']);

// Model array for template
$data = array();

if (isset($_REQUEST['logout'])) {
    // Save message in session
    flashMessage("info", "User logged out!");
    $miracl->logout();
    // Send redirect to client page reload
    header("Location: .");
    // End output here
    die();
} else if (isset($_REQUEST['refresh'])) {
    $miracl->refreshUserData();
    // Send redirect to client page reload
    header("Location: .");
    // End output here
    die();
} else {
    // Reload page if authorization happened just now
    if ($miracl->validateAuthorization()) {
        // Save message in session
        flashMessage("success", "Successfully logged in!");
        // Send redirect to client page reload
        header("Location: .");
        // End output here
        die();
    }

    // Populate model for template
    if ($miracl->isLoggedIn()) {
        $data['isAuthorized'] = true;
        $data['email'] = $miracl->getEmail();
        $data['userID'] = $miracl->getUserID();
    } else {
        $data["authURL"] = $miracl->getAuthURL();
    }
}

// Add messages to model and clear messages from session
if (isset($_SESSION["messages"])) {
    $data["messages"] = $_SESSION["messages"];
    $_SESSION["messages"] = array();
}

// Render template from model and output that to client
echo renderTemplate("main", $data);

/**
 * Save message to session for showing it later
 *
 * @param $category string Category of message. Default Bootstrap categories are: success, info, warning, danger
 * @param $message string Message text
 */
function flashMessage($category, $message)
{
    $_SESSION["messages"][] = array(
        "category" => $category,
        "text" => $message
    );
}

/**
 * Render template and return it as string.
 * 
 * @param $name string File name for template
 * @param $vars array Associative array with name-value pairs for template
 * @return string Rendered template
 */
function renderTemplate($name, $vars)
{
    ob_start();
    extract($vars);
    include "templates/$name.php";
    return ob_get_clean();
}