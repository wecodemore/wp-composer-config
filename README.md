# Auto generate your `wp-config.php`

Usage as Composer post-package-install script. Auto generation of `wp-config.php` files for
WordPress installs. This package mostly is a convenience package that should make the build process
easier.

## What is it?

This package is a `wp-config.php` generator for WordPress that runs as _Composer Script_.
It is based up on [Vance Lucas PHPDotEnv package](https://github.com/vlucas/phpdotenv) that
offers a solution for adding a not version controlled/ignored `.env` file. The key/value data
is made available as `getenv()`, `$_ENV` and `$_SERVER` variable afterwards. This config
generator fetches a maintained list of configurable WordPress constants (like DB credentials)
and builds a `wp-config.php` file in the WordPress root directory. This does not make increase
security, but it helps preventing that you push sensitive information to a version controled
repository. It also makes it easier to maintain different environments like development,
staging and production.

## How To: Setup

We recommend to use something like Andreys/"[@Rarst](https://twitter.com/Rarst)" recipe for a
[site stack](http://composer.rarst.net/recipe/site-stack) to get a thoughtful base structure for
your project. Simply add this package to your stack.

	"config" : {
		"vendor-dir": "wp-content/vendor"
	},
	// ...
	"require" : {
		// ... other software installed to /var/www/wp-content/vendor
        "wecodemore/wp-composer-config" : "1.x-dev"
	},

Then setup the script

	"scripts" : {
		"post-package-install" : [
			"WCM\\WPComposerConfig\\WPConfigCommand::postPackageInstall"
		]
	},

Finally the script needs some variable set for the `extra` object:

	"extra"   : {
		"wordpress-install-dir" : "path/to/wordpress",
		"wordpress-env-dir"     : "path/to/the/.env/file
	 }

That's it.

## How To: Use it

The check list:

1. Make sure that you add `.env` to your `.gitignore` file
1. Take a look at the `/ci` folder in this package and copy the `.env.example` contents
 to your projects `.env` file. This is a blueprint of all major versions
1. Adjust the settings that you find there
1. Add the setup steps described above to your `composer.json` file
1. Open your CLI and enter `composer install`
1. Auth keys and Salt get fetched directly from the wordpess.org servers

#### Options

The following options are mirrored by the package over from PHPDotEnv:

**Comment:** Prefix a line with `#`.

**Empty string:** Set a `=` after your key without a value

The following option is new and only works for auto generating the `wp-config.php` file.
This should make it easier to test different settings without adding and deleting the same
constants over and over again.

**Use WP Default:** Set neither a `=`, nor a value for that key. Or just don't set it at all.

## Internals

The package does not overwrite already existing Auth Keys and Salts. If you need them to be
regenerated, please just delete them from the config by hand. They will get added again with
a new set of hashes directly from wp.org.

## FAQ

#### **Q:** I need Multisite

**A:** Switching from the (default) single site install to a multi site install is a multi step
process that involves user interaction, changing the `.htaccess` file
and adding constants step by step. We don't want to mess up your repo, so we don't do that by
now. But if you think you can make it happen, just fork the repo and send a Pull Request.

#### **Q:** Shall I install it from GitHub or from Packagist?

**A:** The package is on Packagist and auto updated from GitHub instantly (using WebHooks).

#### **Q:** Will you implement X?

**A:** Just ask in a new issue. We discuss everything with everyone. If you got a PR, even better.

#### **Q:** What version should I refer to in my `composer.json`?

**A:** We use [semantic versioning](http://semver.org/), so you will want to stay
up to date with major versions.
