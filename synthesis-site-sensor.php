<?php

/*
 Plugin Name: Synthesis Site Sensor
 Plugin URI: http://websynthesis.com/site-sensor/
 Description: WordPress worker plugin for the Synthesis Site Sensor service
 Version: 0.5.3
 Author: Copyblogger Media
 Author URI: http://www.copyblogger.com
 */

class Synthesis_Site_Sensor {

	const WP_VERSION = 'wordpress_version';
	const WP_PRIVACY = 'wordpress_privacy';
	const WP_RSS_FEED = 'wordpress_rss_feed';
	const WP_XML_SITEMAP = 'wordpress_xml_sitemap';
	const SS_FEED_VERSION = 'site_sensor_feed_version';

	const SETTINGS_SLUG = 'synthesis-site-sensor-settings-menu';
	const SETTINGS_NONCE = 'synthesis-site-sensor-settings-nonce';

	const TRIGGER_SLUG_OPTION = 'synthesis-site-sensor-trigger-slug';

	const SENSE_WP_VERSION_OPTION = 'synthesis-site-sensor-wp-version';
	const SENSE_PRIVACY_SETTINGS_OPTION = 'synthesis-site-sensor-privacy-settings';
	const SENSE_RSS_FEEDS_OPTION = 'synthesis-site-sensor-rss-feeds';
	const SENSE_XML_SITEMAP_OPTION = 'synthesis-site-sensor-xml-sitemap';

	CONST VERSION = '0.5.3';
	CONST FEED_VERSION = '1.0';

	const DEBUG = false;

	static $plugin_name;

	// Trigger Variable to watch for
	private static $trigger;

