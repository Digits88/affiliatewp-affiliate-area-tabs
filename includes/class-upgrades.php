<?php

class Affiliate_Area_Tabs_Upgrades {

	/**
	 * Signals whether the upgrade was successful.
	 *
	 * @access public
	 * @var    bool
	 */
	private $upgraded = false;

	/**
	 * Affiliate Area Tabs version.
	 *
	 * @access private
	 * @since  1.2
	 * @var    string
	 */
	private $version;

	/**
	 * Sets up the Upgrades class instance.
	 *
	 * @access public
	 */
	public function __construct() {

		$this->version = get_option( 'affwp_aat_version' );

		add_action( 'admin_init', array( $this, 'init' ), -9999 );

	}

	/**
	 * Initializes upgrade routines for the current version of Affiliate Area Tabs.
	 *
	 * @access public
	 */
	public function init() {

		if ( empty( $this->version ) ) {
			$this->version = '1.1.6'; // last version that didn't have the version option set
		}

		if ( version_compare( $this->version, '1.2', '<' ) ) {
			$this->v12_upgrade();
		}

		// Inconsistency between current and saved version.
		if ( version_compare( $this->version, AFFWP_AAT_VERSION, '<>' ) ) {
			$this->upgraded = true;
		}

		// If upgrades have occurred
		if ( $this->upgraded ) {
			update_option( 'affwp_aat_version_upgraded_from', $this->version );
			update_option( 'affwp_aat_version', AFFWP_AAT_VERSION );
		}

	}

	/**
	 * Performs database upgrades for version 1.2.
	 *
	 * @access private
	 * @since 1.2
	 */
	private function v12_upgrade() {
		
		// Get the current Affiliate Area Tabs.
		$affiliate_area_tabs = affiliate_wp()->settings->get( 'affiliate_area_tabs' );

		if ( $affiliate_area_tabs ) {
			foreach ( $affiliate_area_tabs as $key => $tab_array ) {
				// Set the slug for any custom tab
				$affiliate_area_tabs[$key]['slug']  = affiliatewp_affiliate_area_tabs()->make_slug( $tab_array['title'] );
			}
		}

		// Get the current AffiliateWP settings
		$options = get_option( 'affwp_settings' );

		// Get the default AffiliateWP tabs. We need to merge these with any custom tabs.
		$default_tabs = affiliatewp_affiliate_area_tabs()->default_tabs();
		
		// Create our new array in the needed format.
		$new_tabs = array();

		$i = 1;

		foreach ( $default_tabs as $slug => $title ) {
			$new_tabs[$i]['id']    = 0;
			$new_tabs[$i]['title'] = $title;
			$new_tabs[$i]['slug']  = $slug;
			$i++;
		}

		/**
		 * Prior to v1.2, tabs that were hidden were stored in the affiliate_area_hide_tabs array.
		 * This will add a "hide" key to the existing "affiliate_area_tabs" array and remove the now uneeded affiliate_area_hide_tabs array.
		 */
		$hide_tabs = affiliate_wp()->settings->get( 'affiliate_area_hide_tabs' );

		// Some tabs are currently hidden.
		if ( $hide_tabs ) {
		
			// Loop through our affiliate area tabs and match them to any tab hidden in the old array.
			foreach ( $new_tabs as $key => $tab_array ) {

				if ( isset( $tab_array['slug'] ) && array_key_exists( $tab_array['slug'], $hide_tabs ) ) {
					// We have a match. Set the "hide" key to "yes"
					$new_tabs[$key]['hide'] = 'yes';
				}

			}

			// Finally, remove the old hidden tabs array, we don't need this anymore.
			unset( $options['affiliate_area_hide_tabs'] );

		}
		
		// Merge
		$reindexed = array_merge( $new_tabs, $affiliate_area_tabs );
		
		// Reindex array so it starts from 1
		$options['affiliate_area_tabs'] = array_combine( range(1, count( $reindexed ) ), array_values( $reindexed ) );

		// Update options array to include our new tabs
		update_option( 'affwp_settings', $options );

		// Upgraded!
		$this->upgraded = true;
		
    }
    
}
new Affiliate_Area_Tabs_Upgrades;