<?php
use H4APlugin\Core\Common\Currencies;
use H4APlugin\WPGroupSubs\Common\FormPages;
use H4APlugin\Core\Common\Notices;
use H4APlugin\Core\FrontEnd\FrontEndNotice;
use H4APlugin\WPGroupSubs\Common\Plan;
use function \H4APlugin\Core\format_str_to_kebabcase;

$current_plugin_domain = \H4APlugin\Core\get_current_plugin_domain();
$transient_name = FrontEndNotice::gen_transient_name();
$transient = get_transient( $transient_name );

if( !empty( $transient ) && !empty( $transient[ 'front-end' ] ) ){
    Notices::displayAll();
}else{
	$single_form_type_id = "";
	$multiple_form_type_id = "";
	$plans = Plan::getPlans( "object" );
	?><section class="wgs-card-container">
    <?php
    if( !empty( $plans ) ){
        foreach ( $plans as $plan ){
            ?>
            <div class="wgs-card wgs-card-inline wgs-text-center">
                <div class="wgs-card-block">
                    <div id="<?php echo format_str_to_kebabcase( $plan->plan_name, true ); ?>" class="wgs-card-content">
                        <h4 class="wgs-card-title"><?php echo $plan->plan_name; ?></h4>
                        <p class="wgs-card-text">
                            <?php
                            $str_price = "";
                            if( $plan->price === 0 ){
                                $str_price .= __( "Free!", $current_plugin_domain );
                            }else{
	                            $wgs_currency_options = get_option( "wgs-currency-options" );
	                            $str_price = Currencies::format_string_price( $plan->price, $wgs_currency_options['currency'], $wgs_currency_options['currency_position'] );
                            }
                            printf('<span class="wgs-card-amount">%s</span>', $str_price );
                            switch( $plan->duration_type ){
                                case "day":
                                    if(  $plan->price === 0 ){
                                        $duration_plan = sprintf( _n( "<br/>Member access for %s day", "<br/>Member access for %s days", $plan->duration_value, $current_plugin_domain ), $plan->duration_value );
                                    }else{
                                        $duration_plan = sprintf( _n( " / day", " / %s days", $plan->duration_value, $current_plugin_domain ), $plan->duration_value ) ;
                                    }
                                    echo $duration_plan;
                                    break;
                                case "month":
                                    if(  $plan->price === 0 ){
                                        $duration_plan = sprintf( _n( "<br/>Member access for %s month", "<br/>Member access for %s months", $plan->duration_value, $current_plugin_domain ), $plan->duration_value );
                                    }else{
                                        $duration_plan = sprintf( _n( " / month", " / %s months", $plan->duration_value, $current_plugin_domain ), $plan->duration_value ) ;
                                    }
                                    echo $duration_plan;
                                    break;
                                case "year":
                                    if(  $plan->price === 0 ){
                                        $duration_plan = sprintf( _n( "<br/>Member access for %s year", "<br/>Member access for %s years", $plan->duration_value, $current_plugin_domain ), $plan->duration_value );
                                    }else{
                                        $duration_plan = sprintf( _n( " / year", " / %s years", $plan->duration_value, $current_plugin_domain ), $plan->duration_value ) ;
                                    }
                                    echo $duration_plan;
                                    break;
                                case "date":
                                    // TODO : generate date_format according to country
                                    $duration_plan = sprintf( __( "<br/>Member access until %s", $current_plugin_domain ), date("d/m/Y", strtotime( $plan->expiration_date ) ) );
                                    echo "<br/>";
                                    echo $duration_plan;
                                    break;
                                case "unlimited":
                                    $duration_plan = __( "<br/>Unlimited plan ", $current_plugin_domain );
                                    echo "<br/>";
                                    echo $duration_plan;
                                    break;
                            }
                            if($plan->plan_type === "multiple"){
                                $members_string = "";
                                if( (int) $plan->members_min > 2 && !empty( $plan->members_max ) ){
                                    $members_string .= sprintf ( __( "Between %d and %d user accounts authorized.", $current_plugin_domain ), $plan->members_min, $plan->members_max );
                                }else if( (int) $plan->members_min === 2 && !empty( $plan->members_max ) ){
                                    $members_string .= sprintf ( __( "Until %d user accounts authorized.", $current_plugin_domain ), $plan->members_max );
                                }else if( (int) $plan->members_min > 2 && empty( $plan->members_max ) ){
                                    $members_string .= sprintf ( __( "For %d user accounts and more.", $current_plugin_domain ), $plan->members_min );
                                }else if( (int) $plan->members_min === 2 && empty( $plan->members_max ) ){
                                    $members_string .= __( "Unlimited user account creation.", $current_plugin_domain );
                                }
                                echo '<br/><small class="wgs-info">'.$members_string.'</small>';
                            }
                            ?>
                        </p>
                        <?php
                        $href = FormPages::buildUrlAction( $plan->plan_name, 1 );
                        ?>
                        <button onclick="window.location.href='<?php echo $href; ?>'"><?php echo __("Sign up", $current_plugin_domain ) ;?></button>
                    </div>
                </div>
            </div>
            <?php
        }
    } ?>
    </section><?php
}
?>
	
