<?php

class Tx_KnEntree_Controller_AuthController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * authService
	 *
	 * @var Tx_Kntree_Service_Authentication
	 */
	public $authService;

	/**
	 * frontendUserRepository
	 *
	 * @var Tx_KnEntree_Domain_Repository_FrontendUserRepository
	 */
	public $frontendUserRepository;

	/**
	 * frontendUserGroupRepository
	 *
	 * @var Tx_KnEntree_Domain_Repository_FrontendUserGroupRepository
	 */
	public $frontendUserGroupRepository;

	/**
	 * injectAuthService
	 *
	 * @param Tx_KnEntree_Service_Authentication $authService
	 * @return void
	 */
	public function injectAuthService(Tx_KnEntree_Service_Authentication $authService) {
		$this->authService = $authService;
	}

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
	 * frontendUserGroupRepository
	 *
	 * @param Tx_KnEntree_Domain_Repository_FrontendUserGroupRepository $frontendUserGroupRepository
	 * @return void
	 */
	public function injectFrontendUserGroupRepository(Tx_KnEntree_Domain_Repository_FrontendUserGroupRepository $frontendUserGroupRepository) {
		$this->frontendUserGroupRepository = $frontendUserGroupRepository;
	}

	/**
	 * initializeAction
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->authService->setServiceProvider($this->settings['serviceProviderIdentifier']);

			// When logged into Entree, attempt a user synchronisation
		if ($this->authService->getServiceProvider()->isAuthenticated() == TRUE) {
			$attributes = $this->authService->getServiceProvider()->getAttributes();
			if (isset($attributes[$this->settings['attributeMapping']['username']])) {
				$results = $this->frontendUserRepository->findByUsername($attributes[$this->settings['attributeMapping']['username']]);

				if (count($results) == 0) {
					$this->persistFrontendUser($attributes);
				}
			}

			if ($this->frontendUserRepository->getCurrentUser() === FALSE) {
				$this->authService->typo3Authentication($this->frontendUserRepository->findOneByUsername($attributes[$this->settings['attributeMapping']['username']]));
			}
		} else {
			if ($this->frontendUserRepository->getCurrentUser() instanceof Tx_KnEntree_Domain_Model_FrontendUser) {
				if ($this->settings['terminateT3SessionWhenNoEntreeAuth'] == 1) {
					$this->authService->logOff();
				}
			}
		}
	}

	/**
	 * showAction
	 *
	 * @return void
	 */
	public function showAction() {
		$this->view->assign('currentUser', $this->frontendUserRepository->getCurrentUser());
	}

	/**
	 * errorAction
	 *
	 * @return void
	 */
	public function errorAction() {

	}

	/**
	 * successAction
	 *
	 * @return void
	 */
	public function successAction() {

	}

	/**
	 * loginAction
	 *
	 * @return void
	 */
	public function loginAction() {
		if ($this->authService->getServiceProvider()->isAuthenticated() == FALSE) {
			$this->uriBuilder->setCreateAbsoluteUri(TRUE);
			$errorUrl = $this->uriBuilder->uriFor(
				'error',
				array(),
				'Auth',
				'KnEntree',
				'Auth'
			);

			$returnUrl = $this->uriBuilder->uriFor(
				'success',
				array(),
				'Auth',
				'KnEntree',
				'Auth'
			);

			$this->authService->entreeAuthentication(array(
				'ErrorURL' => $errorUrl,
				'KeepPost' => (bool) $this->settings['keepPost'],
				'ReturnTo' => $returnUrl,
				'ReturnCallBack' => array()
			));
		} else {
			$this->redirect('show');
		}
	}

	/**
	 * Persists the specified frontenduser
	 *
	 * @param array $attributes
	 * @return void
	 */
	protected function persistFrontendUser(array $attributes) {
		$object = $this->objectManager->create('Tx_KnEntree_Domain_Model_FrontendUser');

		foreach ($this->settings['attributeMapping'] as $userField => $attribute) {
			$methodName = 'set' . ucfirst($userField);
			if (is_callable(array($object, $methodName)) && isset($attributes[$attribute])) {
				$object->$methodName($attributes[$attribute][0]);
			}
		}

		$userGroup = $this->frontendUserGroupRepository->findOneByUid($this->settings['defaultUserGroup']);
		if ($userGroup instanceof Tx_KnEntree_Domain_Model_FrontendUserGroup) {
			$object->addUsergroup($userGroup);
		}
		$object->setPassword(substr(md5($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . microtime()), 0, 8));
		$this->frontendUserRepository->add($object);
		$persistenceManager = t3lib_div::makeInstance('Tx_Extbase_Persistence_Manager');
		$persistenceManager->persistAll();
	}
}
?>