<?php
/**
 * Slice 2: the /sales landing archive (all products in any deal) and the
 * per-deal archives at /sales/<deal-slug>, both served by one template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STS_Sale_Deals_Pages {

	const QUERY_VAR      = 'sts_sales';
	const REWRITE_OPTION = 'sts_sd_rewrite_version';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'add_rewrite' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrite' ), 99 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'adjust_query' ) );
		add_filter( 'template_include', array( $this, 'template_include' ), 99 );
		add_filter( 'document_title_parts', array( $this, 'document_title' ) );
		add_filter( 'body_class', array( $this, 'body_class' ) );
	}

	public static function activate() {
		$self = self::get_instance();
		$self->add_rewrite();
		flush_rewrite_rules();
		update_option( self::REWRITE_OPTION, STS_SD_VERSION );
	}

	public function add_rewrite() {
		add_rewrite_rule( '^' . STS_Sale_Deals::BASE . '/?$', 'index.php?' . self::QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Rules change between versions; flush once per version, not on every request.
	 */
	public function maybe_flush_rewrite() {
		if ( get_option( self::REWRITE_OPTION ) !== STS_SD_VERSION ) {
			flush_rewrite_rules();
			update_option( self::REWRITE_OPTION, STS_SD_VERSION );
		}
	}

	public function query_vars( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public static function is_sales_page() {
		return (bool) get_query_var( self::QUERY_VAR );
	}

	public static function is_deals_view() {
		return self::is_sales_page() || is_tax( STS_Sale_Deals::TAXONOMY );
	}

	/**
	 * Make the main query a full product listing: every product in any deal on
	 * /sales, every product of the deal on /sales/<deal>. No pagination: deals
	 * hold a handful of products (same as a category page on the themes we run).
	 */
	public function adjust_query( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( self::QUERY_VAR ) ) {
			$query->set( 'post_type', 'product' );
			$query->set( 'posts_per_page', -1 );
			$query->set( 'tax_query', array(
				array(
					'taxonomy' => STS_Sale_Deals::TAXONOMY,
					'operator' => 'EXISTS',
				),
			) );
			$query->is_home     = false;
			$query->is_archive  = true;
			$query->is_post_type_archive = false;
			return;
		}

		if ( $query->is_tax( STS_Sale_Deals::TAXONOMY ) ) {
			$query->set( 'posts_per_page', -1 );
		}
	}

	/**
	 * Use the theme's own taxonomy-sts_deal.php (or sts-sales.php for /sales) when
	 * it exists, otherwise the plugin's generic template.
	 */
	public function template_include( $template ) {
		if ( self::is_sales_page() ) {
			$theme = locate_template( array( 'sts-sales.php', 'taxonomy-' . STS_Sale_Deals::TAXONOMY . '.php' ) );
			return $theme ? $theme : STS_SD_PLUGIN_DIR . 'templates/archive-deals.php';
		}
		if ( is_tax( STS_Sale_Deals::TAXONOMY ) ) {
			$theme = locate_template( array( 'taxonomy-' . STS_Sale_Deals::TAXONOMY . '.php' ) );
			return $theme ? $theme : STS_SD_PLUGIN_DIR . 'templates/archive-deals.php';
		}
		return $template;
	}

	public function document_title( $parts ) {
		if ( self::is_deals_view() ) {
			$parts['title'] = self::page_title();
		}
		return $parts;
	}

	public function body_class( $classes ) {
		if ( self::is_deals_view() ) {
			// /sales is not a WooCommerce page, so add the classes WooCommerce's grid CSS is scoped to.
			$classes[] = 'sts-deals-page';
			$classes[] = 'woocommerce';
			$classes[] = 'woocommerce-page';
		}
		return $classes;
	}

	public static function page_title() {
		if ( is_tax( STS_Sale_Deals::TAXONOMY ) ) {
			return STS_Sale_Deals::get_display_name( get_queried_object() );
		}
		return apply_filters( 'sts_sale_deals_sales_title', __( 'Sales', 'sts-sale-deals' ) );
	}

	/**
	 * Deals that currently have at least one product (empty deals get no chip).
	 *
	 * @return WP_Term[]
	 */
	public static function get_chip_terms() {
		$terms = get_terms( array(
			'taxonomy'   => STS_Sale_Deals::TAXONOMY,
			'hide_empty' => true,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );
		return is_wp_error( $terms ) ? array() : $terms;
	}

	public static function display_name( $term ) {
		return STS_Sale_Deals::get_display_name( $term );
	}
}

/**
 * Template helpers, for themes that supply their own sts-sales.php / taxonomy-sts_deal.php.
 */
function sts_sale_deals_sales_url() {
	return home_url( '/' . STS_Sale_Deals::BASE . '/' );
}

function sts_sale_deals_display_name( $term ) {
	return STS_Sale_Deals::get_display_name( $term );
}

function sts_sale_deals_chip_terms() {
	return STS_Sale_Deals_Pages::get_chip_terms();
}
