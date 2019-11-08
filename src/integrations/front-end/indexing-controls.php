<?php
/**
 * WPSEO plugin file.
 *
 * @package Yoast\WP\Free\Integrations\Front_End
 */

namespace Yoast\WP\Free\Integrations\Front_End;

use Yoast\WP\Free\Conditionals\Front_End_Conditional;
use Yoast\WP\Free\Helpers\Options_Helper;
use Yoast\WP\Free\Helpers\Robots_Helper;
use Yoast\WP\Free\Integrations\Integration_Interface;
use Yoast\WP\Free\Presentations\Indexable_Presentation;

/**
 * Class Indexing_Controls
 */
class Indexing_Controls implements Integration_Interface {

	/**
	 * The robots helper.
	 *
	 * @var Robots_Helper
	 */
	protected $robots;

	/**
	 * @codeCoverageIgnore
	 * @inheritDoc
	 */
	public static function get_conditionals() {
		return [ Front_End_Conditional::class ];
	}

	/**
	 * @codeCoverageIgnore
	 *
	 * @param Robots_Helper $robots The robots helper.
	 */
	public function __construct( Robots_Helper $robots ) {
		$this->robots = $robots;
	}

	/**
	 * @codeCoverageIgnore
	 * @inheritDoc
	 */
	public function register_hooks() {
		// The option `blog_public` is set in Settings > Reading > Search Engine Visibility.
		if ( (string) \get_option( 'blog_public' ) === '0' ) {
			\add_filter( 'wpseo_robots', [ $this->robots, 'set_robots_no_index' ], 10, 2 );
		}

		\add_action( 'template_redirect', [ $this, 'noindex_robots' ] );
		\add_filter( 'loginout', [ $this, 'nofollow_link' ] );
		\add_filter( 'register', [ $this, 'nofollow_link' ] );

		// Remove actions that we will handle through our wpseo_head call, and probably change the output of.
		remove_action( 'wp_head', 'rel_canonical' );
		remove_action( 'wp_head', 'index_rel_link' );
		remove_action( 'wp_head', 'start_post_rel_link' );
		remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
		remove_action( 'wp_head', 'noindex', 1 );
	}

	/**
	 * Sends a Robots HTTP header preventing URL from being indexed in the search results while allowing search engines
	 * to follow the links in the object at the URL.
	 *
	 * @since 1.1.7
	 *
	 * @return boolean Boolean indicating whether the noindex header was sent.
	 */
	public function noindex_robots() {
		if ( ! is_robots() ) {
			return false;
		}

		return $this->set_robots_header();
	}

	/**
	 * Adds rel="nofollow" to a link, only used for login / registration links.
	 *
	 * @param string $input The link element as a string.
	 *
	 * @return string
	 */
	public function nofollow_link( $input ) {
		return str_replace( '<a ', '<a rel="nofollow" ', $input );
	}

	/**
	 * Sets the x-robots-tag to noindex follow.
	 *
	 * @codeCoverageIgnore Too difficult to test.
	 */
	protected function set_robots_header() {
		if ( headers_sent() === false ) {
			header( 'X-Robots-Tag: noindex, follow', true );

			return true;
		}

		return false;
	}
}
