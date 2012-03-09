<?php

class Tx_KnEntree_Domain_Repository_FrontendUserRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * Returns current user object or false when no user was found
	 *
	 * @return mixed
	 */
	public function getCurrentUser() {
		if (is_array($GLOBALS['TSFE']->fe_user->user)) {
			return $this->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
		}

		return FALSE;
	}
}
?>