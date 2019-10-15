<?php

$hook['post_controller_constructor'] = array(
    'class'    => 'Scalar_hook_allowed_hosts',
    'function' => 'process_request',
    'filename' => 'allowed_hosts.php',
    'filepath' => 'hooks',
    'params'   => array()
);
