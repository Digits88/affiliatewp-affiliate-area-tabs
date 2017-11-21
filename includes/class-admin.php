<?php

class AffiliateWP_Affiliate_Area_Tabs_Admin {

	public function __construct() {
		add_filter( 'affwp_settings_tabs', array( $this, 'settings_tab' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ), 100 );
		add_action( 'affiliate_area_tabs_tab_row', array( $this, 'render_tab_row' ), 10, 3 );
		add_filter( 'pre_update_option_affwp_settings', array( $this, 'pre_update_option' ), 10, 2 );
	}


	/**
	 * Register the new settings tab.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array
	 */
	public function settings_tab( $tabs ) {
		$tabs['affiliate_area_tabs'] = __( 'Affiliate Area Tabs', 'affiliatewp-affiliate-area-tabs' );
		return $tabs;
	}

	/**
	 * Register our settings
	 *
	 * @access public
	 * @since 1.0.0
	 * 
	 * @return array
	 */
	public function register_settings() {

		add_settings_field(
			'affwp_settings[affiliate_area_tabs_list]',
			__( 'Affiliate Area Tabs', 'affiliatewp-affiliate-area-tabs' ) . $this->expand_collapse_tabs(),
			array( $this, 'tabs_list' ),
			'affwp_settings_affiliate_area_tabs',
			'affwp_settings_affiliate_area_tabs'
		);

	}

	/**
	 * Add link to expand/collapse tabs
	 *
	 * @access private
	 * @since 1.2
	 * 
	 * @return string
	 */
	private function expand_collapse_tabs() {
		ob_start();

		$expand_text   = __( 'Expand all tabs', 'affiliatewp-affiliate-area-tabs' );
		$collapse_text = __( 'Collapse all tabs', 'affiliatewp-affiliate-area-tabs' );
		?>
		<p>
			<a href="#" class="aat-hide-show-tabs" data-text-swap="<?php echo $collapse_text; ?>" data-text-original="<?php echo $expand_text; ?>"><?php echo $expand_text; ?></a>
		</p>
		<?php
		return ob_get_clean();
	}

	/**
	 * Sanitize tabs
	 *
	 * @access public
	 * @since 1.2
	 * @param $new_value array of new values
	 * @param $old_value array of old values
	 * 
	 * @return array $new_value
	 */
	public function pre_update_option( $new_value, $old_value ) {

		// Loop through tabs.
		foreach ( $new_value['affiliate_area_tabs'] as $key => $tab ) {
			
			// Skip sanitization on any non-custom tab.
			if ( 0 === $tab['id'] ) {
				continue;
			}

			// Tab's must have both a title and id assigned.
			if ( empty( $tab['title'] ) || ! isset( $tab['id'] ) ) {
				
				// Unset the tab
				unset( $new_value['affiliate_area_tabs'][$key] );
				
				// Skip to the next tab.
				continue;
			}

			// Create an initial tab slug for custom tabs (core tabs already have a slug).
			if ( empty( $tab['slug'] ) ) {
				
				// Create a slug from the tab's title
				$new_value['affiliate_area_tabs'][$key]['slug'] = affiliatewp_affiliate_area_tabs()->functions->make_slug( $tab['title'] );

			}

			// Force the tab ID to be an integer.
			$new_value['affiliate_area_tabs'][$key]['id'] = (int) $new_value['affiliate_area_tabs'][$key]['id'];
			

			// Determine if the tab is a custom tab.
			if ( affiliatewp_affiliate_area_tabs()->functions->is_custom_tab( $tab['slug'] ) ) {

				/**
				 * Loop through the old values
				 * 
				 * This is neccessary since custom tabs could have moved position via the admin interface.
				 * 
				 * First we check if the custom tab exists in the old values.
				 * If so, we then check its title. If the title changed, we attempt
				 * To update its tab slug.
				 */
				foreach ( $old_value['affiliate_area_tabs'] as $old_key => $old_tab ) {
					
					// Found the custom slug, must be the same tab.
					if ( $old_tab['slug'] === $tab['slug'] ) {
						
						// Check to see if the tab's title was changed.
						if ( $old_tab['title'] !== $tab['title'] ) {
							// Create a new slug.
							$new_slug = affiliatewp_affiliate_area_tabs()->functions->make_slug( $tab['title'] );

							// Check that the slug isn't already in-use.
							if ( ! array_key_exists( $new_slug, affwp_get_affiliate_area_tabs() ) ) {
								// Slug isn't being used, use the new slug.
								$new_value['affiliate_area_tabs'][$key]['slug'] = $new_slug;
							}

							// If the new slug is already in-use, the slug will not change, and remain as its previous value.
						
						}

					}
				}

			}

		}

		return $new_value;

	}

