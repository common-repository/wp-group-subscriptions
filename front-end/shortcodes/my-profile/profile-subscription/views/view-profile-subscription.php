<?php
use \H4APlugin\WPGroupSubs\Common\Subscriber;
use \H4APlugin\WPGroupSubs\Common\Plan;
use \H4APlugin\WPGroupSubs\Common\Payment;
use \H4APlugin\Core\Common\Countries;
use H4APlugin\Core\FrontEnd\FrontEndForm;
use \H4APlugin\Core\Common\Paypal;
use \H4APlugin\Core\Config;
use function \H4APlugin\Core\wp_build_url;
use function H4APlugin\Core\return_datetime;
use function \H4APlugin\Core\asBoolean;
use function \H4APlugin\Core\format_str_to_display;
use function \H4APlugin\Core\get_current_plugin_domain;

$plugin_domain = get_current_plugin_domain();

if( Subscriber::isLoggedIn() ){
	$subscriber_loggedIn = Subscriber::getSubscriberLoggedIn();
	$plan = new Plan( $subscriber_loggedIn->plan_id, "read" );
	if( isset( $attrs ) && ( !isset( $attrs['title'] ) || ( isset( $attrs['title'] ) && asBoolean( $attrs['title'] ) ) ) ) :
		printf( '<h2>%s</h2>',
			__( "Subscription", $plugin_domain )
		);
	endif;
	?>
    <table>
        <tbody>
        <tr>
            <th><?php _e( "Current Plan", $plugin_domain ) ?></th>
            <td><?php echo $plan->plan_name; ?></td>
        </tr>
        <tr>
            <th><?php _e( "Subscription status", $plugin_domain ) ?></th>
            <td><?php _e(  $subscriber_loggedIn->status, $plugin_domain ); ?>
	            <?php
	            $wgs_paypal_options = get_option( Config::gen_options_name( "paypal" ) );
                if( $wgs_paypal_options['paypal_renew'] === "true" && $subscriber_loggedIn->status === "disabled" ):
	                $form = new FrontEndForm( 1, "activation-account" );
                    if( !empty( $form ) ){
	                    $return_url = Payment::getReturnPageUrl();
	                    $form->action = Paypal::getUrlPaypal();
	                    $form->options['submitBox'] = array( 'button' => "Active your account : Proceed to payment" );
	                    $form->options['has_required_fields'] = false;
	                    $wgs_currency_options = get_option( Config::gen_options_name( "currency" ) );
	                    $form->content[0]['items']['business']['value'] = ( !empty( $wgs_paypal_options['paypal_email'] ) ) ? format_str_to_display( $wgs_paypal_options['paypal_email'] ) : "";
	                    $form->content[0]['items']['item_name']['value'] = $plan->plan_name;
	                    $form->content[0]['items']['amount']['value'] = $plan->price;
	                    $form->content[0]['items']['currency_code']['value'] = ( !empty( $wgs_currency_options['currency'] ) ) ? $wgs_currency_options['currency'] : "";
	                    $form->content[0]['items']['first_name']['value'] = format_str_to_display( $subscriber_loggedIn->first_name );
	                    $form->content[0]['items']['last_name']['value'] = format_str_to_display( $subscriber_loggedIn->last_name );
	                    $address1 = null;
	                    if( !empty( $subscriber_loggedIn->street_number ) ){
		                    $address1 =  format_str_to_display( $subscriber_loggedIn->street_number . " " . $subscriber_loggedIn->street_name );
	                    }else{
		                    $address1 =  format_str_to_display( $subscriber_loggedIn->street_name );
	                    }
	                    $form->content[0]['items']['address1']['value'] = $address1;
	                    $form->content[0]['items']['city']['value'] = format_str_to_display( $subscriber_loggedIn->city );
	                    $form->content[0]['items']['zip']['value'] = format_str_to_display( $subscriber_loggedIn->zip_code );
	                    $form->content[0]['items']['country']['value'] = Countries::getIsoByCountryId( $subscriber_loggedIn->country_id );
	                    $form->content[0]['items']['return']['value'] = $return_url;
	                    $form->content[0]['items']['cancel']['value'] = $return_url;
	                    $form->writeForm();
                    } ?>
	            <?php endif ; ?>
            </td>
        </tr>
        <tr>
            <th><?php _e( "Last Subscription date", $plugin_domain ) ?></th>
            <td><?php echo return_datetime( $subscriber_loggedIn->last_subscription_date,"d/m/Y H:i" ); ?></td>
        </tr>
        <tr>
            <th style="vertical-align: top;"><?php _e( "Your Payments", $plugin_domain ) ?></th><td>
				<?php
				$payments = Payment::getAllPaymentsBySubscriberId( $subscriber_loggedIn->subscriber_id, false );
				if( !$payments ){
					_e( "None", $plugin_domain );
				}else{ ?>
                    <table>
                        <thead>
                        <th><?php _e( "Date", $plugin_domain ) ?></th>
                        <th><?php _e( "Amount", $plugin_domain ) ?></th>
                        </thead>
                        <tbody>
						<?php foreach ( $payments as $payment ):
							$wgs_currency_options = get_option( "wgs-currency-options" );
							printf( '<tr><td>%s</td><td>%s</td></tr>',
								return_datetime( $payment['payment_date'], "d/m/Y H:i" ),
								$payment['amount']
							);
						endforeach; ?>
                        </tbody></table>
				<?php } ?>
            </td>
        </tr>
        </tbody>
    </table>
<?php } ?>