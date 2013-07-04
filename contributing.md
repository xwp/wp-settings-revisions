Pull requests should be opened with the `develop` branch as the base. Do not
open pull requests into the `master` branch, as this branch contains the latest
stable release.

Before submitting pull request, run the unit tests. To run, see [post][1] via
[WP-CLI][4] (install it too):

```sh
wp_docroot=path/to/wordpress/install
cd $wp_docroot
wp core init-tests ./unit-tests --dbname=wp_test --dbuser=root --dbpass=asd
mysql -u'root' -p'asd' -e 'CREATE DATABASE IF NOT EXISTS wp_test'
cd wp-content/plugins/settings-revisions
WP_TESTS_DIR=$wp_docroot/unit-tests phpunit
```

Also run your code through PHP_CodeSniffer (PHPCS) and the [WordPress Coding Standards sniffs][2].
We have to integrate the PHPCS checks with Travis CI (see [#16][3]).

[1]: http://wp-cli.org/blog/plugin-unit-tests.html
[2]: https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards
[3]: https://github.com/x-team/wp-settings-revisions/issues/16
[4]: http://wp-cli.org/