	/**
	 * Admin scripts.
	 *
	 * @access public
	 * @since 1.2
	 */
	public function admin_scripts() {

		// Admin CSS file.
		$screen = get_current_screen();

		// Use minified libraries if SCRIPT_DEBUG is set to false.
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Register scripts.
		wp_register_style( 'aat-admin', AFFWP_AAT_PLUGIN_URL . 'assets/css/admin' . $suffix . '.css', array( 'dashicons' ), AFFWP_AAT_VERSION );
		wp_register_script( 'aat-admin-scripts', AFFWP_AAT_PLUGIN_URL . 'assets/js/admin-scripts' . $suffix . '.js',  array(), AFFWP_AAT_VERSION, false );

		// Enqueue scripts.
		if ( $screen->id === 'affiliates_page_affiliate-wp-settings' && isset( $_GET['tab'] ) && $_GET['tab'] === 'affiliate_area_tabs' ) {
			wp_enqueue_style( 'aat-admin' );
			wp_enqueue_script( 'aat-admin-scripts' );
		}

	}

	/**
	 * Render the tabs list
	 * 
	 * @since 1.0.0
	 */
	public function tabs_list() {
		
		$tabs  = affiliatewp_affiliate_area_tabs()->functions->get_tabs();
		$count = count( $tabs );
		$key   = 0;
		?>
		
		<div class="widefat aat_repeatable_table">
		
			<div class="aat-repeatables-wrap">
				<?php foreach ( $tabs as $tab_slug => $tab_title ) : $key++; ?>
					<div class="aat_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
						<?php do_action( 'affiliate_area_tabs_tab_row', $key, $tab_slug, $tab_title ); ?>
					</div>
				<?php endforeach; ?>

				<div class="aat-add-repeatable-row">
					<button class="button-secondary aat-add-repeatable"><?php _e( 'Add New Tab', 'affiliatewp-affiliate-area-tabs' ); ?></button>
				</div>

			</div>
		</div>
	<?php
	}
	
