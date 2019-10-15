# scalar-hooks

This repository contains Codeigniter hooks designed specifically for [Scalar](https://github.com/anvc/scalar). 

See the CI documentation regarding [Hooks - Extending the Framework Core](https://codeigniter.com/userguide2/general/hooks.html) for more information.  

## Quickstart

To use all o the hooks:

1. Copy `hooks/*.php` to `system/application/hooks/`
2. In `system/application/config/config.php` enable hooks, since they are disabled by default:
    ```
    $config['enable_hooks'] = TRUE;
    ```
4. Review the documentation for each hook and configure as necessary (e.g. set environment variables, etc). 

## Hook Documentation

## Allowed Hosts

Configuration in `config/hooks.php`:

```php
$hook['post_controller_constructor'] = array(
    'class'    => 'Scalar_hook_allowed_hosts',
    'function' => 'process_request',
    'filename' => 'allowed_hosts.php',
    'filepath' => 'hooks',
    'params'   => array('subdomains' => true)
);
```

Environment variables:

- `SCALAR_ALLOWED_HOSTS: "my.scalar.doman"`
- `SCALAR_DOMAIN: "my.scalar.domain"` (only if subdomains is set to true)
