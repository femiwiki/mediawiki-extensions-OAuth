<?php
/**
 * Class containing GUI even handler functions for an OAuth environment
 */
class MWOAuthUIHooks {
	public static function onGetPreferences( $user, &$preferences ) {
		$prefInsert = array( 'mwoauth-prefs-managegrants' =>
			array(
				'section' => 'personal/info',
				'label-message' => 'mwoauth-prefs-managegrants',
				'type' => 'info',
				'raw' => true,
				'default' => Linker::linkKnown(
					SpecialPage::getTitleFor( 'MWOAuthManageMyGrants' ),
					wfMessage( 'mwoauth-prefs-managegrantslink' )->escaped()
				)
			),
		);

		$after = array_key_exists( 'editcount', $preferences )
			? 'usergroups'
			: 'userid';
		$preferences = wfArrayInsertAfter( $preferences, $prefInsert, $after );

		return true;
	}

	/**
	 * Override MediaWiki namespace for a message
	 * @param string $title Message name (no prefix)
	 * @param string &$message Message wikitext
	 * @return bool false if we replaced $message
	 */
	public static function onMessagesPreLoad( $title, &$message ) {
		// Quick fail check
		if ( substr( $title, 0, 15 ) !== 'Tag-OAuth_CID:_' ) {
			return true;
		}

		// More expensive check
		if ( !preg_match( '!^Tag-OAuth_CID:_(\d+)((?:-description)?)(?:/|$)!', $title, $m ) ) {
			return true;
		}

		$dbr = MWOAuthUtils::getCentralDB( DB_SLAVE );
		$cmr = MWOAuthDAOAccessControl::wrap(
			MWOAuthConsumer::newFromId( $dbr, $m[1] ), RequestContext::getMain()
		);
		if ( !$cmr ) {
			// Invalid consumer, skip it
			return true;
		}

		if ( $m[2] ) {
			$message = htmlspecialchars( $cmr->get( 'description' ) );
		} else {
			$target = SpecialPage::getTitleFor( 'MWOAuthListConsumers',
				'view/' . $cmr->get( 'consumerKey' )
			);
			$encName = wfEscapeWikiText( $cmr->get( 'name', function( $s ) use ( $cmr ) {
				return $s . ' [' . $cmr->get( 'version' ) . ']';
			} ) );
			$message = "[[$target|$encName]]";
		}
		return false;
	}
}
