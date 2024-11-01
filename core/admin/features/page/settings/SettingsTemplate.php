<?php
namespace H4APlugin\Core\Admin;

use function H4APlugin\Core\addHTMLinDOMDocument;
use H4APlugin\Core\Config;
use function H4APlugin\Core\format_str_from_kebabcase;
use function H4APlugin\Core\format_str_to_kebabcase;
use function H4APlugin\Core\get_current_plugin_dir_url;
use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\wp_admin_build_url;
use function H4APlugin\Core\wp_debug_log;

abstract class SettingsTemplate extends Template
{
	protected $current_plugin_domain;

	protected $page_slug;
    protected $a_hrefs = array();
    protected $setting_section_id;



    public function __construct( $data ){
	    $this->current_plugin_domain = get_current_plugin_domain();
	    parent::__construct( $data );
        $this->page_slug = $_GET['page'];
        $this->setting_section_id = H4A_SETTING_SECTION . $this->slug;
    }

    public function setTabsHeader(){

        global $wp_settings_sections;
        if ( ! isset( $wp_settings_sections[ $this->page_slug ] ) )
            return false;
	    $tab_headers = array();
        foreach ( (array) $wp_settings_sections[ $this->page_slug ] as $section ) {
            $this->a_hrefs[ format_str_to_kebabcase( $section['title'] ) ] = format_str_to_kebabcase( str_replace( "&", "&amp;", strtolower( $section['title'] ) ) );
            $section_slug = str_replace( H4A_SETTING_SECTION, "" , $section['id'] );
            $id_section = strtolower( format_str_to_kebabcase( $section['title'] ) . "-menu" );
            if ( $section['title'] ){
                $atts = array(
                    'class'    => "nav-tab",
                    'id'       => $id_section,
                    'href' => wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, array( "tab" => $section_slug ) )
                );
                $tab_header = array(
                    'atts' => $atts,
                    'title' => $section['title']
                );
                $tab_headers[] = $tab_header;
            }
        }
        return $tab_headers;
    }

    public function write( &$htmlTmpl ) {
        wp_debug_log();
        ob_start();
        /*global $wp_settings_fields;
        pretty_var_dump( $wp_settings_fields );*/
        echo '<form method="post" action="options.php">';

        // This prints out all hidden setting fields
        settings_fields( H4A_OPTIONS_GROUP );
        $this->do_settings_sections();
        submit_button();

        echo "</form>";

        $html = ob_get_clean();
        addHTMLinDOMDocument($htmlTmpl, $html, "form" );
    }

    /**
     * Same as do_settings_sections but with tabs
     */
    protected function do_settings_sections() {
        global $wp_settings_sections;

        if ( ! isset( $wp_settings_sections[$this->page_slug] ) )
            return;

        foreach ( (array) $wp_settings_sections[$this->page_slug] as $section ) {
            $section_slug = str_replace( H4A_SETTING_SECTION, "" , $section['id'] );
            global $wp_settings_fields;
            if( $section_slug === $this->slug ){
                printf( '<section class="h4a-option-tab" id="%s-content">', $this->a_hrefs[ format_str_to_kebabcase( $section['title'] ) ] );
                echo "";
                if ( $section['callback'] )
                    call_user_func( $section['callback'], $section );
                echo '<table class="form-table"><tbody>';
                do_settings_fields( $this->page_slug, $section['id'] );
                echo "</tbody></table>";
                $this->write_aside_content();
                echo "</section>";
            }else{
                foreach ( (array) $wp_settings_fields[$this->page_slug][$section['id']] as $field ) {
                	$options_name = Config::gen_options_name( $section_slug );
                    $wp_options = get_option($options_name);
	                $option_value = ( isset( $wp_options[ $field['id'] ] ) )  ? esc_attr( $wp_options[ $field['id'] ] ) : null;
	                printf(
                        '<input type="hidden" id="%s" name="%s" value="%s"/>',
                        $field['id'],
                        $options_name . "[" . $field['id'] . "]",
		                $option_value
                    );
                }
            }
        }
    }

    abstract protected function write_aside_content();

    public function set_template_scripts() {
        wp_debug_log();

        $this->set_tabs_scripts();
        $this->set_modal_scripts();

        $this->set_additional_scripts();

    }

    public static function getWGSSettingTranslation(){
	    $options_names =  Config::get_options_names( true );
	    $current_plugin_domain = get_current_plugin_domain();
	    if( !empty( $options_names ) ){
	    	$tabs = array();
		    foreach ( $options_names as $options_name ){
			    $tabs[ $options_name ] = __( $options_name, $current_plugin_domain );
		    }
		    return array(
			    'tabs' => $tabs,
			    'modal_title' => "WP Group Subscription Premium",
			    'notice' => __( "This option is not active yet.", $current_plugin_domain ),
			    'modal_explanation_begin' => __( "This option is a Premium version feature. It's included in ... ", $current_plugin_domain ),
			    'modal_subtitle_addon' => __( "addon", $current_plugin_domain ),
			    'url_assets' => get_current_plugin_dir_url() . "assets/",
			    'modal_subtitle' => __( "Get this option in a 3 steps", $current_plugin_domain ),
			    'modal_button_premium' => __( "Get the premium version", $current_plugin_domain ),
			    'modal_txt_insert_key' => __( "Insert your license key.", $current_plugin_domain ),
			    'modal_txt_enjoy_settings' => __( "Enjoy with the new settings!", $current_plugin_domain )
	        );
	    }
    	return null;
    }

    abstract public function set_additional_scripts();
}