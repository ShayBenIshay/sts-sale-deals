<?php
/**
 * Core: the sts_deal taxonomy and badge rendering.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STS_Sale_Deals {

	const TAXONOMY = 'sts_deal';

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// Standard loop + single product placement. Themes that bypass these hooks
		// (custom content-product.php) call sts_sale_deals_badge_html() instead.
		if ( apply_filters( 'sts_sale_deals_auto_loop', true ) ) {
			add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'render_loop_badge' ), 9 );
		}
		if ( apply_filters( 'sts_sale_deals_auto_single', true ) ) {
			add_action( 'woocommerce_before_single_product_summary', array( $this, 'render_single_badge' ), 25 );
		}
	}

	public function register_taxonomy() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'labels'             => array(
					'name'          => __( 'Sale Deals', 'sts-sale-deals' ),
					'singular_name' => __( 'Sale Deal', 'sts-sale-deals' ),
					'menu_name'     => __( 'Sale Deals', 'sts-sale-deals' ),
					'all_items'     => __( 'All deals', 'sts-sale-deals' ),
					'edit_item'     => __( 'Edit deal', 'sts-sale-deals' ),
					'view_item'     => __( 'View deal', 'sts-sale-deals' ),
					'update_item'   => __( 'Update deal', 'sts-sale-deals' ),
					'add_new_item'  => __( 'Add new deal', 'sts-sale-deals' ),
					'new_item_name' => __( 'New deal name', 'sts-sale-deals' ),
					'search_items'  => __( 'Search deals', 'sts-sale-deals' ),
					'not_found'     => __( 'No deals found', 'sts-sale-deals' ),
				),
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_admin_column'  => true,
				'show_in_quick_edit' => true,
				'show_in_rest'       => true,
				'hierarchical'       => true,
				'rewrite'            => false,
				'query_var'          => false,
			)
		);
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'sts-sale-deals', STS_SD_PLUGIN_URL . 'assets/css/sale-deals.css', array(), STS_SD_VERSION );
	}

	/**
	 * Term settings with defaults. Label text falls back to the deal name.
	 */
	public static function get_term_settings( $term ) {
		$text = (string) get_term_meta( $term->term_id, 'sts_label_text', true );
		$bg   = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'sts_label_bg', true ) );
		$fg   = sanitize_hex_color( (string) get_term_meta( $term->term_id, 'sts_label_color', true ) );
		$icon = get_term_meta( $term->term_id, 'sts_label_icon', true );

		return array(
			'text'  => '' !== $text ? $text : $term->name,
			'bg'    => $bg ? $bg : '#2a8c8d',
			'color' => $fg ? $fg : '#ffffff',
			'icon'  => '' === $icon ? true : (bool) $icon,
			'image' => (int) get_term_meta( $term->term_id, 'sts_label_image', true ),
		);
	}

	/**
	 * The deal for a product: the first term by term id (variations use the parent).
	 *
	 * @return WP_Term|null
	 */
	public function get_product_deal( $product ) {
		$product = $product ? wc_get_product( $product ) : null;
		if ( ! $product ) {
			return null;
		}
		$id    = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
		$terms = wp_get_object_terms( $id, self::TAXONOMY, array( 'orderby' => 'term_id', 'order' => 'ASC' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}
		return $terms[0];
	}

	public function get_badge_html( $product = null ) {
		if ( null === $product ) {
			global $product;
		}
		$term = $this->get_product_deal( $product );
		if ( ! $term ) {
			return '';
		}
		$s = self::get_term_settings( $term );

		if ( $s['image'] && wp_attachment_is_image( $s['image'] ) ) {
			$inner = wp_get_attachment_image( $s['image'], 'medium', false, array( 'class' => 'sts-deal-badge__img', 'alt' => $s['text'] ) );
			$html  = sprintf( '<span class="sts-deal-badge sts-deal-badge--image" data-deal="%s">%s</span>', esc_attr( $term->slug ), $inner );
		} else {
			$icon = '';
			if ( $s['icon'] ) {
				$icon = '<svg class="sts-deal-badge__icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>';
			}
			$html = sprintf(
				'<span class="sts-deal-badge" dir="auto" data-deal="%1$s" role="img" aria-label="%2$s" style="background:%3$s;color:%4$s">%5$s<span class="sts-deal-badge__text">%2$s</span></span>',
				esc_attr( $term->slug ),
				esc_attr( $s['text'] ),
				esc_attr( $s['bg'] ),
				esc_attr( $s['color'] ),
				$icon
			);
		}

		return apply_filters( 'sts_sale_deals_badge_html', $html, $term, $product );
	}

	public function render_loop_badge() {
		echo $this->get_badge_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in get_badge_html().
	}

	public function render_single_badge() {
		$html = $this->get_badge_html();
		if ( '' !== $html ) {
			echo '<div class="sts-deal-gallery-wrapper">' . $html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	}
}