	/**
	 * Individual Tab Row
	 *
	 * Used to output a row for each tab.
	 *
	 * @since 1.2
	 *
	 * @param       $key
	 * @param string $tab_slug
	 * @param string $tab_title
	 */
	public function render_tab_row( $key, $tab_slug, $tab_title ) {
		?>

		<div class="aat-draghandle-anchor">
			<span class="dashicons dashicons-move" title="<?php _e( 'Click and drag to re-order', 'affiliatewp-affiliate-area-tabs' ); ?>"></span>
		</div>

		<div class="aat-repeatable-row-header">

			<div class="aat-repeatable-row-title">
				<?php printf( __( '%s', 'affiliatewp-affiliate-area-tabs' ), '<span class="affiliate-area-tabs-title">' . $tab_title . '</span><span class="aat-tab-number"> (Tab <span class="aat-tab-number-key">' . $key . '</span>)</span>' ); ?>
				<span class="affiliate-area-tabs-edit">
					<span class="dashicons dashicons-arrow-down"></span>
				</span>
			</div>
			
			<div class="aat-repeatable-row-standard-fields" style="display: none;">

				<?php if ( $tab_notice = $this->tab_notices( $tab_slug ) ) : ?>
					<p class="aat-tab-default"><?php echo $tab_notice; ?></p>
				<?php endif; ?>

				<?php

				/**
				 * Hide a field if it's not a custom tab.
				 */
				$hidden = ! affiliatewp_affiliate_area_tabs()->functions->is_custom_tab( $tab_slug ) ? ' style="display: none;"' : '';

				/**
				 * Tab title.
				 */
				?>

				<p class="aat-tab-title"<?php echo $hidden; ?>>

					<label for="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][title]"><strong><?php _e( 'Tab Title', 'affiliatewp-affiliate-area-tabs' ); ?></strong></label>
					<span class="description"><?php _e( 'Enter a title for the tab.', 'affiliatewp-affiliate-area-tabs' ); ?></span>
				
					<input id="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][title]" name="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][title]" type="text" class="widefat" value="<?php echo esc_attr( $tab_title ); ?>"/>

					<?php
					/**
					 * This makes sure the core tabs have their slug correctly saved as per the default_tabs() method.
					 * Custom tab slugs are generated in update_settings()
					 */
					?>
					<input name="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][slug]" type="hidden" value="<?php echo $tab_slug; ?>" />

				</p>

				<?php
				/**
				 * Tab content.
				 */
				?>
				<p class="aat-tab-content"<?php echo $hidden; ?>>
					<label for="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][id]"><strong><?php _e( 'Tab Content', 'affiliatewp-affiliate-area-tabs' ); ?></strong></label>
					<span class="description"><?php _e( 'Select which page will be used for the tab\'s content. This page will be blocked for non-affiliates.', 'affiliatewp-affiliate-area-tabs' ); ?></span>
						
					<?php

					$pages = affiliatewp_affiliate_area_tabs()->functions->get_pages();
					$tabs  = affiliate_wp()->settings->get( 'affiliate_area_tabs', array() );
					?>
					<select id="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][id]" class="widefat" name="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][id]">
						<?php foreach ( $pages as $id => $title ) :
							$selected = $tabs && isset( $tabs[$key]['id'] ) ? ' ' . selected( $tabs[$key]['id'], $id, false ) : '';
						?>
							<option value="<?php echo $id; ?>"<?php echo $selected; ?>><?php echo $title; ?></option>
						<?php endforeach; ?>
					</select>
				</p>

				<?php
				$checked = isset( $tabs[$key]['hide'] ) && 'yes' === $tabs[$key]['hide'] ? 'yes' : 'no';
				?>
				<p class="aat-tab-hide">
					<label for="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][hide]">
						<input type="checkbox" id="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][hide]" class="affiliate-area-hide-tabs" name="affwp_settings[affiliate_area_tabs][<?php echo $key; ?>][hide]" value="yes"<?php checked( $checked, 'yes' ); ?> />
						<?php _e( 'Hide tab in Affiliate Area', 'affiliatewp-affiliate-area-tabs' ); ?>
					</label>
				</p>

				<?php 
				/**
				 * Delete custom tab.
				 * Only custom tabs can be deleted.
				 * 
				 * @since 1.2
				 */
				if ( affiliatewp_affiliate_area_tabs()->functions->is_custom_tab( $tab_slug ) ) : ?>
				<p><a href="#" class="aat_remove_repeatable"><?php _e( 'Delete tab', 'affiliatewp-affiliate-area-tabs' ); ?></a></p>
				<?php endif; ?>

			</div>
		</div>
		
	<?php 
	}

	/**
	 * Tab notice.
	 * Useful for add-ons to let the end-user know where the tab was added from.
	 * 
	 * @since 1.2
	 * @param string $tab_slug The slug of the tab
	 * 
	 * @return string $notice The notice to show on the tab
	 */
	public function tab_notices( $tab_slug ) {

		switch ( $tab_slug ) {

			case affiliatewp_affiliate_area_tabs()->functions->is_default_tab( $tab_slug ):
				$notice = __( 'This is a default AffiliateWP tab.', 'affiliatewp-affiliate-area-tabs' );
				break;
			
			case 'order-details':
				$notice = __( 'This tab has been added from the <em>Order Details For Affiliates</em> add-on.', 'affiliatewp-affiliate-area-tabs' );
				break;

			case 'direct-links':
				$notice = __( 'This tab has been added from the <em>Direct Link Tracking</em> add-on.', 'affiliatewp-affiliate-area-tabs' );
				break;

			case 'coupons':
				$notice = __( 'This tab has been added from the <em>Show Affiliate Coupons</em> add-on.', 'affiliatewp-affiliate-area-tabs' );
				break;

			default:
				$notice = '';
				break;
		}

		return apply_filters( 'affwp_aat_tab_notices', $notice, $tab_slug );

	}

}
new AffiliateWP_Affiliate_Area_Tabs_Admin;