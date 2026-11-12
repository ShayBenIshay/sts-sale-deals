<?php
/**
 * Generic template for /sales and /sales/<deal>. A theme overrides it by adding
 * taxonomy-sts_deal.php (both views) or sts-sales.php (/sales only).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$is_deal = is_tax( STS_Sale_Deals::TAXONOMY );
$current = $is_deal ? get_queried_object() : null;
?>

<div class="sts-deals-wrap">
	<h1 class="sts-deals-title"><?php echo esc_html( STS_Sale_Deals_Pages::page_title() ); ?></h1>

	<?php $chips = STS_Sale_Deals_Pages::get_chip_terms(); ?>
	<?php if ( $chips || $is_deal ) : ?>
		<nav class="sts-deals-chips" aria-label="<?php echo esc_attr__( 'Sale deals', 'sts-sale-deals' ); ?>">
			<?php if ( $is_deal ) : ?>
				<a class="sts-deals-chip sts-deals-chip--all" href="<?php echo esc_url( sts_sale_deals_sales_url() ); ?>"><?php echo esc_html__( 'All sales', 'sts-sale-deals' ); ?></a>
			<?php endif; ?>
			<?php foreach ( $chips as $chip ) : ?>
				<?php $active = $current && (int) $current->term_id === (int) $chip->term_id; ?>
				<a class="sts-deals-chip<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_term_link( $chip ) ); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo esc_html( STS_Sale_Deals::get_display_name( $chip ) ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( $current && ! empty( $current->description ) ) : ?>
		<div class="sts-deals-description"><?php echo wp_kses_post( wpautop( $current->description ) ); ?></div>
	<?php endif; ?>

	<?php if ( have_posts() ) : ?>
		<?php
		wc_get_template( 'loop/loop-start.php' );
		while ( have_posts() ) :
			the_post();
			wc_get_template_part( 'content', 'product' );
		endwhile;
		wc_get_template( 'loop/loop-end.php' );
		?>
	<?php else : ?>
		<p class="sts-deals-empty"><?php echo esc_html__( 'There are no products in this deal right now.', 'sts-sale-deals' ); ?></p>
	<?php endif; ?>
</div>

<?php
get_footer();
