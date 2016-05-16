# Setup

## Development

Project uses [Composer](http://getcomposer.org) for management of dependencies. You will need `composer.phar` for
installation of dependencies.

Use:
`php composer.phar install` in project directory to download all necessary dependencies into `./vendor` directory.

# Sample

See `index.php` for sample and `templates/main.php` for used template.
Replace `CLIENT_ID`, `CLIENT_SECRET` and `REDIRECT_URL` with valid values.
To start using Miracl API, `MiraclClient` should be initialized. `CLIENT_ID` and `CLIENT_SECRET` can be obtained from
Miracl(unique per application). `REDIRECT_URL` is URI of your application end-point that will be responsible obtaining
token. It should be the same as registered in Miracl system for this client ID.
