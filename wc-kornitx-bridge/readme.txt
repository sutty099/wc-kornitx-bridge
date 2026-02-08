=== KornitX Bridge for WooCommerce ===
Contributors: sutty099
Tags: woocommerce, kornitx, smartlink, personalization, print-on-demand
Requires at least: 6.1
Tested up to: 6.6
Stable tag: 0.2.9d
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

* Smartlink iFrame with a2c=postMessage, mei, meo; origin and mei validation.
* Listens for ADD_TO_CART_CALLBACK; adds items to Woo cart; stores print job ref + thumbnails.
* Edit Design link on cart (reopens app with pj=<ref>).
* Uses Smartlink thumbnail in cart image/title.
* Variation resolver (Colour via aspect options; Size via print size/attributes).
* Create Orders API: Type 2 lines use print_job_ref; Non-Type-2 include colour/size top-level.
* Debug Tools: enable/disable Smartlink logging, view last callbacks, run variation diagnostics.

== Changelog ==
= 0.2.9d =
* Refactored modules + hard guards on all product flows.
* Stronger resolver; safer add-to-cart; safer cart UI; safer order submission.
