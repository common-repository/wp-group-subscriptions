<?php

use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\format_attrs;
use function H4APlugin\Core\get_current_plugin_domain;
use H4APlugin\WPGroupSubs\Common\Subscriber;

$group_names = Subscriber::getAllGroup();
$current_plugin_domain = get_current_plugin_domain();
?>

<form id="wgs-admin-edit-member-step-1"
      action="<?php echo wp_admin_build_url( 'edit-member', false, null ); ?>"
      method="get">
    <div class="post-body-content">
		<?php
		$attr_input_hidden = array(
			'type' => 'hidden',
			'name' => 'page',
			'value' => 'edit-member'
		);
		printf( '<input %s />', format_attrs( $attr_input_hidden ) );
		if( empty( $group_names ) ){ ?>
            <table class="form-table widefat fixed">
                <tbody>
                <tr><td><?php _e( 'No multiple plan subscription is found. You need group names before adding new member.', $current_plugin_domain ); ?></td></tr>
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
					echo ' : ' . __( "Which group name ?", $current_plugin_domain );
					?></th>
            </tr>
            </thead>
            <tbody><?php
			$f_group_names = array();
			foreach ( $group_names as $group_name ){
				$members = Subscriber::getAllMembersById( $group_name['subscriber_id'] );
				if( count( $members ) < $group_name['members_max'] ){
					$f_group_names[] = $group_name;
				}
			}
			if( count( $f_group_names ) === 0 ){
				echo '<tr><td>';
				_e( 'All groups reached the maximum of members.', $current_plugin_domain );
				echo '</td></tr>';
			}else{
				echo '<tr>';
			    if( count( $f_group_names ) < 4 ){

                    foreach( $f_group_names as $f_group_name ){
                        echo '<td>';
                        $input_id = 'wgs_subscriber_' . $f_group_name['subscriber_id'];
                        $attr_input = array(
                            'type' => 'radio',
                            'name' => 'subs',
                            'id' => $input_id,
                            'value' => $f_group_name['subscriber_id'],
                            'required' => 'required'
                        );
                        $input = sprintf( '<input %s />', format_attrs( $attr_input ) );
                        $attr_label    = array(
                            'for' => $input_id,
                        );
                        printf( '<label %s >%s%s</label>', format_attrs( $attr_label ), $input, $f_group_name['group_name'] );
                        echo '</td>';
                    }
                }else{
                    echo '<td><select id="wgs_group_name" name="subs" required="required">';
                    printf( '<option value="" >%s</option>', __( "Please select a group", $current_plugin_domain) );
                    foreach( $f_group_names as $f_group_name ){
                        $attr_input = array(
                            'value' => $f_group_name['subscriber_id']
                        );
                        printf( '<option %s >%s</option>', format_attrs( $attr_input ), $f_group_name['group_name'] );
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
            <?php } ?>
            </tbody>
        </table><?php } ?>
    </div>
</form>
