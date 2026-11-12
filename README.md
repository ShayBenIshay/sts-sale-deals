# STS Sale Deals

Sale deals as a hidden WooCommerce product taxonomy (`sts_deal`), with a label design per deal shown on products.

## How it works
- **One deal = one term** under Products > Sale Deals. The term holds the label: text, colour, text colour, optional tag icon, or an uploaded image badge.
- Assign products to a deal from the product edit screen, Quick Edit or Bulk Edit.
- The same deal appears in Discount Rules PRO's filter dropdown (Custom Taxonomy > Sale Deals), so one selection drives both the discount and the label.
- The label shows on the single product page (gallery) and on the shop loop. If a product is in several deals, the oldest deal wins.
- Products > Tags to deals converts existing product tags into deals (tags are left untouched).

## Themes that bypass the loop hooks
If the theme overrides `content-product.php` and never fires `woocommerce_before_shop_loop_item_title`, echo `sts_sale_deals_badge_html( $product )` inside the image wrapper. Disable the automatic hooks with the filters `sts_sale_deals_auto_loop` / `sts_sale_deals_auto_single` (return `false`).

## Requirements
WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+.

## Status
Version 0.1.1 (slice 1: deals + labels). The Sales page and sub-pages are planned. Provided as-is, under GPL-2.0-or-later (see `LICENSE`).

Setup, customization and support are available from [Shay Tech Solutions](https://shaytechsolutions.com).
