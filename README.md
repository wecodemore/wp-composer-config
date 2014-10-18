# Auto generate your `wp-config.php`

Usage as Composer post-package-install script. Auto generation of `wp-config.php` files for
WordPress installs. This package mostly is a convenience package that should make the build process
easier.

## What is it?

This package is a `wp-config.php` generator for WordPress. It is based up on
[Vance Lucas PHPDotEnv package](https://github.com/vlucas/phpdotenv) that offers a solution
for adding a not version controlled/ignored `.env` file. The key/value data is made available
as `getenv()`, `$_ENV` and `$_SERVER` variable afterwards. This config generator fetches a
maintained list of configurable WordPress constants (like DB credentials) and builds a
`wp-config.php` file in the WordPress root directory.

## Suggested Tools

 * Composer to handle packages like the WordPress core, themes and plugins
 * 

## How To

We recommend to use something like Andreys/"[@Rarst](https://twitter.com/Rarst)" recipe for a
[site stack](http://composer.rarst.net/recipe/site-stack) to get a thoughtful base structure for
your project. Simply add this package to your stack.

	"config":       {
		"vendor-dir": "wp-content/vendor"
	},
	// ...
	"require"      : {
		// ... other software installed to /var/www/wp-content/vendor
        "wecodemore/wp-composer-config" : "~1.0"
	},

Then setup the script

	"scripts"      : {
		"post-package-install" : [
			"WCM\\WPComposerConfig\\WPConfigCommand::postPackageInstall"
		]
	},


## TODO

 +

## FAQ

#### **Q:** Shall I install it from GitHub or from Packagist?

**A:** The package is on Packagist and auto updated from GitHub instantly (using WebHooks).

#### **Q:** What version should I refer to in my `composer.json`?

**A:** We use [semantic versioning](http://semver.org/), so you will want to stay up to date with major versions.
