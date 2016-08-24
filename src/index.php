<?php
/**
 * Index file.
 */

// Autoload for MiraclClient and dependencies
require './vendor/autoload.php';
require 'util.php';

// Read config for Miracl client
$config = json_decode(file_get_contents('miracl.json'), true);

// Initialize client
$miracl = new \Com\Miracl\MaasSdk\MiraclClient(
    $config['client_id'],
    $config['secret'],
    $config['redirect_uri'],
    $config['miracl_base_url']
);

// Model array for template
$data = array();

if (isset($_REQUEST['logout'])) {
    // Save message in session
    flashMessage('info', 'User logged out!');
    $miracl->logout();
    // Send redirect to client page reload
    header('Location: .');
    // End output here
    die();
} elseif (isset($_REQUEST['refresh'])) {
    $miracl->refreshUserData();
    // Send redirect to client page reload
    header('Location: .');
    // End output here
    die();
} else {
    // Reload page if authorization happened just now
    if (isset($_REQUEST['code'])) {
        // Validate authorization
        if ($miracl->validateAuthorization()) {
            // Save success message in session
            flashMessage('success', 'Successfully logged in!');
        } else {
            // Save fail message in session
            flashMessage('danger', 'Login failed!');
        }
        // Send redirect to client page reload
        header('Location: .');
        // End output here
        die();
    }
    // Populate model for template
    if ($miracl->isLoggedIn()) {
        $data['isAuthorized'] = true;
        $data['email'] = $miracl->getEmail();
        $data['userID'] = $miracl->getUserID();
    } else {
        $data['authURL'] = $miracl->getAuthURL();
    }
}

// Add messages to model and clear messages from session
if (isset($_SESSION['messages'])) {
    $data['messages'] = $_SESSION['messages'];
    $_SESSION['messages'] = array();
}

// Render template from model and output that to client
echo renderTemplate('main', $data);
