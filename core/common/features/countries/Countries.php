<?php /** @noinspection ALL */

namespace H4APlugin\Core\Common;


use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;

class Countries {

	public function __construct() {

	}

	public static function getCountries( $withPhoneCode = false ) {

		global $wpdb;

		// Start query string
		$countries_query_string        =  "SELECT iso, nicename";
		if( $withPhoneCode )
			$countries_query_string   .=  ", phonecode";
		$countries_query_string       .= " FROM {$wpdb->prefix}". get_current_plugin_prefix() ."countries";

		// Return results
		$a_countries       = $wpdb->get_results( $countries_query_string, ARRAY_A );
		$f_countries       = array();
		$current_plugin_domain = get_current_plugin_domain();
		$f_countries[null] =  __( 'Please select your country', $current_plugin_domain );
		if( $withPhoneCode ){
			foreach ($a_countries as $country){
				$f_countries[$country['phonecode']] = 	__( $country['nicename'], $current_plugin_domain );
			}
		}else{
			foreach ($a_countries as $country){
				$f_countries[$country['iso']] = __( $country['nicename'], $current_plugin_domain );
			}
		}

		$country_array = apply_filters( 'h4a_get_countries', $f_countries);

		return $country_array;

	}

	public static function getCountryIdByIso ( $countryIso ){

		global $wpdb;

		$results = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}". get_current_plugin_prefix() ."countries WHERE iso = '".$countryIso."';", ARRAY_A );

		if(count($results) === 1){
			return (int) $results[0]["id"];
		}else{
			return false;
		}

	}

	/**
	 * @param $countryId
	 *
	 * @return bool|string
	 */
	public static function getIsoByCountryId ( int $countryId ){

		global $wpdb;

		$results = $wpdb->get_results( "SELECT iso FROM {$wpdb->prefix}". get_current_plugin_prefix() ."countries WHERE id = ".$countryId.";", ARRAY_A );

		if(count($results) === 1){
			return (string) $results[0]["iso"];
		}else{
			return false;
		}

	}

}