<?php
/**
 * Order details
 *
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$order = wc_get_order( $order_id );

?>
<div class="thankyou-border"></div>
<div class="row">
<div class="col-md-6">
<h2><?php _e( 'Order Details', 'woocommerce' ); ?></h2>
<table class="shop_table order_details order-table">
	<thead>
		<tr>
			<td class="cart-name product-name custom-border-none"><?php _e( 'ITEM', 'woocommerce' ); ?></td>
			<td class="cart-name product-total custom-border-none"><?php _e( 'TOTAL', 'woocommerce' ); ?></td>
		</tr>
	</thead>
	<tbody>
		<?php
		if ( sizeof( $order->get_items() ) > 0 ) {

			foreach( $order->get_items() as $item_id => $item ) {
				$_product  = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
				$item_meta = new WC_Order_Item_Meta( $item['item_meta'], $_product );

				if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
					?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
						<td class="product-data product-name">
							<?php
								if ( $_product && ! $_product->is_visible() ) {
									echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item );
								} else {
									echo apply_filters( 'woocommerce_order_item_name', sprintf( '<a href="%s">%s</a>', get_permalink( $item['product_id'] ), $item['name'] ), $item );
								}

								echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', $item['qty'] ) . '</strong>', $item );

								// Allow other plugins to add additional product information here
								do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

								$item_meta->display();

								if ( $_product && $_product->exists() && $_product->is_downloadable() && $order->is_download_permitted() ) {

									$download_files = $order->get_item_downloads( $item );
									$i              = 0;
									$links          = array();

									foreach ( $download_files as $download_id => $file ) {
										$i++;

										$links[] = '<small><a href="' . esc_url( $file['download_url'] ) . '">' . sprintf( __( 'Download file%s', 'woocommerce' ), ( count( $download_files ) > 1 ? ' ' . $i . ': ' : ': ' ) ) . esc_html( $file['name'] ) . '</a></small>';
									}

									echo '<br/>' . implode( '<br/>', $links );
								}

								// Allow other plugins to add additional product information here
								do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
							?>
						</td>
						<td class="product-data product-total">
							<?php echo $order->get_formatted_line_subtotal( $item ); ?>
						</td>
					</tr>
					<?php
				}

				if ( $order->has_status( array( 'completed', 'processing' ) ) && ( $purchase_note = get_post_meta( $_product->id, '_purchase_note', true ) ) ) {
					?>
					<tr class="product-purchase-note">
						<td colspan="3"><?php echo wpautop( do_shortcode( wp_kses_post( $purchase_note ) ) ); ?></td>
					</tr>
					<?php
				}
			}
		}

		do_action( 'woocommerce_order_items_table', $order );
		?>
	</tbody>
	<tfoot>
	<?php
		$has_refund = false;

		if ( $total_refunded = $order->get_total_refunded() ) {
			$has_refund = true;
		}

		if ( $totals = $order->get_order_item_totals() ) {
			foreach ( $totals as $key => $total ) {
				$value = $total['value'];

				// Check for refund
				if ( $has_refund && $key === 'order_total' ) {
					$refunded_tax_del = '';
					$refunded_tax_ins = '';

					// Tax for inclusive prices
					if ( wc_tax_enabled() && 'incl' == $order->tax_display_cart ) {

						$tax_del_array = array();
						$tax_ins_array = array();

						if ( 'itemized' == get_option( 'woocommerce_tax_total_display' ) ) {

							foreach ( $order->get_tax_totals() as $code => $tax ) {
								$tax_del_array[] = sprintf( '%s %s', $tax->formatted_amount, $tax->label );
								$tax_ins_array[] = sprintf( '%s %s', wc_price( $tax->amount - $order->get_total_tax_refunded_by_rate_id( $tax->rate_id ), array( 'currency' => $order->get_order_currency() ) ), $tax->label );
							}

						} else {
							$tax_del_array[] = sprintf( '%s %s', wc_price( $order->get_total_tax(), array( 'currency' => $order->get_order_currency() ) ), WC()->countries->tax_or_vat() );
							$tax_ins_array[] = sprintf( '%s %s', wc_price( $order->get_total_tax() - $order->get_total_tax_refunded(), array( 'currency' => $order->get_order_currency() ) ), WC()->countries->tax_or_vat() );
						}

						if ( ! empty( $tax_del_array ) ) {
							$refunded_tax_del .= ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_del_array ) );
						}

						if ( ! empty( $tax_ins_array ) ) {
							$refunded_tax_ins .= ' ' . sprintf( __( '(Includes %s)', 'woocommerce' ), implode( ', ', $tax_ins_array ) );
						}
					}

					$value = '<del>' . strip_tags( $order->get_formatted_order_total() ) . $refunded_tax_del . '</del> <ins>' . wc_price( $order->get_total() - $total_refunded, array( 'currency' => $order->get_order_currency() ) ) . $refunded_tax_ins . '</ins>';
				}
				?>
				<tr>
					<td class="subtotal-name"><?php echo $total['label']; ?></td>
					<td class="subtotal-price"><?php echo $value; ?></td>
				</tr>
				<?php
			}
		}

		// Check for refund
		if ( $has_refund ) { ?>
			<tr>
				<th scope="row"><?php _e( 'Refunded:', 'woocommerce' ); ?></th>
				<td>-<?php echo wc_price( $total_refunded, array( 'currency' => $order->get_order_currency() ) ); ?></td>
			</tr>
		<?php
		}

		// Check for customer note
		if ( '' != $order->customer_note ) { ?>
			<tr>
				<th scope="row"><?php _e( 'Note:', 'woocommerce' ); ?></th>
				<td><?php echo wptexturize( $order->customer_note ); ?></td>
			</tr>
		<?php } ?>
	</tfoot>
</table>

</div>

<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>

<div class="col-md-6">
<header>
	<h2><?php _e( 'Customer details', 'woocommerce' ); ?></h2>
</header>
<table class="shop_table shop_table_responsive customer_details">
<?php
	if ( $order->billing_email ) {
		echo '<div class="col-md-6"><h3 class="order-header">' . __( 'Email:', 'woocommerce' ) . '</h3><p class="order-info">' . $order->billing_email . '</p></div>';
	}

	if ( $order->billing_phone ) {
		echo '<div class="col-md-6"><h3 class="order-header">' . __( 'Telephone:', 'woocommerce' ) . '</h3><p class="order-info">' . $order->billing_phone . '</p></div>';
	}

	// Additional customer details hook
	do_action( 'woocommerce_order_details_after_customer_details', $order );
?>
</table>

<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) : ?>

<div class="addresses">

	<div class="col-sm-6">

<?php endif; ?>

		<header class="title">
			<h3 class="order-header"><?php _e( 'Billing Address', 'woocommerce' ); ?></h3>
		</header>
		<address>
			<?php
				if ( ! $order->get_formatted_billing_address() ) {
					_e( 'N/A', 'woocommerce' );
				} else {
					echo $order->get_formatted_billing_address();
				}
			?>
		</address>

<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && get_option( 'woocommerce_calc_shipping' ) !== 'no' ) : ?>

	</div><!-- /.col-sm-6 -->

	<div class="col-sm-6">

		<header class="title">
			<h3 class="order-header"><?php _e( 'Shipping Address', 'woocommerce' ); ?></h3>
		</header>
		<address>
			<?php
				if ( ! $order->get_formatted_shipping_address() ) {
					_e( 'N/A', 'woocommerce' );
				} else {
					echo $order->get_formatted_shipping_address();
				}
			?>
		</address>

	</div><!-- /.col-sm-6 -->

</div><!-- /adresses-set -->

<?php endif; ?>

<div class="clear"></div>

</div>
</div>
