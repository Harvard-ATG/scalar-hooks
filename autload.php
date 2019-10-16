<?php
spl_autoload_register(function ($class_name) {
    $basedir = dirname(__FILE__);
		$class_map = array(
			"Scalar_hook_allowed_hosts" => "$basedir/hooks/allowed_hosts.php",
		);
    if(isset($class_map[$class_name])) {
			  print("loading $class_name");
        require_once($class_map[$class_name]);
    }
});
