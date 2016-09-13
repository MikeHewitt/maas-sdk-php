<?php
/**
 * Utility functions.
 */

/**
 * Save message to session for showing it later
 *
 * @param string $category Category of message.
 *                         Default Bootstrap categories are: success, info, warning, danger
 * @param string $message  Message text
 */
function flashMessage($category, $message)
{
    $_SESSION['messages'][] = array(
        'category' => $category,
        'text' => $message
    );
}

/**
 * Render template and return it as string.
 *
 * @param string $name File name for template
 * @param array  $vars Associative array with name-value pairs for template
 *
 * @return string Rendered template
 */
function renderTemplate($name, $vars)
{
    ob_start();
    extract($vars);
    include '../templates/'.$name.'.php';
    return ob_get_clean();
}
