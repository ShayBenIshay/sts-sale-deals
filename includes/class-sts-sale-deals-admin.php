<?php
/**
 * Admin: label design fields on the deal term, and a one-time "convert tags to deals" tool.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class STS_Sale_Deals_Admin {

	private static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$tax = STS_Sale_Deals::TAXONOMY;
		add_action( "{$tax}_add_form_fields", array( $this, 'render_add_fields' ) );
		add_action( "{$tax}_edit_form_fields", array( $this, 'render_edit_fields' ) );
		add_action( "created_{$tax}", array( $this, 'save_fields' ) );
		add_action( "edited_{$tax}", array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_menu', array( $this, 'add_tool_page' ) );
		add_action( 'admin_post_sts_sd_convert', array( $this, 'handle_convert' ) );
	}

	public function enqueue( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || STS_Sale_Deals::TAXONOMY !== $screen->taxonomy ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'sts-sale-deals-admin', STS_SD_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'wp-color-picker' ), STS_SD_VERSION, true );
	}

	private function fields( $s ) {
		$image_url = $s['image'] ? wp_get_attachment_image_url( $s['image'], 'thumbnail' ) : '';
		wp_nonce_field( 'sts_sd_save_term', 'sts_sd_nonce' );
		?>
		<div class="sts-sd-field">
			<label for="sts_label_text"><?php esc_html_e( 'Label text', 'sts-sale-deals' ); ?></label>
			<input type="text" name="sts_label_text" id="sts_label_text" value="<?php echo esc_attr( $s['text_raw'] ); ?>">
			<p class="description"><?php esc_html_e( 'Shown on the product, e.g. "3 for 10". Leave empty to use the deal name.', 'sts-sale-deals' ); ?></p>
		</div>
		<div class="sts-sd-field">
			<label for="sts_label_bg"><?php esc_html_e( 'Label colour', 'sts-sale-deals' ); ?></label>
			<input type="text" name="sts_label_bg" id="sts_label_bg" class="sts-sd-color" value="<?php echo esc_attr( $s['bg'] ); ?>">
		</div>
		<div class="sts-sd-field">
			<label for="sts_label_color"><?php esc_html_e( 'Text colour', 'sts-sale-deals' ); ?></label>
			<input type="text" name="sts_label_color" id="sts_label_color" class="sts-sd-color" value="<?php echo esc_attr( $s['color'] ); ?>">
		</div>
		<div class="sts-sd-field">
			<label>
				<input type="checkbox" name="sts_label_icon" value="1" <?php checked( $s['icon'] ); ?>>
				<?php esc_html_e( 'Show tag icon', 'sts-sale-deals' ); ?>
			</label>
		</div>
		<div class="sts-sd-field">
			<label><?php esc_html_e( 'Image badge (optional, replaces the text pill)', 'sts-sale-deals' ); ?></label>
			<input type="hidden" name="sts_label_image" id="sts_label_image" value="<?php echo esc_attr( $s['image'] ); ?>">
			<img id="sts_label_image_preview" src="<?php echo esc_url( $image_url ); ?>" style="max-width:80px;display:<?php echo $image_url ? 'block' : 'none'; ?>">
			<button type="button" class="button" id="sts_label_image_pick"><?php esc_html_e( 'Choose image', 'sts-sale-deals' ); ?></button>
			<button type="button" class="button" id="sts_label_image_clear"><?php esc_html_e( 'Remove', 'sts-sale-deals' ); ?></button>
		</div>
		<?php
	}

	private function defaults() {
		return array(
			'text_raw' => '',
			'bg'       => '#2a8c8d',
			'color'    => '#ffffff',
			'icon'     => true,
			'image'    => 0,
		);
	}

	public function render_add_fields() {
		$this->fields( $this->defaults() );
	}

	public function render_edit_fields( $term ) {
		$s             = STS_Sale_Deals::get_term_settings( $term );
		$s['text_raw'] = (string) get_term_meta( $term->term_id, 'sts_label_text', true );
		echo '<tr class="form-field"><th>' . esc_html__( 'Label design', 'sts-sale-deals' ) . '</th><td>';
		$this->fields( $s );
		echo '</td></tr>';
	}

	public function save_fields( $term_id ) {
		if ( ! isset( $_POST['sts_sd_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sts_sd_nonce'] ) ), 'sts_sd_save_term' ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		update_term_meta( $term_id, 'sts_label_text', isset( $_POST['sts_label_text'] ) ? sanitize_text_field( wp_unslash( $_POST['sts_label_text'] ) ) : '' );
		update_term_meta( $term_id, 'sts_label_bg', isset( $_POST['sts_label_bg'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['sts_label_bg'] ) ) : '' );
		update_term_meta( $term_id, 'sts_label_color', isset( $_POST['sts_label_color'] ) ? (string) sanitize_hex_color( wp_unslash( $_POST['sts_label_color'] ) ) : '' );
		update_term_meta( $term_id, 'sts_label_icon', empty( $_POST['sts_label_icon'] ) ? 0 : 1 );
		update_term_meta( $term_id, 'sts_label_image', isset( $_POST['sts_label_image'] ) ? absint( $_POST['sts_label_image'] ) : 0 );
	}

	/* ---- Convert product tags to deals ---- */

	public function add_tool_page() {
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'Convert tags to deals', 'sts-sale-deals' ),
			__( 'Tags to deals', 'sts-sale-deals' ),
			'manage_woocommerce',
			'sts-sd-convert',
			array( $this, 'render_tool_page' )
		);
	}

	public function render_tool_page() {
		$tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Convert product tags to deals', 'sts-sale-deals' ); ?></h1>
			<?php if ( isset( $_GET['converted'] ) ) : ?>
				<div class="notice notice-success"><p>
					<?php
					/* translators: %d: number of deals */
					echo esc_html( sprintf( __( '%d deal(s) created or updated. Edit them under Products > Sale Deals.', 'sts-sale-deals' ), absint( $_GET['converted'] ) ) );
					?>
				</p></div>
			<?php endif; ?>
			<p><?php esc_html_e( 'Tick the tags that are sale deals. Each becomes a deal with the same slug, and every product with the tag is added to it. The tags are left untouched. You can edit the label afterwards.', 'sts-sale-deals' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sts_sd_convert">
				<?php wp_nonce_field( 'sts_sd_convert', 'sts_sd_convert_nonce' ); ?>
				<table class="widefat striped" style="max-width:720px">
					<thead><tr><th></th><th><?php esc_html_e( 'Tag', 'sts-sale-deals' ); ?></th><th><?php esc_html_e( 'Products', 'sts-sale-deals' ); ?></th><th><?php esc_html_e( 'Label text (optional)', 'sts-sale-deals' ); ?></th></tr></thead>
					<tbody>
					<?php if ( is_wp_error( $tags ) || empty( $tags ) ) : ?>
						<tr><td colspan="4"><?php esc_html_e( 'No product tags found.', 'sts-sale-deals' ); ?></td></tr>
					<?php else : ?>
						<?php foreach ( $tags as $tag ) : ?>
							<tr>
								<td><input type="checkbox" name="tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>"></td>
								<td><?php echo esc_html( $tag->name ); ?> <code><?php echo esc_html( $tag->slug ); ?></code></td>
								<td><?php echo esc_html( $tag->count ); ?></td>
								<td><input type="text" name="label[<?php echo esc_attr( $tag->term_id ); ?>]" class="regular-text"></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
				<?php submit_button( __( 'Convert selected tags', 'sts-sale-deals' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_convert() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['sts_sd_convert_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sts_sd_convert_nonce'] ) ), 'sts_sd_convert' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'sts-sale-deals' ) );
		}

		$tax_deal = STS_Sale_Deals::TAXONOMY;
		$tag_ids  = isset( $_POST['tags'] ) ? array_map( 'absint', (array) $_POST['tags'] ) : array();
		$labels   = isset( $_POST['label'] ) ? (array) wp_unslash( $_POST['label'] ) : array();
		$done     = 0;

		foreach ( $tag_ids as $tag_id ) {
			$tag = get_term( $tag_id, 'product_tag' );
			if ( ! $tag || is_wp_error( $tag ) ) {
				continue;
			}
			$existing = get_term_by( 'slug', $tag->slug, $tax_deal );
			if ( $existing ) {
				$deal_id = $existing->term_id;
			} else {
				$created = wp_insert_term( $tag->name, $tax_deal, array( 'slug' => $tag->slug ) );
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$deal_id = (int) $created['term_id'];
			}

			$label = isset( $labels[ $tag_id ] ) ? sanitize_text_field( $labels[ $tag_id ] ) : '';
			if ( '' !== $label ) {
				update_term_meta( $deal_id, 'sts_label_text', $label );
			}

			$product_ids = get_posts(
				array(
					'post_type'      => 'product',
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'tax_query'      => array( array( 'taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_id ) ),
				)
			);
			foreach ( $product_ids as $pid ) {
				wp_set_object_terms( $pid, $deal_id, $tax_deal, true );
			}
			$done++;
		}

		wp_safe_redirect( add_query_arg( array( 'post_type' => 'product', 'page' => 'sts-sd-convert', 'converted' => $done ), admin_url( 'edit.php' ) ) );
		exit;
	}
}
