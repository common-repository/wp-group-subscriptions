<?php

namespace H4APlugin\WPGroupSubs\Common;


use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_build_url;
use H4APlugin\WPGroupSubs\Shortcodes\MyProfileShortcode;

class Profile_Widget extends \WP_Widget {

	protected $current_plugin_domain;

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		$this->current_plugin_domain = get_current_plugin_domain();
		parent::__construct(
			'wgs_profile_widget', // Base ID
			'WGS Log In/Profile', // Name
			array( 'description' => __( "To display a login area for WGS users and access to their profile page.", $this->current_plugin_domain ) ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		// outputs the content of the widget
		?>
        <style>
            #wgs_profile-widget-login-nav > ul{
                display: flex;
                justify-content: flex-end;
                margin: 0;
                padding: 0;
                list-style: none outside;
            }
            #wgs_profile-widget-login-nav > ul > li{
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                position: absolute;
            }
            input#wgs_profile-widget-login-link{
                display:none
            }
            #wgs_profile-widget-login-label{
                cursor: pointer;
                justify-content: center;
                display: flex;
                height: 30px;
                line-height: 30px;
                border-radius: 5px;
                background-color: rgba( 0, 0, 0, 0.5 );
                z-index: 999;
                padding: 5px;
            }
            #wgs_profile-widget-login-label > *{
                height: inherit !important;
                line-height: 30px;
            }
            /*style for the second level menu*/
            #wgs_profile-widget-login-nav ul.submenu{
                display: flex;
                flex-direction: column;
                max-height:0;
                padding:0 !important;
                margin: 0 !important;
                overflow:hidden;
                list-style-type:none;
                box-shadow:0 0 1px rgba(0,0,0,.3);
                transition: max-height 0.5s ease-out, border 0.8s ease-out;
                min-width:100%;
                z-index: 999;
                border-radius: 5px;
                background: #fff;
                border: 0 solid transparent;
            }
            input#wgs_profile-widget-login-link:checked ~ ul.submenu{
                max-height:300px;
                border: 1px solid rgba(0,0,0,.5);
                transition: max-height 0.5s ease-in, border 0.2s ease-in;
            }
            #wgs_profile-widget-login-nav ul.submenu li{
                margin: 0!important;
            }

            #wgs_profile-widget-login-nav ul.submenu li a{
                display:block;
                padding:12px;
                color:#444;
                text-decoration:none;
                box-shadow:0 -1px rgba(0,0,0,.5) inset;
                transition:background .8s;
                white-space:nowrap;
            }
        </style>
        <nav id="wgs_profile-widget-login-nav">
            <ul><li>
					<?php
					if( Subscriber::isLoggedIn()  || Member::isLoggedIn() ) :
						if( Subscriber::isLoggedIn() ){
							$user_loggedIn = Subscriber::getSubscriberLoggedIn();
						}else{
							$user_loggedIn = Member::getMemberLoggedIn();
						}
						if( !$user_loggedIn ){
							if( Subscriber::isLoggedIn() ){
								Subscriber::logOut();
                            }else if( Member::isLoggedIn() ){
								Member::logOut();
							}
						}
					endif;

					if( !empty( $user_loggedIn ) ) :

						$profile_url = wp_build_url( "wgs-profile", H4A_WGS_PLUGIN_LABEL_MY_PROFILE );
						$signout_url = wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN, array( "sign" => "out") );
						?>
                        <input id="wgs_profile-widget-login-link" type="checkbox" name="menu" autocomplete="off"/>
                        <label id="wgs_profile-widget-login-label" for="wgs_profile-widget-login-link">
                            <i class="dashicons dashicons-admin-users"></i>
                            <span>
		                    <?php echo $user_loggedIn->first_name . " " .  $user_loggedIn->last_name; ?>
                            </span>
                        </label>
                        <ul class="submenu">
                            <li><a href="<?php echo $profile_url ; ?>"><?php _e( MyProfileShortcode::getProfilePageTitle(), $this->current_plugin_domain ) ?></a></li>
                            <li><a href="<?php echo $signout_url ; ?>"><?php _e( "Sign Out", $this->current_plugin_domain ) ?></a></li>
                        </ul>
					<?php else : ?>
                        <a id="wgs_profile-widget-login-label" href="<?php echo wp_build_url( "wgs-login", H4A_WGS_PLUGIN_LABEL_LOG_IN  ); ?>">
                            <i class="dashicons dashicons-admin-users"></i>
                            <span>
		                    <?php _e( "Sign In", $this->current_plugin_domain ); ?>
                            </span>
                        </a>
					<?php endif; ?>
                </li>
            </ul>
        </nav>
		<?php
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		// outputs the options form in the admin
		/*if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'My Profile', $this->current_plugin_domain );
		}
		*/?><!--
        <p>
            <label for="<?php /*echo esc_attr( $this->get_field_id( 'title' ) ); */?>"><?php /*esc_attr_e( 'Title:', $this->current_plugin_domain ); */?></label>
            <input class="widefat" id="<?php /*echo esc_attr( $this->get_field_id( 'title' ) ); */?>" name="<?php /*echo esc_attr( $this->get_field_name( 'title' ) ); */?>" type="text" value="<?php /*echo esc_attr( $title ); */?>">
        </p>-->
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		// processes widget options to be saved
	}

}