	public static function start() {

		// @todo: load plugin text domain
		self::$plugin_name = __( 'Synthesis Site Sensor', 'syn-site-sensor' );

		add_action( 'init', array( __CLASS__, 'check_for_request' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'add_styles' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_plugin_options_menu' ) );
		add_action( 'plugin_action_links', array( __CLASS__, 'plugin_action_links' ), 10, 2 );

		self::$trigger = get_option( self::TRIGGER_SLUG_OPTION, false );

		if ( !self::$trigger )
			add_action( 'admin_notices', array( __CLASS__, 'missing_plugin_settings' ) );

	}

	public static function check_for_request() {

		if ( self::$trigger && isset( $_REQUEST[self::$trigger] ) ) {
			$response = array(
				self::SS_FEED_VERSION => self::FEED_VERSION,
				self::WP_VERSION      => self::get_response_message( self::check_wordpress_version() ),
				self::WP_PRIVACY      => self::get_response_message( self::check_privacy_settings() ),
				self::WP_RSS_FEED     => self::get_response_message( self::check_rss_feed() ),
				self::WP_XML_SITEMAP  => self::get_response_message( self::check_xml_sitemap() ),
			);

			if ( isset( $_REQUEST['d'] ) ) {
				self::debug('<strong>Site Check Results:</strong>');
				foreach ( $response as $check => $message ) {
					self::debug( $check . ': ' . $message);
				}
				die();
			} else {
				die( json_encode( $response ) );
			}

		} else if ( isset( $_REQUEST[self::SETTINGS_NONCE] ) && wp_verify_nonce( $_REQUEST[self::SETTINGS_NONCE], 'save' ) ) {

			if ( isset( $_REQUEST['ssc-url'] ) ) {
				$url = trim( $_REQUEST['ssc-url'] );
				if ( !empty( $url ) ) {
					update_option( self::TRIGGER_SLUG_OPTION, $url );
				}
			}

			// Update settings.
			update_option( self::SENSE_WP_VERSION_OPTION, isset( $_REQUEST['sense-wp-version'] ) );
			update_option( self::SENSE_PRIVACY_SETTINGS_OPTION, isset( $_REQUEST['sense-privacy-settings'] ) );
			update_option( self::SENSE_RSS_FEEDS_OPTION, isset( $_REQUEST['sense-rss-feeds'] ) );
			update_option( self::SENSE_XML_SITEMAP_OPTION, isset( $_REQUEST['sense-xml-sitemap'] ) );
		}
	}

	public static function check_rss_feed() {
		// Return true if this check is turned-off
		if ( !get_option( self::SENSE_RSS_FEEDS_OPTION, true ) ) {
			return true;
		}

		$latest_post_url = self::get_latest_post_permalink();
		if ( !$latest_post_url ) {
			return true;
		}

		$feed = fetch_feed( add_query_arg( array( 'format' => 'xml' ), get_feed_link( 'rss2' ) ) );
		if ( is_wp_error( $feed ) ) {
			/** @var WP_Error $feed */
			return new WP_Error( 'broken-rss-feed', sprintf( __( 'Your site RSS feed has the following error. %s', 'syn-site-sensor' ), $feed->get_error_message() ) );
		}

		$items = $feed->get_items();
		if ( !count( $items ) ) {
			return new WP_Error( 'empty-feed', __( 'No items were found on your RSS feed.', 'syn-site-sensor' ) );
		}

		self::debug('Begin Feed Debug');
		self::debug('Latest Post: ' . print_r( $latest_post_url, true ));

		foreach ( $items as $item ) {
			$item_permalink = $item->get_permalink();
			// Grab origin link from Feedburner/FeedBlitz
			$orig = $item->get_item_tags('http://rssnamespace.org/feedburner/ext/1.0', 'origLink');
			$item_origin_permalink = isset( $orig[0] ) && isset( $orig[0]['data'] ) ? $orig[0]['data'] : '';

			self::debug( 'Feed Item: ' . $item->get_permalink() );
			self::debug( 'FB Orig Link: ' . $item_origin_permalink );
			
			if ( $item_permalink == $latest_post_url || $item_origin_permalink == $latest_post_url ) {
				return true;
			}
		}

		return new WP_Error( 'latest-post-not-found', sprintf( 'Your latest post was not among the %d items on your RSS feed.', count( $items ) ) );
	}

	public static function check_xml_sitemap() {

		// If sitemap checks are turned off, then bail on all logic.
		if ( !get_option( self::SENSE_XML_SITEMAP_OPTION, true ) ) {
			self::debug('XML Sitemap detection is disabled');
			return true;
		}

		// See if we're using Yoast's WordPress SEO's sitemap_index.xml
		if ( function_exists( 'get_wpseo_options' ) ) {
			self::debug( 'Yoast SEO is installed' );
			// WordPress SEO is enabled, check to see if sitemap indexes are on.
			$options = get_wpseo_options();
			$sitemap_index_key = 'enablexmlsitemap';

			// Check to see if the option is set, and it's value evaluates to true.
			if ( !empty( $options[$sitemap_index_key] ) ) {
				// Check our sitemap index for the latest post.
				return self::check_yoast_xml_sitemap();
			}
		}

		// Sitemap indexes are not being used. Check the default sitemap.
		return self::check_default_xml_sitemap();
	}

	/**
	 * Checks the normal sitemap (sitemap.xml) for the latest post.
	 *
	 * @return array|bool|WP_Error
	 */
	public static function check_default_xml_sitemap() {
		self::debug( 'Begin XML Sitemap Debug');

		$latest_post_url = self::get_latest_post_permalink();
		self::debug( "Latest Post URL: $latest_post_url" );

		$sitemap_url = site_url( 'sitemap.xml' );
		$sitemap = self::get_xml_sitemap( $sitemap_url );
		self::debug( "Sitemap URL: $sitemap_url");

		if ( is_wp_error( $sitemap ) ) {
			self::debug( 'Error parsing XML sitemap' );
			return $sitemap;
		}

		foreach ( $sitemap->url as $url ) {
			self::debug( 'Sitemap Post URL: ' . $url->loc );
			if ( $url->loc == $latest_post_url ) {
				return true;
			}
		}

		return new WP_Error( 'latest-post-not-found', __( 'Your latest post was not found in your XML sitemap.', 'syn-site-sensor' ) );
	}

	public static function check_yoast_xml_sitemap() {
		self::debug( 'Begin Sitemap Debug' );
		self::debug( 'Checking Yoast XML sitemap' );
		// Determine how many posts we store per post-sitemap.xml page
		$options = get_wpseo_options();
		$sitemap_epp_index_key = 'entries-per-page';
		$sitemap_entries_per_page = !empty( $options[$sitemap_epp_index_key] ) ? intval( $options[$sitemap_epp_index_key] ) : 1000;

		self::debug( "Yoast Entries Per Page: $sitemap_entries_per_page" );

		// Find which post-sitemap{n}.xml page our latest post is on. Note: if there's only one page, {n} is blank.
		$post_counts = wp_count_posts();
		$published_posts = isset( $post_counts->publish ) ? $post_counts->publish : 0;
		$inherit_posts = isset( $post_counts->inherit ) ? $post_counts->inherit : 0;
		$post_sitemap_count = ceil( ( $published_posts + $inherit_posts ) / $sitemap_entries_per_page );

		self::debug( "Sitemap Number: $post_sitemap_count" );

		// If we only have one page of posts, then the sitemap doesn't have a number
		if ($post_sitemap_count == 1) {
			$post_sitemap_count = '';
		}

		$sitemap_file = "post-sitemap{$post_sitemap_count}.xml";
		$sitemap_url = site_url( $sitemap_file );

		self::debug( "Sitemap URL: $sitemap_url" );

		// Fetch the latest sitemap
		$sitemap = self::get_xml_sitemap( $sitemap_url );

		if ( is_wp_error( $sitemap ) ) {
			self::debug( 'Error parsing Yoast sitemap' );
			return $sitemap;
		}

		$latest_post_url = self::get_latest_post_permalink();
		self::debug( "Latest Post URL: $latest_post_url" );

		foreach ( $sitemap->url as $url ) {
			self::debug( 'Sitemap Post URL: ' . $url->loc );
			if ( $url->loc == $latest_post_url ) {
				return true;
			}
		}

		// Return an error if we don't find the latest post in the latest sitemap
		return new WP_Error( 'latest-post-not-found', __( 'Your latest post was not found in your XML sitemap.', 'syn-site-sensor' ) );
	}

	public static function get_xml_sitemap( $url ) {
		$sitemap_response = wp_remote_get( $url );

		if ( is_wp_error( $sitemap_response ) ) {
			return $sitemap_response;
		}

		if ( $sitemap_response['response']['code'] != 200 ) {
			return new WP_Error( 'error-retrieving-xml-sitemap', sprintf( 'Retrieving the XML Sitemap returned the following error: %d - %s', $sitemap_response['response']['code'], esc_html( $sitemap_response['response']['message'] ) ) );
		}

		$sitemap_content = wp_remote_retrieve_body( $sitemap_response );

		try {
			$sitemap = @new SimpleXMLElement( $sitemap_content );
		} catch ( Exception $e ) {
			return new WP_Error( 'error-parsing-sitemap', sprintf( 'Parsing your XML Sitemap caused the folliwing error: %s', esc_html( $e->getMessage() ) ) );
		}

		return $sitemap;
	}

	public static function check_privacy_settings() {
		// Return true if this check is turned-off
		if ( !get_option( self::SENSE_PRIVACY_SETTINGS_OPTION, true ) ) {
			return true;
		}
		if ( '1' == get_option( 'blog_public' ) )
			return true;

		return new WP_Error( 'site-not-public', 'Your site is configured to block search engines. Unblock them in Settings &rarr; Privacy.' );
	}

	public static function check_wordpress_version() {
		// Return true if this check is turned-off
		if ( !get_option( self::SENSE_WP_VERSION_OPTION, true ) ) {
			return true;
		}

		// Include admin to gain access to required update functions
		require_once( ABSPATH . 'wp-admin/includes/admin.php' );

		$update = get_preferred_from_update_core();
		// it's possible that the version has never been checked
		if ( $update === false ) {

			wp_version_check();
			$update = get_preferred_from_update_core();

		}

		if ( isset( $update->response ) && $update->response == 'latest' )
			return true;

		return new WP_Error( 'core-out-of-date', 'There is an update available for WordPress. Update your site to keep it secure.' );
	}

	private static function get_response_message( $response ) {
		if ( !self::DEBUG && !isset( $_REQUEST['d'] ) ) {
			return is_wp_error( $response ) ? false : true;
		}

		// If something goes wrong, status checks return a WP_Error
		// This pulls out the error message
		if ( is_wp_error( $response ) ) {
			/** @var WP_Error $response */
			return $response->get_error_message();
		} else {
			//return "ok";
			return 'true';
		}
	}

	private static function debug( $message ) {
		if ( isset( $_REQUEST['d'] ) ) {
			echo $message . "<br />\n";
		}
	}

	private static function get_latest_post_permalink() {

		$latest_posts = wp_get_recent_posts( array( 'numberposts' => 1, 'post_status' => 'publish' ) );
		if ( empty( $latest_posts ) || !is_array( $latest_posts ) )
			return false;

		$latest_post = array_shift( $latest_posts );
		$post_id = $latest_post['ID'];
		$latest_post_url = esc_url( apply_filters( 'the_permalink_rss', get_permalink( $post_id ) ) );

		return $latest_post_url;

	}

	// Callback for "admin_menu"
	static function add_plugin_options_menu() {

		add_options_page( self::$plugin_name, self::$plugin_name, 'manage_options', self::SETTINGS_SLUG, array( __CLASS__, 'render_settings_page' ) );

	}

	// Callback to render the settings page.
	static function render_settings_page() {

		if ( !current_user_can( 'manage_options' ) )
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'syn-site-sensor' ) );

		// Inputs for the view
		$ssc_url = get_option( self::TRIGGER_SLUG_OPTION );
		$sense_wp_version = get_option( self::SENSE_WP_VERSION_OPTION, true );
		$sense_privacy_settings = get_option( self::SENSE_PRIVACY_SETTINGS_OPTION, true );
		$sense_rss_feeds = get_option( self::SENSE_RSS_FEEDS_OPTION, true );
		$sense_xml_sitemap = get_option( self::SENSE_XML_SITEMAP_OPTION, true );
		include( dirname( __FILE__ ) . '/views/manage-site-sensor.php' );

	}

	public static function missing_plugin_settings() {

		$screen = get_current_screen();
		if ( ( !isset( $screen->base ) || 'plugins' != $screen->base ) && current_user_can( 'manage_options' ) ) {
			?>
			<div class="error">
			<p>
				<?php printf( __( '%s has not been configured with an access option. It will not function until it is configured.', 'syn-site-sensor' ), self::$plugin_name ); ?>
			</p>
			</div>
		<?php
		}

	}

	public static function add_styles( $hook ) {

		if ( $hook == 'settings_page_synthesis-site-sensor-settings-menu' )
			wp_enqueue_style( 'synthesis-site-sensor', plugin_dir_url( __FILE__ ) . '/css/site-sensor.css', array(), self::VERSION );

	}

	function plugin_action_links( $links, $file ) {

		if ( $file != plugin_basename( __FILE__ ) )
			return $links;

		$settings_link = '<a href="options-general.php?page=' . self::SETTINGS_SLUG . '">' . __( 'Settings', 'wp_mail_smtp' ) . '</a>';

		array_unshift( $links, $settings_link );

		return $links;

	}

}

Synthesis_Site_Sensor::start();
