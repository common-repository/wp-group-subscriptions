<?php

use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\format_attrs;
use function H4APlugin\Core\get_current_plugin_domain;
use H4APlugin\WPGroupSubs\Common\Plan;

$current_plugin_domain = get_current_plugin_domain();

$plans = Plan::getPlans();

?>
<form id="wgs-admin-edit-subscriber-step-1"
      action="<?php echo wp_admin_build_url( 'edit-subscriber', false, null ); ?>"
      method="get">
    <div class="post-body-content">
		<?php
		$attr_input_hidden = array(
			'type' => 'hidden',
			'name' => 'page',
			'value' => 'edit-subscriber'
		);
		printf( '<input %s />', format_attrs( $attr_input_hidden ) );
		if( empty( $plans ) ){ ?>
            <table class="form-table widefat fixed">
                <tbody>
                <tr><td><?php _e( 'No published plan found, please publish a plan before adding new subscriber.', $current_plugin_domain ); ?></td></tr>
                </tbody>
            </table>
			<?php
		}else{
			?>
            <table class="form-table widefat fixed">
            <thead>
            <tr>
                <th><?php
					printf( __( "Step %d", $current_plugin_domain ), 1 );
					echo ' : ' . __( "Which plan ?", $current_plugin_domain );
					?></th>
            </tr>
            </thead>
            <tbody><?php
			echo '<tr>';
			if( count( $plans) < 4 ){

				foreach( $plans as $plan ){
					echo '<td>';
					$input_id = "wgs_subscriber_plan_" . $plan['plan_id'];
					$attr_input = array(
						'type' => "radio",
						'name' => "pl",
						'id' => $input_id,
						'class' => "wgs-subscriber-plan",
						'value' => $plan['plan_id'],
						'required' => "required"
					);
					$input = sprintf( '<input %s />', format_attrs( $attr_input ) );
					$attr_label    = array(
						'for' => $input_id,
					);
					printf( '<label %s >%s%s</label>', format_attrs( $attr_label ), $input, $plan['plan_name'] );
					echo '</td>';
				}
			}else{
				echo '<td><select id="wgs_plan" name="pl" required="required" >';
				printf( '<option value="" >%s</option>', __( "Please select a plan", $current_plugin_domain) );
				foreach( $plans as $plan ){
					$attr_input = array(
						'value' => $plan['plan_id']
					);
					printf( '<option %s >%s</option>', format_attrs( $attr_input ), $plan['plan_name'] );
				}
				echo '</select></td>';
			}
			echo '</tr>'; ?>
            <tr>
                <td><?php
					$button_text = sprintf( __( "Step %d", $current_plugin_domain ), 2 ) . ' : ' . __( "Form", $current_plugin_domain );
					$attr_button = array(
						"type" => "submit",
						"class" => "button button-primary"
					);
					printf( '<button %s>%s</button>', format_attrs( $attr_button ), $button_text ); ?>
                </td>
            </tr>
            </tbody>
            </table><?php } ?>
    </div>
</form>
