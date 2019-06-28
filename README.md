Connect to Git via Curl

### Usage

#### Composer
First add the following to your `composer.json` file:
```json
"require": {
  "srag/gitcurl": ">=0.1.0"
},
```

If your plugin should support ILIAS 5.2 or earlier you need to require `ilCurlConnection` like follow in your `composer.json` file:
```json
"autoload": {
    "classmap": [
      "../../../../../../../Services/WebServices/Curl/classes/class.ilCurlConnection.php",
```
May you need to adjust the relative `ilCurlConnection` path

And run a `composer install`.

If you deliver your plugin, the plugin has it's own copy of this library and the user doesn't need to install the library.

Tip: Because of multiple autoloaders of plugins, it could be, that different versions of this library exists and suddenly your plugin use an older or a newer version of an other plugin!

So I recommand to use [srag/librariesnamespacechanger](https://packagist.org/packages/srag/librariesnamespacechanger) in your plugin.

### Requirements
* ILIAS 5.3 or ILIAS 5.4
* PHP >=7.0

### Adjustment suggestions
* Adjustment suggestions by pull requests
* Adjustment suggestions which are not yet worked out in detail by Git tasks under https://jira.studer-raimann.ch/projects/LGITCURL
* Bug reports under https://jira.studer-raimann.ch/projects/LGITCURL
* For external users you can report it at https://plugins.studer-raimann.ch/goto.php?target=uihk_srsu_LGITCURL
