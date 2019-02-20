<p align="center">
  <a href="https://adshares.net/">
    <img src="https://adshares.net/logos/ads.svg" alt="Adshares" width=72 height=72>
  </a>
  <h3 align="center"><small>Adshares WordPress Plugin</small></h3>
  <p align="center">
    <a href="https://github.com/adshares/wordpress-plugin/issues/new?template=bug_report.md&labels=Bug">Report bug</a>
    ·
    <a href="https://github.com/adshares/wordpress-plugin/issues/new?template=feature_request.md&labels=New%20Feature">Request feature</a>
    ·
    <a href="https://github.com/adshares/wordpress-plugin/wiki">Wiki</a>
  </p>
</p>

<br>

This plugin provides integration with Adshares [AdServer](https://github.com/adshares/adserver) for publishers.
All you have to do is to login into your Adshares account and select which ad units will be displayed.
It supports various options for position and visibility.

## Getting Started

Several quick start options are available:

- Install directly from the [WordPress Plugin Directory](https://wordpress.org/plugins/adshares/) (recommended)
- Install with [Composer](https://getcomposer.org/): `composer require adshares/wordpress-plugin`
- [Download the latest release](https://github.com/adshares/wordpress-plugin/releases/latest)
- Clone the repo: `git clone https://github.com/adshares/wordpress-plugin.git`

This plugin requires **PHP 5.5** or higher.

### Building plugin

 1. Clone or download project
 2. Install [Composer](https://getcomposer.org/)
 3. Build distribution files:
 ```
 composer install
 composer build
 ```
 4. Plugin files will be saved in `build/adshares` directory
 5. Copy directory `build/adshares` into `wp-content/plugins`

### Contributing

Please follow our [Contributing Guidelines](docs/CONTRIBUTING.md)

### Versioning

We use [SemVer](http://semver.org/) for versioning. 
For the versions available, see the [tags on this repository](https://github.com/adshares/wordpress-plugin/tags).

### Authors

* **[Maciej Pilarczyk](https://github.com/m-pilarczyk)** - _PHP programmer_

See also the list of [contributors](https://github.com/adshares/wordpress-plugin/contributors) who participated in this project.

### License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
