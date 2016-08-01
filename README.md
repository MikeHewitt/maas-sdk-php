# Setup

## Development

This project uses [Composer](http://getcomposer.org) for management of dependencies. You will need `composer.phar` for
installation of dependencies.

Use `php composer.phar install` in the root project directory to download all necessary dependencies into the `./vendor` directory.

# Structure

## MiraclClient

A MiraclClient (`client`) object must be constructed in order to use the SDK. `client` contains internal state and it is not shareable between multiple users.  `client` uses `$_SESSION` to store state information. There should be no output to the client before using any of `client` functions.

### Authorization flow

* The user is presented with the login button. When the user interacts with it, the Miracl system will authenticate the user and perform a redirect to the configured redirect URL
* Authorization is validated by calling `validateAuthorization()`
* User status is acquired by calling `isLoggedIn()`
* Additional user information is requested by calling `getEmail` and `getUserID()`

### MiraclClient methods

#### validateAuthorization

Validates the current authorization. In the case of callback it requests additional data from the Miracl system and caches data in the
session. Returns true if authorization has just happened.

#### isLoggedIn

Checks if authentification information is in the session and returns true if the user is considered to be logged in.

#### getAuthURL

Generates the URL for use in the `mpad.js` script (see [Frontend](#markdown-header-frontend)) and saves verification data in the session.

#### refreshUserData

Refreshes cached user data. Can invalidate logged in status if token is expired.

#### getUserID and getEmail

Returns cached user data. Can be used only when logged in.

#### logout

Removes cached user data from the session.

## Frontend

The authorization flow depends on the `mpad.js` browser library. To show the login button:

* Insert a div with a distinct ID where the login button is to appear
* Use `client.get_auth_url(session)`to generate the authorization URL
* At the end of page body load `mpad.js` with the parameters `data-authurl`
(authorization URL) and `data-element` (login button ID)

```
<script src="https://dd.cdn.mpin.io/mpad/mpad.js" data-authurl="{{ auth_url }}" data-element="btmpin"></script>
```

# Sample app

See `index.php` for sample script and `templates/main.php` for the webpage template.

Credentials-based configuration is found in `miracl.json`.

Replace `CLIENT_ID`, `SECRET` and `REDIRECT_URL` with valid credential values.

To enable use of Miracl APIs, a `MiraclClient` should be initialized. `CLIENT_ID` and `SECRET` can be obtained from
Miracl (they are unique per application). `REDIRECT_URL` is URI of your application end-point that will be responsible for obtaining an access token. It should match what is registered in the Miracl system for the corresponding client ID.

The Redirect URI for this sample is `http://127.0.0.1` if it is accessed on the local web server root.

# Tests

Tests use PHPUnit. To run tests, use:
```
wget https://phar.phpunit.de/phpunit.phar
php phpunit.phar --bootstrap vendor/autoload.php tests/MiraclClientTest
```
