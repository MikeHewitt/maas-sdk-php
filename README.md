# maas-sdk-php

[![Master Build Status](https://secure.travis-ci.org/miracl/maas-sdk-php.png?branch=master)](https://travis-ci.org/miracl/maas-sdk-php?branch=master)
[![Master Coverage Status](https://coveralls.io/repos/miracl/maas-sdk-php/badge.svg?branch=master&service=github)](https://coveralls.io/github/miracl/maas-sdk-php?branch=master)

* **category**:    SDK
* **copyright**:   2016 MIRACL UK LTD
* **license**:     ASL 2.0 - http://www.apache.org/licenses/LICENSE-2.0
* **link**:        https://github.com/miracl/maas-sdk-php

## Description

PHP version of the Software Development Kit (SDK) for MPin-As-A-Service (MAAS).


# Setup

## Development

First, you need to install all development dependencies using [Composer](https://getcomposer.org/):

```bash
$ curl -sS https://getcomposer.org/installer | php
$ mv composer.phar /usr/local/bin/composer
```

This project include a Makefile that allows you to test and build the project with simple commands.
To see all available options:

```bash
make help
```

To install all the development dependencies:

```bash
make build_dev
```

## Running all tests

Before committing the code, please check if it passes all tests using

```bash
make qa_all
```
this generates the phpunit coverage report in target/coverage.

Generate the documentation:

```bash
make docs
```

Generate static analysis reports in target/report:

```bash
make reports
```

Please check all the available options using `make help`.


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

Generates the URL for use in the `mpad.js` script (see [Frontend](#markdown-header-frontend)) and saves the verification data in the session.

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
<script src="<<Insert correct mpad url here>>" data-authurl="{{ auth_url }}" data-element="btmpin"></script>
```
Please refer to your distributor-specific documentation to find the correct url for the mpad.js script src

# Sample app

See `src/index.php` for sample script and `templates/main.php` for the webpage template.

Credentials-based configuration is found in `miracl.json`.

Replace `CLIENT_ID`, `SECRET` and `REDIRECT_URL` with valid credential values.

To enable use of Miracl APIs, a `MiraclClient` should be initialized. `CLIENT_ID` and `SECRET` can be obtained from
Miracl (they are unique per application). `REDIRECT_URL` is URI of your application end-point that will be responsible for obtaining an access token. It should match what is registered in the Miracl system for the corresponding client ID.

The Redirect URI for this sample is `http://127.0.0.1` if it is accessed on the local web server root.
