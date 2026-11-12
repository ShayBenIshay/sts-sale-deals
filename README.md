# STS Sale Deals

Sale deals as a WooCommerce product taxonomy (`sts_deal`), with a label design per deal shown on products, a `/sales` page listing every deal product, and a page per deal at `/sales/<deal-slug>`.

## How it works
- **One deal = one term** under Products > Sale Deals. The term holds the label: text, colour, text colour, optional tag icon, or an uploaded image badge.
- Assign products to a deal from the product edit screen, Quick Edit or Bulk Edit.
- The same deal appears in Discount Rules PRO's filter dropdown (Custom Taxonomy > Sale Deals), so one selection drives both the discount and the label.
- The label shows on the single product page (gallery) and on the shop loop. If a product is in several deals, the oldest deal wins.
- Products > Tags to deals converts existing product tags into deals (tags are left untouched).

## Sales pages
- `/sales` lists every product that is in any deal, with a chip per deal (deals with no products get no chip). `/sales/<deal-slug>` lists one deal's products and shows the deal description. No pages are created in the database; both are served by the plugin.
- Deals are also available in Appearance > Menus, so you add them to the menu by hand. The plugin never edits menus.
- The plugin's `templates/archive-deals.php` renders both through the theme's own `content-product.php`. A theme overrides it with `taxonomy-sts_deal.php` (both views) or `sts-sales.php` (`/sales` only). Helpers for such templates: `sts_sale_deals_chip_terms()`, `sts_sale_deals_sales_url()`.
- The listing is not paginated.
- Deal pages are public and indexable. After updating from 0.1.x the rewrite rules are flushed once automatically.

## Themes that bypass the loop hooks
If the theme overrides `content-product.php` and never fires `woocommerce_before_shop_loop_item_title`, echo `sts_sale_deals_badge_html( $product )` inside the image wrapper. Disable the automatic hooks with the filters `sts_sale_deals_auto_loop` / `sts_sale_deals_auto_single` (return `false`).

## Requirements
WordPress 5.0+, WooCommerce 5.0+, PHP 7.4+.

## Status
Version 0.2.0 (deals, labels, Sales page and per-deal pages). Provided as-is, under GPL-2.0-or-later (see `LICENSE`).

Setup, customization and support are available from [Shay Tech Solutions](https://shaytechsolutions.com).
