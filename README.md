# Laravel Nova - optional

## Installation

You have to merge the following with your `composer.json` and adjust the `laravel/nova` version constraint to your needs.

```json
{
    "require": {
        "laravel/nova": "^3.0"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./nova"
        }
    ],
    "scripts": {
        "pre-install-cmd": [
            "[ -d nova/src/Actions ] || mkdir -p nova/src/Actions",
            "[ -f nova/composer.json ] || echo '{\"name\":\"laravel/nova\",\"description\":\"\",\"version\":\"3.9999.0\",\"autoload\":{\"psr-4\":{\"Laravel\\\\\\Nova\\\\\\\":\"src/\"}}}' > nova/composer.json",
            "[ -f nova/src/Actions/Actionable.php ] || echo '<?php namespace Laravel\\Nova\\Actions; trait Actionable{}' > nova/src/Actions/Actionable.php"
        ],
        "pre-update-cmd": [
            "[ -d nova/src/Actions ] || mkdir -p nova/src/Actions",
            "[ -f nova/composer.json ] || echo '{\"name\":\"laravel/nova\",\"description\":\"\",\"version\":\"3.9999.0\",\"autoload\":{\"psr-4\":{\"Laravel\\\\\\Nova\\\\\\\":\"src/\"}}}' > nova/composer.json",
            "[ -f nova/src/Actions/Actionable.php ] || echo '<?php namespace Laravel\\Nova\\Actions; trait Actionable{}' > nova/src/Actions/Actionable.php"
        ]
    }
}
```

After this you should require this package by running following.

```bash
composer require astrotomic/nova-optional
```

You should ensure that the `App\Providers\NovaServiceProvider` isn't listed in your `config/app.php[providers]` - but the class itself should exist - following the nova installation instructions.

## Usage

After you've prepared your application in general you have to install nova or thee placeholder.
To do so you should setup your nova credentials with composer - or not if you don't have a license.
After this you should call the command provided by this package.

```bash
php artisan nova:optional {--no-dev}
```

As we only download nova itself but not its dependencies you will have to install them in a second run.

```bash
composer update laravel/nova --with-all-dependencies 
```

### Nova Addons

In case you have nova addons installed you shouldn't discover them by default.
To do so add all of them to your `composer.json` like following.

```json
{
    "extra": {
        "laravel": {
            "dont-discover": [
                "tightenco/nova-google-analytics"
            ]
        }
    }
}
```

To use them if nova is successfully installed you should register these package service providers again in your `\App\Providers\NovaServiceProvider`.

```php
use Astrotromic\NovaOptional\NovaOptionalServiceProvider;
use Laravel\Nova\NovaApplicationServiceProvider;
use Tightenco\NovaGoogleAnalytics\ToolServiceProvider;
use Tightenco\NovaGoogleAnalytics\CardServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    public function register(): void
    {
        if (NovaOptionalServiceProvider::hasNovaInstalled()) {
            $this->app->register(ToolServiceProvider::class);
            $this->app->register(CardServiceProvider::class);
        }
    }
}
```

## Credits

- [Tom Witkowski](https://github.com/Gummibeer)
- [Matt Stauffer](https://github.com/mattstauffer)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.