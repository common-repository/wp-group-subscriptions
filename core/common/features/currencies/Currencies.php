<?php

namespace H4APlugin\Core\Common;


use function H4APlugin\Core\get_current_plugin_domain;
use function H4APlugin\Core\get_current_plugin_prefix;
use function H4APlugin\Core\wp_get_symbol_currency;

class Currencies {

	public function __construct() {

	}

	public static function getCurrencies() {

		global $wpdb;

		// Start query string
		$currencies_query_string        =  "SELECT * FROM {$wpdb->prefix}". get_current_plugin_prefix() ."currencies;";

		// Return results
		$a_currencies   = $wpdb->get_results( $currencies_query_string, ARRAY_A );
		$f_currencies   = array();
		$current_plugin_domain         = get_current_plugin_domain();
		$f_currencies[] =  array(
			"iso" => null,
			"name" => __( 'Please select the currency', $current_plugin_domain )
		);
		foreach ($a_currencies as $currency){
			$f_currencies[] = array(
				"iso" => $currency['iso'],
				"name" => __( $currency['name'], $current_plugin_domain )
			);
		}

		return $f_currencies;

	}
	
	public static function format_string_price( $price, $currency, $position ){
		$symbol = wp_get_symbol_currency( $currency );
		$format_price = "";
		if( $position === "before")
			$format_price .=  $symbol;
		//TODO: option to show price for international
		$format_price .=  number_format( $price, 2, ',', ' ');
		if( $position === "after")
			$format_price .=  $symbol;
		return $format_price;
	}
}