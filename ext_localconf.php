<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

Tx_Extbase_Utility_Extension::configurePlugin(
	$_EXTKEY,
	'Auth',
	array(
		'Auth' => 'show, login, error, success'
	),
	array(
		'Auth' => 'show, login, error, success'
	)
);
?>