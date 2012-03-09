<?php

class Tx_KnEntree_Service_Authentication {

	/**
	 * Authentication object
	 *
	 * @var SimpleSAML_Auth_Simple
	 */
	protected $auth;

	/**
	 * frontendUserRepository
	 *
	 * @var Tx_KnEntree_Domain_Repository_FrontendUserRepository
	 */
	public $frontendUserRepository;

	/**
	 * injectFrontendUserRepositoru
	 *
	 * @param Tx_KnEntree_Domain_Repository_FrontendUserRepository $frontendUserRepository
	 * @return void
	 */
	public function injectFrontendUserRepository(Tx_KnEntree_Domain_Repository_FrontendUserRepository $frontendUserRepository) {
		$this->frontendUserRepository = $frontendUserRepository;
	}

	/**
	 * Constructor, includes simplesamlphp
	 */
	public function __construct() {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['kn_entree']);

		if (!isset($extConf['simplesamlphpPath'])) {
			throw new Exception('simplephpPath not set in extension configuration!');
		}

		if (!is_file($extConf['simplesamlphpPath'] . '/lib/_autoload.php')) {
			throw new Exception('simplesamlphp not found in ' . $extConf['simplesamlphpPath']);
		}

		require_once $extConf['simplesamlphpPath'] . '/lib/_autoload.php';
	}

	/**
	 * Sets the service provider
	 *
	 * @param string $serviceProviderIdentifier
	 * @return void
	 */
	public function setServiceProvider($serviceProviderIdentifier) {
		$this->auth = new SimpleSAML_Auth_Simple($serviceProviderIdentifier);
	}

	/**
	 * Get service provider
	 *
	 * @return SimpleSAML_Auth_Simple
	 */
	public function getServiceProvider() {
		return $this->auth;
	}

	/**
	 * Redirects to the Entree login page based on the specified settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function entreeAuthentication(array $settings) {
		$this->auth->requireAuth($settings);
	}

	/**
	 * Logs the user into TYPO3 based on a frontend user object
	 *
	 * @param Tx_KnEntree_Domain_Model_FrontendUser $user
	 * @return boolean
	 */
	public function typo3Authentication(Tx_KnEntree_Domain_Model_FrontendUser $user) {
		$loginData = array(
			'username' => $user->getUsername(),
			'uident' => $user->getPassword(),
			'status' =>'login'
		);

			// @todo write a service
		$GLOBALS['TSFE']->fe_user->checkPid = 0;
		$info = $GLOBALS['TSFE']->fe_user->getAuthInfoArray();
		$user = $GLOBALS['TSFE']->fe_user->fetchUserRecord($info['db_user'], $loginData['username']);
		if ($GLOBALS['TSFE']->fe_user->compareUident($user, $loginData)) {
			$GLOBALS['TSFE']->fe_user->user = $GLOBALS["TSFE"]->fe_user->fetchUserSession();
			$GLOBALS['TSFE']->loginUser = 1;
			$GLOBALS['TSFE']->fe_user->fetchGroupData();
			$GLOBALS['TSFE']->fe_user->start();
			$GLOBALS['TSFE']->fe_user->createUserSession($user);
			$GLOBALS['TSFE']->fe_user->loginSessionStarted = TRUE;
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Logs the user out of both systems
	 *
	 * @return void
	 */
	public function logOff() {
		$this->logOffTYPO3();
		$this->logOffEntree();
	}

	/**
	 * Logs the current user  out of TYPO3
	 *
	 * @return void
	 */
	protected function logOffTYPO3() {
		$GLOBALS['TSFE']->fe_user->logoff();
	}

	/**
	 * Logs the current user out of Entree
	 *
	 * @return void
	 */
	protected function logOffEntree() {

	}
}
?>