<?php
	use Symfony\Component\Yaml\Yaml;

	require 'vendor/autoload.php';
	
	$ymlConfig = Yaml::parse(file_get_contents('parameters.yml'));

	foreach($ymlConfig['parameters'] as $confName => $confValue) {
		define($confName, $confValue);
	}
