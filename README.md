# Setup

## Development

Project uses [Composer](http://getcomposer.org) for management of dependencies. You will need `composer.phar` for
installation of dependencies.

Use:
`php composer.phar install` in project directory to download all necessary dependencies into `./vendor` directory.

# Structure

## MiraclClient

MiraclClient (`client`) object must be constructed to use SDK. `client` contains internal state and it is not shareable
 between multiple users.  `client` uses `$_SESSION` to store state information. No output should be done to client
 before using any of `client` functions.

### Authorization flow

* User is presented with login button. When user interacts with it, Miracl system will authenticate user and do redirect
to configured redirect URL
* Authorization is validated by calling `validateAuthorization()`
* User status is acquired by calling `isLoggedIn()`
  * Additional user information is requested by calling `getEmail` and `getUserID()`

### MiraclClient methods

#### validateAuthorization

Validates current authorization. In case of callback it requests additional data from Miracl system and caches data in
session. Returns true if authorization happened just now.

#### isLoggedIn

Checks if authentification information is in session and returns true if user can be considered logged in.

#### getAuthURL

Generates URL for use in `mpad.js` script (see [Frontend](#markdown-header-frontend)) and saves verification data in
session.

#### refreshUserData

Refreshes cached user data. Can invalidate logged in status if token is expired.

#### getUserID and getEmail

Returns cached user data. Can be used only when logged in.

#### logout

Removes cached user data from session.

## Frontend

Authorization flow depends on `mpad.js` browser library. To show login button:

* Put div with distinct ID where login button should be
* Create authorization URL by using
`client.get_auth_url(session)`
* At the end of page body load `mpad.js` with parameters `data-authurl`
(authorization URL) and `data-element` (login button ID)

```
<script src="https://demo.dev.miracl.net/mpin/mpad.js" data-authurl="{{ auth_url }}" data-element="btmpin"></script>
```

# Sample

See `index.php` for sample and `templates/main.php` for used template.
Replace `CLIENT_ID`, `CLIENT_SECRET` and `REDIRECT_URL` with valid values.
To start using Miracl API, `MiraclClient` should be initialized. `CLIENT_ID` and `CLIENT_SECRET` can be obtained from
Miracl(unique per application). `REDIRECT_URL` is URI of your application end-point that will be responsible obtaining
token. It should be the same as registered in Miracl system for this client ID.
