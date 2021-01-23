<?php
/*
Plugin Name: ACL Woocommerce PVIT Companion
Description: Redirige les requêtes depuis PVIT vers les bonnes pages Woocommerce
Plugin URI: https://github.com/bface007/ACL-Woocommerce-PVIT-Companion
Author: Dan T. Ngossinga
Version: 0.3
Author URI: https://github.com/bface007
 */
add_action('init', 'process_pvit_post_request');

function process_pvit_post_request()
{
    if (isset($_POST['ref']) && isset($_POST['statut']) && function_exists('wc_get_order')) {
        $order_id = intval($_POST['ref']);
        $status = intval($_POST['statut']);
        $current_order = wc_get_order($order_id);

        if(is_null($current_order)) {
            return;
        }

        if ($current_order->get_payment_method() == 'wc_am_gabon') { // success payment via airtel money's pvit
            if($status == 200) { // if payment succeeded
                if ($current_order->get_status() == 'on-hold') {
                    $items = $current_order->get_items();
                    $found = false;

                    // loop to find if there is at least one non virtual,non downloadable or non backorders allowed item
                    foreach ($items as $item) {
                        $product = wc_get_product($item['product_id']);

                        $is_virtual = $product->is_virtual();

                        $is_downloadable = $product->is_downloadable();

                        $is_backorders_allowed = $product->backorders_allowed();

                        if (!$is_virtual || !$is_downloadable || $is_backorders_allowed) {
                            $found = true;
                            break;
                        }
                    }
                    $next_status = 'completed';

                    if ($found) {
                        $next_status = 'processing';
                    }

                    $current_order->update_status($next_status);
                }

                wp_redirect($current_order->get_checkout_order_received_url());
            } else {
                wc_add_notice( __( 'Paiement échoué ou en attente de validation', 'acl-woocommerce-pvit-companion' ), 'error' );
                wp_safe_redirect( wc_get_page_permalink( 'cart' ) );
            }

            exit();
        }

    }
}