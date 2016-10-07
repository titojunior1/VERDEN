<?php
/**
 * Executa Script CRON.
 * @author Tito Junior 
 *
 */

$basePath = realpath(dirname(__FILE__) . '/..');
require_once $basePath . DIRECTORY_SEPARATOR . 'includes/verden.php';

try {
	Controller_Cron::executar($_SERVER['argv']);
} catch (Exception $e) {
	echo 'Erro ao executar Cron: ' . $e->getMessage();
	echo PHP_EOL;
	echo PHP_EOL;
}
