# scalar-hooks

This repository contains Codeigniter hooks designed specifically for [Scalar](https://github.com/anvc/scalar). 

See the CI documentation regarding [Hooks - Extending the Framework Core](https://codeigniter.com/userguide2/general/hooks.html) for more information.  

## Quickstart

To use all o the hooks:

1. Copy `config/hooks.php` to the scalar `system/application/config/hooks.php`
2. Copy `hooks/*.php` to `system/application/hooks/`
3. In `system/application/config/config.php` enable hooks, since they are disabled by default:
    ```
    $config['enable_hooks'] = TRUE;
    ```
4. Review the documentation for each hook and configure as necessary (e.g. set environment variables, etc). 

## Hook Documentation

To use `hooks/allowed_hosts.php`, the following environment variable must be set:

- `SCALAR_ALLOWED_HOSTS: "my.scalar.doman"`

To also check subdomains, be sure to specify the parent domain otherwise it defaults to the `SERVER_NAME`:

- `SCALAR_DOMAIN: "my.scalar.domain"`

