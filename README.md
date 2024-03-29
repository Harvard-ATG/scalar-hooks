# scalar-hooks

Hooks for [Scalar](https://github.com/anvc/scalar).

This repository contains Codeigniter hooks designed specifically for Scalar. See the [CodeIgniter documentation](https://codeigniter.com/userguide2/index.html) for more details about [Hooks and Extending the Framework Core](https://codeigniter.com/userguide2/general/hooks.html).

## Quickstart

To use the hooks:

1. Copy `hooks/*.php` to `system/application/hooks/`
2. In the scalar installation, edit `system/application/config/config.php` and enable hooks:
    ```
    $config['enable_hooks'] = TRUE;
    ```
4. Configure each hook in `config/hooks.php` (otherwise it won't be triggered) and review documentation in this readme for any other required parameters or environment variables.

## Hook Documentation

## Allowed Hosts

**Purpose:** 

Provide a list of strings representing the host/domain names that Scalar is allowed to serve. If subdomains are being used for books, optionally validate those subdomains.

**Configuration**:

In `config/hooks.php` add the following:

```php
$hook['post_controller_constructor'] = array(
    'class'    => 'Scalar_hook_allowed_hosts',
    'function' => 'process_request',
    'filename' => 'allowed_hosts.php',
    'filepath' => 'hooks',
    'params'   => array(
        'subdomain_allowed' => false, 
        'subdomain_validator' => 'is_valid_book'
    )
);
```

**Environment variables:**

- `SCALAR_ALLOWED_HOSTS: "my.scalar.doman"`
- `SCALAR_DOMAIN: "my.scalar.domain"` (only required if subdomains are being validated)

## Tests

To install PHPUnit:

```
$ wget -O phpunit https://phar.phpunit.de/phpunit-7.phar
$ chmod u+x phpunit
$ ./phpunit --version
PHPUnit 7.5.16 by Sebastian Bergmann and contributors.
```

To run unit tests:

```
$ ./phpunit --bootstrap autoload.php tests
```
