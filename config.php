<?php
	use Symfony\Component\Yaml\Yaml;

	require 'vendor/autoload.php';
	
	$ymlConfig = Yaml::parse('parameters.yml');

	foreach($ymlConfig['parameters'] as $confName => $confValue) {
		define($confName, $confValue);
	}
