<?php /** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpIncludeInspection */

namespace H4APlugin\Core;

use H4APlugin\Core\Common\Notices;

/**
 * Table of Contents
 *
 * 1.0  - Strings
 * 2.0  - Numbers
 * 3.0  - Arrays & Objects
 * 4.0  - Date & time
 * 5.0  - Urls & paths
 * 6.0  - Files & Directories
 * 7.0  - Errors
 * 8.0  - Tokens
 * 9.0  - XML
 * 10.0 - License
 * -----------------------------------------------------------------------------
 */

/**
 * 1.0 - Strings
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\\format_str_to_kamelcase" ) ) {
	function format_str_to_kamelcase( $str ) {
		$str   = str_replace( " ", "§", $str );
		$str   = str_replace( "_", "§", $str );
		$str   = str_replace( "-", "§", $str );
		$a_str = explode( "§", $str );
		$str   = "";
		foreach ( $a_str as $cut_str ) {
			$str .= ucfirst( strtolower( $cut_str ) );
		}

		return $str;
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_to_kebabcase" ) ) {
	function format_str_to_kebabcase( $str, $no_accents = false, $utf8_encode = false ) {
		if ( $no_accents ) {
			$str = format_str_no_accents( $str, $utf8_encode );
		}
		$str = strtolower( str_replace( ".", "", $str ) );
		$str = strtolower( str_replace( " - ", " ", $str ) );
		$str = strtolower( str_replace( " ", "-", $str ) );

		return $str;
	}
}

if( !function_exists( "H4APlugin\Core\\format_kamelcase_to_kebabcase" ) ) {
	function format_kamelcase_to_kebabcase( $str ) {
		return uncamelize( $str, $splitter="-" ) ;
	}
}

if( !function_exists( "H4APlugin\Core\\format_kamelcase_to_kebabcase" ) ) {
	function format_kamelcase_to_underscorecase( $str ) {
		return uncamelize( $str ) ;
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_to_underscorecase" ) ) {
	function format_str_to_underscorecase( $str ) {
		return strtolower( str_replace( " ", "_", $str ) );
	}
}

if( !function_exists( "H4APlugin\Core\\uncamelize" ) ) {
	function uncamelize( $camel, $splitter="_" ) {
		$camel = preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', $splitter.'$0', $camel));
		return strtolower( $camel );

	}
}

if( !function_exists( "H4APlugin\Core\\format_str_from_underscorecase" ) ) {
	function format_str_from_underscorecase( $str, $is_uc_first = true ) {
		$str_replace = str_replace( "_", " ", $str );
		return ( $is_uc_first ) ? ucfirst( $str_replace ) : $str_replace;
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_from_kebabcase" ) ) {
	function format_str_from_kebabcase( $str, $is_uc_first = true ) {
		$str_replace = str_replace( "-", " ", $str );
		return ( $is_uc_first ) ? ucfirst( $str_replace ) : $str_replace;
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_capitalize_first_letter" ) ) {
	function format_str_capitalize_first_letter( $str ) {
		return ucfirst( strtolower( $str ) );
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_to_display" ) ) {
	function format_str_to_display( $str ) {
		return strtoupper( stripslashes( format_str_no_accents( $str ) ) );
	}
}

if( !function_exists( "H4APlugin\Core\\format_str_no_accents" ) ) {
	function format_str_no_accents( $str, $utf8_encode = false ) {
		if( $utf8_encode )
			$str = utf8_encode( $str );
		/*$unwanted_array = array(    'Š'=>"S", 'š'=>"s", 'Ž'=>"Z", 'ž'=>"z", 'À'=>"A", 'Á'=>"A", 'Â'=>"A", 'Ã'=>"A", 'Ä'=>"A", 'Å'=>"A", 'Æ'=>"A", 'Ç'=>"C", 'È'=>"E", 'É'=>"E",
									'Ê'=>"E", 'Ë'=>"E", 'Ì'=>"I", 'Í'=>"I", 'Î'=>"I", 'Ï'=>"I", 'Ñ'=>"N", 'Ò'=>"O", 'Ó'=>"O", 'Ô'=>"O", 'Õ'=>"O", 'Ö'=>"O", 'Ø'=>"O", 'Ù'=>"U",
									'Ú'=>"U", 'Û'=>"U", 'Ü'=>"U", 'Ý'=>"Y", 'Þ'=>"B", 'ß'=>"Ss", 'à'=>"a", 'á'=>"a", 'â'=>"a", 'ã'=>"a", 'ä'=>"a", 'å'=>"a", 'æ'=>"a", 'ç'=>"c",
									'è'=>"e", 'é'=>"e", 'ê'=>"e", 'ë'=>"e", 'ì'=>"i", 'í'=>"i", 'î'=>"i", 'ï'=>"i", 'ð'=>"o", 'ñ'=>"n", 'ò'=>"o", 'ó'=>"o", 'ô'=>"o", 'õ'=>"o",
									'ö'=>"o", 'ø'=>"o", 'ù'=>"u", 'ú'=>"u", 'û'=>"u", 'ý'=>"y", 'þ'=>"b", 'ÿ'=>"y" );*/
		$str = str_replace(
			array(
				"Š", //1
				"š", //2
				"Ž", //3
				"ž", //4
				"À", //5
				"Á", //6
				"Â", //7
				"Ã", //8
				"Ä", //9
				"Å", //10
				"Æ", //11
				"Ç", //12
				"È", //13
				"É", //14
				"Ê", //15
				"Ë", //16
				"Ì", //17
				"Í", //18
				"Î", //19
				"Ï", //20
				"Ñ", //21
				"Ò", //22
				"Ó", //23
				"Ô", //24
				"Õ", //25
				"Ö", //26
				"Ø", //27
				"Ù", //28
				"Ú", //29
				"Û", //30
				"Ü", //31
				"Ý", //32
				"Þ", //33
				"ß", //34
				"à", //35
				"á", //36
				"â", //37
				"ã", //38
				"ä", //39
				"å", //40
				"æ", //41
				"ç", //42
				"è", //43
				"é", //44
				"ê", //45
				"ë", //46
				"ì", //47
				"í", //48
				"î", //49
				"ï", //50
				"ð", //51
				"ñ", //52
				"ò", //53
				"ó", //54
				"ô", //55
				"õ", //56
				"ö", //57
				"ø", //58
				"ù", //59
				"ú", //60
				"û", //61
				"ý", //62
				"þ", //63
				"ÿ"  //64
			),
			array(
				"S", //1
				"s", //2
				"Z", //3
				"z", //4
				"A", //5
				"A", //6
				"A", //7
				"A", //8
				"A", //9
				"A", //10
				"A", //11
				"C", //12
				"E", //13
				"E", //14
				"E", //15
				"E", //16
				"I", //17
				"I", //18
				"I", //19
				"I", //20
				"N", //21
				"O", //22
				"O", //23
				"O", //24
				"O", //25
				"O", //26
				"O", //27
				"U", //28
				"U", //29
				"U", //30
				"U", //31
				"Y", //32
				"B", //33
				"Ss",//34
				"a", //35
				"a", //36
				"a", //37
				"a", //38
				"a", //39
				"a", //40
				"a", //41
				"c", //42
				"e", //43
				"e", //44
				"e", //45
				"e", //46
				"i", //47
				"i", //48
				"i", //49
				"i", //50
				"o", //51
				"n", //52
				"o", //53
				"o", //54
				"o", //55
				"o", //56
				"o", //57
				"o", //58
				"u", //59
				"u", //60
				"u", //61
				"y", //62
				"b", //63
				"y"  //64
			),
			$str
		);
		return $str;
	}
}
if( !function_exists( "H4APlugin\Core\\backward_explode" ) ) {
	function backward_explode($delimiter, $string, $limit = null, $keep_order = true) {
		if ((string)$delimiter === "") {
			return false;
		}

		if ($limit === 0 || $limit === 1) {
			return array($string);
		}

		$explode = explode($delimiter, $string);

		if ($limit === null || $limit === count($explode)) {
			return $keep_order? $explode : array_reverse($explode);
		}

		$parts = array();

		if ($limit > 0) {
			for ($i = 1; $i < $limit; $i++) {
				$parts[] = array_pop($explode);
			}
			$remainder = implode($delimiter, $explode);
			$parts[] = $remainder;
			if ($keep_order) {
				$parts = array_reverse($parts);
			}
		} else {
			if (strpos($string, $delimiter) === false) {
				return array();
			}
			$parts = $explode;
			array_splice($parts, 0, abs($limit));
			if (!$keep_order) {
				$parts = array_reverse($parts);
			}
		}

		return $parts;
	}
}

if( !function_exists( "H4APlugin\Core\\format_attrs" ) ) {
	/**
	 * @param array $attrs
	 *
	 * @return string
	 */
	function format_attrs( array $attrs ) {
		$html = '';

		$prioritized_attrs = array( "type", "name", "value" );

		foreach ( $prioritized_attrs as $att ) {
			if ( isset( $attrs[ $att ] ) ) {
				$value = trim( $attrs[ $att ] );
				$html  .= sprintf( ' %s="%s"', $att, esc_attr( $value ) );
				unset( $attrs[ $att ] );
			}
		}

		foreach ( $attrs as $key => $value ) {
			$key = strtolower( trim( $key ) );

			if ( ! preg_match( "/^[a-z_:][a-z_:.0-9-]*$/", $key ) ) {
				continue;
			}

			$value = trim( $value );

			if ( "" !== $value ) {
				$html .= sprintf( ' %s="%s"', $key, esc_attr( $value ) );
			}
		}
		$html = trim( $html );

		return $html;
	}
}

if( !function_exists( "H4APlugin\Core\asBoolean" ) ) {
	function asBoolean( $str ) {
		if ( $str === "true" ) {
			return true;
		} else if ( $str === "false" ) {
			return false;
		} else {
			return null;
		}
	}
}

if( !function_exists( "H4APlugin\Core\startsWith" ) ) {
	function startsWith( $haystack, $needle ) {
		// search backwards starting from haystack length characters from the end
		return $needle === "" || strrpos( $haystack, $needle, - strlen( $haystack ) ) !== false;
	}
}

if( !function_exists( "H4APlugin\Core\\endsWith" ) ) {
	function endsWith( $haystack, $needle ) {
		// search forward starting from end minus needle length characters
		return $needle === "" || ( ( $temp = strlen( $haystack ) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false );
	}
}

/**
 * 2.0 - Numbers
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\is_number" ) ) {
	function is_number( $nbr ){
		$locale= get_locale();
		setlocale( LC_NUMERIC, $locale );
		return ( is_numeric( $nbr ) || is_float( $nbr ) );
	}
}

if( !function_exists( "H4APlugin\Core\is_float_as_string" ) ) {
	function is_float_as_string( $nbr ) {
		if ( is_numeric( $nbr ) ) {
			$point = ".";
			if ( strpos( $nbr, $point ) !== false ) {
				$nbr = (float) $nbr;
			}

			return is_float( $nbr );
		}

		return false;
	}
}

/**
 * 3.0 - Arrays & Objects
 * -----------------------------------------------------------------------------
 */
if( !function_exists( "H4APlugin\Core\\flatten_array" ) ) {
	function flatten_array( array $array ) {
		$return = array();
		array_walk_recursive( $array, function ( $a ) use ( &$return ) {
			$return[] = $a;
		} );

		return $return;
	}
}

if( !function_exists( "H4APlugin\Core\pretty_var_dump" ) ) {
	function pretty_var_dump( array $array ) {
		echo "<pre>" . var_export( $array, true ) . "</pre>";
	}
}

if( !function_exists( "H4APlugin\Core\ksort_r" ) ) {
	function ksort_r( array &$array ) {   /* ksort() for multi-dimensional array*/
		ksort( $array );
		foreach ( $array as $key => &$item ) {
			if ( is_array( $item ) ) {
				ksort_r( $item );
			}
		}
	}
}

if( !function_exists( "H4APlugin\Core\kvsearch_r" ) ) {
	function kvsearch_r( $array, $key, $value ) {
		$results = array();

		if ( is_array( $array ) ) {
			if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) {
				$results[] = $array;
			}

			foreach ( $array as $subarray ) {
				$results = array_merge( $results, kvsearch_r( $subarray, $key, $value ) );
			}
		}

		return $results;
	}
}

if( !function_exists( "H4APlugin\Core\get_defined_constants_by_filter" ) ) {
	function get_defined_constants_by_filter( $filter ){
		$output = array();

		$constants = get_defined_constants(TRUE)['user'];
		foreach ( $constants as $name => $value )
		{

			if ( startsWith( $name, $filter ) )
			{
				$output[ $name ] = $value;
			}

		}
		return $output;
	}
}

if( !function_exists( "H4APlugin\Core\is_assoc" ) ) {
	function is_assoc( array $arr ) {
		if ( array() === $arr ) {
			return false;
		}

		return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
	}
}

if( !function_exists( "H4APlugin\Core\is_multi" ) ) {
	function is_multi( $a ) {
		$rv = array_filter( $a, 'is_array' );
		if ( count( $rv ) > 0 ) {
			return true;
		}

		return false;
	}
}

if( !function_exists( "H4APlugin\Core\sortBy" ) ) {
	function sortBy( $field, &$array, $direction = "asc" ) {
		$cmp = function ( $a, $b ) use ( $field, $direction ) {
			$a = $a[ $field ];
			$b = $b[ $field ];
			if ( $a == $b ) {
				return 0;
			}

			$direction = strtolower( trim( $direction ) );
			if ( $direction === "desc" ) {
				$output = ( $a > $b ) ? - 1 : 1;
			} else {
				$output = ( $a < $b ) ? - 1 : 1;
			}

			return $output;
		};
		usort( $array, $cmp );

		return true;
	}
}

/**
 * @param $serializeArray
 * @param $sub_array
 * @return mixed
 */
if( !function_exists( "H4APlugin\Core\count_values_by_key" ) ) {
	function count_values_by_key( $array, $value, $key_to_search ) {
		$values_by_column = array_column( $array, $key_to_search );
		$counts           = array_count_values( $values_by_column );

		return $counts[ $value ];
	}
}

/**
 * 4.0 - Date & time
 * ----------------------------------------------------------------------------


/**
 * @param $date
 * @param string $format
 *
 * @return bool
 */
if( !function_exists( "H4APlugin\Core\is_datetime" ) ) {
	function is_datetime( $date, $format = "Y-m-d H:i:s" ) {
		$d = \DateTime::createFromFormat( $format, $date );

		return $d && $d->format( $format ) == $date;
	}
}

/**
 * @param string $start_date
 * @param int $number
 * @param string $format
 *
 * @return bool|string - answer or string with error
 */
if( !function_exists( "H4APlugin\Core\is_date_expired_by_duration" ) ) {
	function is_date_expired_by_duration( $start_date, $number = 0, $format = null ) {
		if ( ! is_datetime( $start_date ) ) {
			return "Invalid start date : " . $start_date;
		}
		if ( ! is_int( (int) $number ) ) {
			return "Invalid number : " . $number;
		}
		if ( ! in_array( $format, array( "day", "month", "year" ) ) ) {
			return "Invalid date format : " . $format;
		}
		$start_time      = strtotime( $start_date );
		$expiration_date = date( "Y-m-d", strtotime( "+" . $number . $format, $start_time ) );

		return is_date_expired_by_date( $expiration_date );
	}
}

/**
 * @param $expiration_date | datetime as string like yyyy-mm-dd
 *
 * @return bool
 */

if( !function_exists( "H4APlugin\Core\is_date_expired_by_date" ) ) {
	function is_date_expired_by_date( $expiration_date ) {
		$today = time();
		if ( ! is_datetime( $expiration_date ) ) {
			$expiration_date = date( "Y-m-d", strtotime( $expiration_date ) );
		}
		$expiration_time = strtotime( $expiration_date );
		if ( isToday( $expiration_time ) ) {
			return false;
		} else {
			$diff = $expiration_time - $today;

			return ( $diff > 0 ) ? false : true;
		}
	}
}

if( !function_exists( "H4APlugin\Core\isToday" ) ) {
	function isToday( $time ) {
		return ( $time === strtotime( "today" ) );
	}
}

if( !function_exists( "H4APlugin\Core\\return_datetime" ) ) {
	function return_datetime( $utc_datetime, $format = "Y-m-d H:i:s" ) {
		$timezone_date = convert_datetime_with_timezone( $utc_datetime );

		return ( ! $timezone_date ) ? $utc_datetime . " ( UTC )" : $timezone_date->format( $format );
	}
}

if( !function_exists( "H4APlugin\Core\convert_datetime_with_timezone" ) ) {
	function convert_datetime_with_timezone( $utc_datetime ) {

		if ( isset( $_COOKIE['h4a_timezone'] ) ) {
			$tz       = new \DateTimeZone( $_COOKIE['h4a_timezone'] );
			$datetime = new \DateTime( $utc_datetime );
			$datetime->setTimezone( $tz );

			return $datetime;
		} else {
			return false;
		}
	}
}

if( !function_exists( "H4APlugin\Core\get_today_as_datetime" ) ) {
	function get_today_as_datetime( $format = "Y-m-d H:i:s" ) {
		date_default_timezone_set( "UTC" );
		$dt = new \DateTime( "now" );

		return $dt->format( $format );
	}
}

if( !function_exists( "H4APlugin\Core\getTimeTypes" ) ) {
	function getTimeTypes() {
		$current_plugin_domain = get_current_plugin_domain();
		$a_timeTypes = array(
			'year'  => __( "Year(s)", $current_plugin_domain ),
			'month' => __( "Month(s)", $current_plugin_domain ),
			'day'   => __( "Day(s)", $current_plugin_domain )
		);

		return $a_timeTypes;
	}
}

/**
 * 5.0 - Urls & paths
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\get_protocol" ) ) {
	function get_protocol( $url ) {
		$a_url = explode( "://", $url );

		return $a_url[0];
	}
}

if( !function_exists( "H4APlugin\Core\is_https" ) ) {
	function is_https() {
		$is_secure = false;
		if ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ) {
			$is_secure = true;
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == "https" || ! empty( $_SERVER['HTTP_X_FORWARDED_SSL'] ) && $_SERVER['HTTP_X_FORWARDED_SSL'] == "on" ) {
			$is_secure = true;
		}
		return $is_secure;
	}
}

if( !function_exists( "H4APlugin\Core\getCalledClassWihtoutNamespace" ) ) {
	function getCalledClassWihtoutNamespace( $called_class ) {
		$path = explode( "\\", $called_class );

		return array_pop( $path );
	}
}


/**
 * 6.0 - Files & Directories
 * -----------------------------------------------------------------------------
 */
if( !class_exists( "H4APlugin\Core\FilesystemRegexFilter" ) ) {
	abstract class FilesystemRegexFilter extends \RecursiveRegexIterator {
		protected $regex;
		public function __construct( \RecursiveIterator $it, $regex) {
			$this->regex = $regex;
			parent::__construct($it, $regex);
		}
	}
}

if( !class_exists( "H4APlugin\Core\FilenameFilter" ) ) {
	class FilenameFilter extends FilesystemRegexFilter {
		// Filter files against the regex
		public function accept() {
			return ( ! $this->isFile() || preg_match( $this->regex, $this->getFilename() ) );
		}
	}
}

if( !class_exists( "H4APlugin\Core\DirnameFilter" ) ) {
	class DirnameFilter extends FilesystemRegexFilter {
		// Filter directories against the regex
		public function accept() {
			return ( ! $this->isDir() || preg_match( $this->regex, $this->getFilename() ) );
		}
	}
}

if( !function_exists( "H4APlugin\Core\include_dir_r" ) ) {
	/**
	 * @param $dir_path
	 * @param string $regex_filename_filter
	 * @param bool $exclude_underscore
	 *
	 * @return mixed
	 */
	function include_dir_r( $dir_path, $regex_filename_filter = "", $exclude_underscore = true ) {
		if( is_dir_empty( $dir_path ) )
			return false;
		if ( $exclude_underscore ) {
			$dir_filter = "/^(?!_)/";
			$file_filter = "/^(?!_).*(\.php)$/";
		} else {
			$dir_filter = null;
			$file_filter = null;
		}
		$files = get_subdir_filtered( $dir_path, $dir_filter, $file_filter );
		if ( isset( $files ) ) {
			foreach ( $files as $name => $object ) {
				if ( $object->getFilename() !== "." && $object->getFilename() !== ".." ) {
					if ( ! is_dir( $name ) ) {
						if ( ! empty( $regex_filename_filter ) ) {
							$delimiter = "/";
							$name = str_replace ( "\\", $delimiter , $name );
							$a_name  = explode( $delimiter, $name );
							$subject = $a_name[ count( $a_name ) - 1 ];
							if ( preg_match( $regex_filename_filter, $subject ) ) {
								include_once $name;
							}
						} else {
							include_once $name;
						}
					}
				}
			}
		}
		return false;
	}
}

if( !function_exists( "H4APlugin\Core\get_dir_content_to_array" ) ) {
	function get_dir_content_to_array( string $dir_path, string $dir_filter = null, string $file_filter = null ) {
		if( is_dir_empty( $dir_path ) )
			return false;
		$output = array();
		$files = get_subdir_filtered( $dir_path, $dir_filter, $file_filter );
		if ( isset( $files ) ) {
			$paths = [];
			$f = 1;
			foreach ( $files as $name => $object ) {
				if ( $object->getFilename() !== "." && $object->getFilename() !== ".." ) {
					// Split by the delimiter.
					$delimiter       = "/";
					$name            = str_replace( "\\", $delimiter, $name );
					$relative_path   = str_replace( $dir_path, "", $name );
					$a_relative_path = explode( $delimiter, $relative_path );
					$path = array_pop($a_relative_path);
					foreach ( array_reverse( $a_relative_path ) as $pathPart ) {
						$path = [ "_" . $pathPart => $path ];
					}
					// Add it to a temp list.
					//wp_debug_log( serialize( $path ) );
					$paths[] = (array) $path;
					$f++;
				}
				//$output = array_merge( $paths[0], $paths[1], $paths[2] );
				$output = call_user_func_array( 'array_merge_recursive', $paths );
			}
		}
		return $output;
	}
}

if( !function_exists( "H4APlugin\Core\get_subdir_filtered" ) ) {
	/**
	 * @param $dir_path
	 * @param $dir_filter
	 * @param $file_filter
	 *
	 * @return \RecursiveIteratorIterator|null
	 */
	function get_subdir_filtered( $dir_path, $dir_filter, $file_filter ) {
		$path      = realpath( $dir_path );
		$directory = new \RecursiveDirectoryIterator( $path );
		$files = null;
		if ( ! empty( $dir_filter ) || ! empty( $file_filter ) ) {
			if ( ! empty( $dir_filter ) ) {
				$filter = new DirnameFilter( $directory, $dir_filter );
			}
			if ( ! empty( $file_filter ) ) {
				$filter = new FilenameFilter( $filter, $file_filter );
			}
			$files = new \RecursiveIteratorIterator( $filter );
		} else {
			$files = new \RecursiveIteratorIterator( $directory );
		}
		return $files;
	}
}

if( !function_exists( "H4APlugin\Core\go_back_path" ) ) {
	/**
	 * @param $path
	 *
	 * @return string
	 */
	function go_back_path( &$path ): string {
		$path = rtrim( $path, "/");
		$a_path = explode( "/", $path );
		unset( $a_path[ count( $a_path ) - 1 ] );
		$path = "";
		foreach ( $a_path as $partPath ) {
			$path .= $partPath . "/";
		}

		return $path;
	}
}

if( !function_exists( "H4APlugin\Core\is_dir_empty" ) ) {
	function is_dir_empty( $dir_path ) {
		return !( new \FilesystemIterator( $dir_path ) )->valid();
	}
}

// $array_root_tags
// ex 1 : array( "tag", "tag2" ) to add without merging;
// ex 1 : array( 'tag' => "attr1", 'tag2' => "attr2" ) to add and merge based on common attribute;
if( !function_exists( "H4APlugin\Core\array_merge_xml" ) ) {
	function array_merge_xml( $array_xml_paths, $array_root_tags, $output_filename ) {
		$doc_ref                     = new \DOMDocument();
		$doc_ref->preserveWhiteSpace = false;
		$doc_ref->formatOutput       = true;
		$doc_ref->load( $array_xml_paths[0] );
		$keys_root_tags = array_keys( $array_root_tags );
		if ( $keys_root_tags === array_filter( $keys_root_tags, 'is_int' ) ) {
			//simple merge
			for ( $x = 1; $x < count( $array_xml_paths ); $x ++ ) {
				$doc_current = new \DOMDocument();
				$doc_current->load( $array_xml_paths[ $x ] );
				foreach ( $array_root_tags as $root_tag ) {
					$res1   = $doc_ref->getElementsByTagName( $root_tag . "s" )->item( 0 ); //edited res - items
					$items2 = $doc_current->getElementsByTagName( $root_tag );
					error_log_array( $doc_ref->getElementsByTagName( $root_tag ) );
					for ( $i = 0; $i < $items2->length; $i ++ ) {
						$item2 = $items2->item( $i );
						// import/copy item from document 2 to document 1
						$item1 = $doc_ref->importNode( $item2, true );

						// append imported item to document 1 'res' element
						$res1->appendChild( $item1 );

					}
				}
			}
		} else {
			//complex merge
			$xpath_ref = new \DOMXPath( $doc_ref );
			$ns_ref    = getNamespaceDOMXPath( $doc_ref, $xpath_ref );

			for ( $x = 1; $x < count( $array_xml_paths ); $x ++ ) {
				$doc_current = new \DOMDocument();
				$doc_current->load( $array_xml_paths[ $x ] );
				$doc_current->preserveWhiteSpace = false;
				$doc_current->formatOutput       = true;
				$xpath_current                   = new \DOMXPath( $doc_current );
				$ns_current                      = getNamespaceDOMXPath( $doc_current, $xpath_current );
				foreach ( $array_root_tags as $root_tag => $root_attr ) {
					$expression_ref = sprintf( "//%s%s", $ns_ref, $root_tag );
					$a_bind_attr    = array();
					foreach ( $xpath_ref->evaluate( $expression_ref ) as $tag_ref ) {
						if( $tag_ref instanceof \DOMElement )
							$a_bind_attr[] = (string) $tag_ref->getAttribute( $root_attr );
					}
					$expression_current = sprintf( "//%s%s", $ns_current, $root_tag );
					foreach ( $xpath_current->evaluate( $expression_current ) as $tag_current ) {
						if( $tag_current instanceof \DOMElement ){
							$val_attr_current = (string) $tag_current->getAttribute( $root_attr );
							if ( in_array( $val_attr_current, $a_bind_attr ) ) {
								// 1. update/add attributes
								$all_attrs_expression_current = sprintf( "//%s%s[@%s='%s']/@*", $ns_current, $root_tag, $root_attr, $val_attr_current );
								foreach ( $xpath_current->evaluate( $all_attrs_expression_current ) as $attribute ) {
									$all_attrs_expression_ref = sprintf( "//%s%s[@%s='%s']", $ns_ref, $root_tag, $root_attr, $val_attr_current );
									foreach ( $xpath_ref->evaluate( $all_attrs_expression_ref ) as $tag_target ) {
										if( $tag_target instanceof \DOMElement )
											$tag_target->setAttribute( $attribute->name, $attribute->value );
									}
								}
								// 2. add Children nodes
								$all_parent_expression = sprintf( "//%s%s[@%s='%s'][1]", $ns_ref, $root_tag, $root_attr, $val_attr_current );
								foreach ( $xpath_ref->evaluate( $all_parent_expression ) as $parent ) {
									$firstChild = $parent->firstChild;
								}
								foreach ( $tag_current->childNodes as $childNode ) {
									if ( $childNode->nodeType === XML_ELEMENT_NODE
									     && isset( $firstChild ) && $firstChild->nodeType === XML_ELEMENT_NODE ) {
										$item = $doc_ref->importNode( $childNode, true );
										if( isset( $parent ) && $parent instanceof \DOMElement )
											$parent->appendChild( $item );
										/*$parent->insertBefore(
											$item,
											$firstChild
										);*/
									}
								}
							} else {
								// "add " . $root_tag . "->" . $root_attr . " : " . $val_attr_current
								if( isset( $tag_ref ) ){
									$parent_node = $tag_ref->parentNode;
									$copyNode    = $doc_ref->importNode( $tag_current, true );
									$parent_node->appendChild( $copyNode );
								}else{
									error_log( "The var tag_ref is not defined." );
								}
							}
						}
					}
				}
			}
		}
		$doc_ref->save( $output_filename );
	}
}

if( !function_exists( "H4APlugin\Core\getNamespaceDOMXPath" ) ) {
	function getNamespaceDOMXPath( $doc_ref, $xpath_ref ) {
		$namespace_ref = $doc_ref->documentElement->namespaceURI;
		if ( $namespace_ref ) {
			$prefix_ns = "ns";
			if( $xpath_ref instanceof \DOMXPath )
				$xpath_ref->registerNamespace( $prefix_ns, $namespace_ref );

			return $prefix_ns . ":";
		} else {
			return "";
		}

	}
}

if( !function_exists( "H4APlugin\Core\get_mime_type" ) ) {
	function get_mime_type( $file_ext ) {
		$mimet = array(
			'txt'  => 'text/plain',
			'htm'  => 'text/html',
			'html' => 'text/html',
			'php'  => 'text/html',
			'css'  => 'text/css',
			'js'   => 'application/javascript',
			'json' => 'application/json',
			'xml'  => 'application/xml',
			'swf'  => 'application/x-shockwave-flash',
			'flv'  => 'video/x-flv',

			// images
			'png'  => 'image/png',
			'jpe'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg'  => 'image/jpeg',
			'gif'  => 'image/gif',
			'bmp'  => 'image/bmp',
			'ico'  => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif'  => 'image/tiff',
			'svg'  => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip'  => 'application/zip',
			'rar'  => 'application/x-rar-compressed',
			'exe'  => 'application/x-msdownload',
			'msi'  => 'application/x-msdownload',
			'cab'  => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3'  => 'audio/mpeg',
			'qt'   => 'video/quicktime',
			'mov'  => 'video/quicktime',

			// adobe
			'pdf'  => 'application/pdf',
			'psd'  => 'image/vnd.adobe.photoshop',
			'ai'   => 'application/postscript',
			'eps'  => 'application/postscript',
			'ps'   => 'application/postscript',

			// ms office
			'doc'  => 'application/msword',
			'rtf'  => 'application/rtf',
			'xls'  => 'application/vnd.ms-excel',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'docx' => 'application/msword',
			'xlsx' => 'application/vnd.ms-excel',
			'pptx' => 'application/vnd.ms-powerpoint',


			// open office
			'odt'  => 'application/vnd.oasis.opendocument.text',
			'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		if ( isset( $mimet[ $file_ext ] ) ) {
			return $mimet[ $file_ext ];
		} else {
			return 'application/octet-stream';
		}
	}
}
/**
 * 7.0 - Errors
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\\error_log_array" ) ) {
	function error_log_array( $array ) {
		error_log( print_r( $array, 1 ) );
	}
}

/**
 * 8.0 - Tokens
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\getToken" ) ) {
	/**
	 * @param $length
	 *
	 * @return string
	 * @throws \Exception
	 */
	function getToken( $length ) {
		$token        = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet .= "0123456789";
		$max          = strlen( $codeAlphabet ); // edited

		for ( $i = 0; $i < $length; $i ++ ) {
			$token .= $codeAlphabet[ random_int( 0, $max - 1 ) ];
		}

		return $token;
	}
}

/**
 * 9.0 - XML DOMDocument
 * -----------------------------------------------------------------------------
 */
if( !function_exists( "H4APlugin\Core\xmlAsStringToArray" ) ) {
	function xmlAsStringToArray( $str_xml, $keep_order = false ) {
		if ( ! $keep_order ) {
			$xml    = simplexml_load_string( $str_xml, "SimpleXMLElement", LIBXML_NOCDATA );
			$json   = json_encode( $xml );
			$output = json_decode( $json, true );
		} else {
			$xml    = simplexml_load_string( $str_xml );
			$output = recursiveSimpleXMLToArray( $xml );
		}

		return $output;
	}
}

if( !function_exists( "H4APlugin\Core\\recursiveSimpleXMLToArray" ) ) {
	function recursiveSimpleXMLToArray( \SimpleXMLElement $simpleXml ) {
		$output = array( 'type' => $simpleXml->getName() );
		if ( ! empty( $simpleXml->attributes() ) ) {
			$attributes            = json_decode( json_encode( $simpleXml->attributes() ), true );
			$output['@attributes'] = $attributes['@attributes'];
		}
		$value = json_decode( json_encode( $simpleXml ), true );
		if ( ! empty( $value ) && is_array( $value ) && isset( $value[0] ) && is_string( $value[0] ) ) {
			$output['value'] = $value[0];
		}
		$position           = 0;
		$output['children'] = array();
		foreach ( $simpleXml->children() as $xml_node ) {
			$output['children'][ $position ] = recursiveSimpleXMLToArray( $xml_node );
			$position ++;
		}
		if ( empty( $output['children'] ) ) {
			unset( $output['children'] );
		}

		return $output;
	}
}

if( !function_exists( "H4APlugin\Core\\recursiveSimpleXMLToArray" ) ) {
	function f_xml( &$array ) {
		foreach ( $array as &$myString ) {
			$myString = html_entity_decode( $myString, ENT_COMPAT, 'UTF-8' );
		}
	}
}

if( !function_exists( "H4APlugin\Core\load_install_config" ) ) {
	/**
	 * load install xml data in $h4a_config
	 *
	 * @param string $xml_install_path
	 *
	 * @return array
	 */
	function load_install_config( string $xml_install_path ) {
		$str_install = file_get_contents( $xml_install_path );

		$a_install = xmlAsStringToArray( $str_install );

		$tree_tags = array(
			[
				'parent_tag' => "tables",
				'child_tag' => "table",
				'formate_tag' => "tables"
			],
			[
				'parent_tag' => "inserts",
				'child_tag' => "insert",
				'formate_tag' => "inserts"
			],
			[
				'parent_tag' => "posts",
				'child_tag' => "post",
				'formate_tag' => "posts"
			]
		);
		$f_db_install = harmonize_install_xml( $tree_tags, $a_install['database'] );
		$f_install = array(
			'database' => $f_db_install
		);
		return $f_install;
	}
}


if( !function_exists( "H4APlugin\Core\harmonize_install_xml" ) ) {
	/**
	 * @param $tree_tags
	 * @param $array_xml
	 * @param $f_output
	 *
	 * @return mixed
	 *
	 * Function to harmonize the install xml as array with tables/inserts/posts tag
	 * because there is a diffrent in the array after using xmlAsStringToArray if there is an unique or several table/insert/post tag
	 * example of tree_tags :
	 * $tree_tags = array(
	 * [
	 * 'parent_tag' => "tables",
	 * 'child_tag' => [ //Does not work !
	 *      'parent_tag' => "table",
	 *      'child_tag' => "column",
	 *      'formate_tag' => "columns"
	 * ],
	 * 'formate_tag' => "tables"
	 * ],
	 * [
	 * 'parent_tag' => "inserts",
	 * 'child_tag' => "insert",
	 * 'formate_tag' => "inserts"
	 * ],
	 * [
	 * 'parent_tag' => "posts",
	 * 'child_tag' => "post",
	 * 'formate_tag' => "posts"
	 * ]
	 * );
	 */
	function harmonize_install_xml( $tree_tags, $array_xml, $f_output = array() ) {
		foreach ( $tree_tags as $node ) {
			$parent_tag = $node['parent_tag'];
			$formate_tag = $node['formate_tag'];
			if ( isset( $array_xml[ $parent_tag ] ) ) {
				if( is_string( $node['child_tag'] ) ){
					$child_tag = $node['child_tag'];
					/**
					 * Case 1 : When parent_tag has not got attributes
					 */

					/* Case 1 - 1 : When only one child_tag */
					if ( isset( $array_xml[ $parent_tag ][ $child_tag ]['@attributes'] ) ) {
						$f_output[ $formate_tag ][0] = $array_xml[ $parent_tag ][ $node['child_tag'] ];
					}
					/* Case 1 - 2 : When several child_tag */
					else {
						foreach ( $array_xml[ $parent_tag ][ $child_tag ] as $item_array_xml ) {
							$f_output[ $formate_tag ][] = $item_array_xml;
						}
					}

					/**
					 * Case 2 : When parent_tag has got attributes
					 */
					//TODO

				}/*else if( is_array( $node['child_tag'] ) ){
					pretty_var_dump( $array_xml[ $parent_tag ] );
					exit;
					if ( isset( $array_xml[ $parent_tag ][ $child_tag ]['@attributes'] ) ) {
						$f_output[ $formate_tag ][0] = ;
                    }
					$f_output[ $formate_tag ] =  self::harmonize_install_xml( $node['child_tag'], $array_xml[ $parent_tag ] );
				}*/
			}
		}
		/*foreach ( $tags as $tag => $children ) {
			if ( isset( $xml[ $tag ] ) ) {
				if ( is_string( $children ) ) {
					if ( isset( $xml[ $tag ][ $children ]['@attributes'] ) ) {
						$f_install[ $tag ][0] = $xml[ $tag ][ $children ];
					} else {
						foreach ( $xml[ $tag ][ $children ] as $table ) {
							$f_install[ $tag ][] = $table;
						}
					}
				} else if ( is_array( $children ) ) {
					//children = array( "columns" => "column" )
					//
					$f_install[ $tag ] = self::formate_install_xml( $children, $xml[ $tag ][ $children ] ,$f_install );
				}
			}
		}*/

		return $f_output;
	}
}

if( !function_exists( "H4APlugin\Core\addHTMLinDOMDocument" ) ) {
	function addHTMLinDOMDocument( &$htmlTmpl, $html, $nodeName ) {
		$contentTmplDoc               = new \DOMDocument;
		$contentTmplDoc->formatOutput = true;
		$contentTmplDoc->loadXML( $html );
		$contentTmplDoc->saveXML();
		// The node we want to import to a new document
		$node = $contentTmplDoc->getElementsByTagName( $nodeName )->item( 0 );

		// Import the node, and all its children, to the document
		if( $htmlTmpl instanceof \DOMDocument)
			$node = $htmlTmpl->importNode( $node, true );

		// And then append it to the "<root>" node
		$htmlTmpl->documentElement->appendChild( $node );
	}
}

/**
 * 10.0 - License
 * -----------------------------------------------------------------------------
 */

if( !function_exists( "H4APlugin\Core\activate_license" ) ){
	function activate_license( $wgs_premium_options, $redirect_url ){
		$license_server_url = null;
		$secret_key_license_verif = null;
		$license_product_ref = null;
		update_option( "wgs-premium-options", $wgs_premium_options );
		$license_key = $wgs_premium_options['wgs_license_key'];
		$h4a_config = Config::getConfig();
		$license_server_url = $h4a_config['plugin_info']['author_uri'];
		$secret_key_license_verif = $h4a_config['plugin_info']['secret_key_license_verif'];
		$license_product_ref = $h4a_config['plugin_info']['title'];
		$current_plugin_domain = get_current_plugin_domain();
		$error_activation_key  = __( "WP Group Subscription license activation error : ", $current_plugin_domain );
		if(
			!isset( $secret_key_license_verif )
			|| !isset( $license_server_url )
			|| !isset( $license_product_ref )
		){
			Notices::setNotice( $error_activation_key . __( 'Please complete correctly license login information.', $current_plugin_domain ) , "error", true );
		}else{
			//Check if it´s already activated for this website
			$api_params = array(
				'slm_action' => "slm_check",
				'secret_key' => $secret_key_license_verif,
				'license_key' => $license_key,
			);
			// Send query to the license manager server
			$response = wp_remote_get( add_query_arg($api_params, $license_server_url ), array('timeout' => 40, 'sslverify' => false) );

			if ( is_wp_error( $response ) ){
				Notices::setNotice( $error_activation_key . $response->get_error_code() , "error" );
			}else{
				// License data.
				$license_data = json_decode( wp_remote_retrieve_body($response) );
				if( $license_data->result == 'success'){
					if( $license_data->status === "active"
					    && !empty( $license_data->registered_domains )
					    && in_array( get_site_url(), $license_data->registered_domains )
					){
						set_transient( H4A_WGS_LICENSE_STATUS, "activated" );
					}else{

						//Not already activated, so try to activate
						$api_params = array(
							'slm_action' => 'slm_activate',
							'secret_key' => $secret_key_license_verif,
							'license_key' => $license_key,
							'registered_domain' => $_SERVER['SERVER_NAME'],
							'item_reference' => urlencode( $license_product_ref ),
						);

						// Send query to the license manager server
						$query = esc_url_raw( add_query_arg( $api_params, $license_server_url ) );
						$response = wp_remote_get( $query, array('timeout' => 40, 'sslverify' => false));

						// Check for error in the response
						if (is_wp_error($response)){
							Notices::setNotice( $error_activation_key . $response->get_error_code() , "error", true );
						}else{
							//var_dump($response);//uncomment it if you want to look at the full response

							// License data.
							$license_data = json_decode(wp_remote_retrieve_body($response));

							if( $license_data->result == 'success'){//Success was returned for the license activation

								//Save the license key in the options table
								Notices::setNotice( __( $license_data->message, $current_plugin_domain ) , "success", true );
								$message_warning = sprintf( __( 'To get WP Group Subscription premium options, <a href="%s">please activate your license key</a>.', get_current_plugin_domain() ),
									wp_admin_build_url( H4A_WGS_PAGE_SETTINGS, true, array( 'tab' => "premium" ) )
								);
								if( Notices::containsNotice( $message_warning ) )
									Notices::removeNotice( $message_warning );

								set_transient( H4A_WGS_LICENSE_STATUS, "activated" );
								wp_redirect( $redirect_url );
							}
							else{
								//Show error to the user. Probably entered incorrect license key.
								Notices::setNotice( $error_activation_key . __( $license_data->message, $current_plugin_domain ) , "error", true );
							}
						}
					}
				}else{
					//Show error to the user. Probably entered incorrect license key.
					Notices::setNotice( $error_activation_key . __( $license_data->message, $current_plugin_domain ) , "error", true );
				}
			}
		}


	}
}

if( !function_exists( "H4APlugin\Core\deactivate_license" ) ){
	function deactivate_license( $wgs_premium_options, $redirect_url = null ){

		$secret_key_license_verif = null;
		$license_server_url = null;
		$license_product_ref = null;

		update_option( "wgs-premium-options", $wgs_premium_options );
		$license_key = $wgs_premium_options['wgs_license_key'];

		$h4a_config = Config::getConfig();
		$license_server_url = $h4a_config['plugin_info']['author_uri'];
		$secret_key_license_verif = $h4a_config['plugin_info']['secret_key_license_verif'];
		$license_product_ref = $h4a_config['plugin_info']['title'];

		$current_plugin_domain = get_current_plugin_domain();
		$error_deactivation_key  = __( "WP Group Subscription license deactivation error : ", $current_plugin_domain );

		if(
			!isset( $secret_key_license_verif )
			|| !isset( $license_server_url )
			|| !isset( $license_product_ref )
		){
			Notices::setNotice( $error_deactivation_key . __( 'Please complete correctly license login information.', $current_plugin_domain ) , "error", true );
		}else{
			// API query parameters
			$api_params = array(
				'slm_action' => 'slm_deactivate',
				'secret_key' => $secret_key_license_verif,
				'license_key' => $license_key,
				'registered_domain' => $_SERVER['SERVER_NAME'],
				'item_reference' => urlencode( $license_product_ref ),
			);

			// Send query to the license manager server
			$query = esc_url_raw( add_query_arg($api_params, $license_server_url ) );
			$response = wp_remote_get( $query, array('timeout' => 40, 'sslverify' => false ) );

			// Check for error in the response
			if ( is_wp_error($response) ){
				Notices::setNotice( $error_deactivation_key . $response->get_error_code() , "error", true );
			}

			// License data.
			$license_data = json_decode(wp_remote_retrieve_body($response));


			if( $license_data->result == 'success' ){//Success was returned for the license activation

				//Save the license key in the options table
				Notices::setNotice( __( $license_data->message, $current_plugin_domain ) , "success", true );
				set_transient( H4A_WGS_LICENSE_STATUS, "deactivated" );
				if( !empty( $redirect_url ) )
					wp_redirect( $redirect_url );
			}
			else{
				//Show error to the user. Probably entered incorrect license key.
				Notices::setNotice( $error_deactivation_key . __( $license_data->message, $current_plugin_domain ) , "error", true );
			}
		}
	}
}


if( !function_exists( "H4APlugin\Core\is_license_activated" ) ){
	function is_license_activated( $remote = false, $license_key = null, $license_server_url = null ){
		$trans_lisence_status = get_transient( H4A_WGS_LICENSE_STATUS );
		if( $remote ) {
			if( empty( $license_key ) ){
				$wgs_premium_options = get_option( "wgs-premium-options" );
				if ( ! empty( $wgs_premium_options ) ) {
					$license_key = $wgs_premium_options['wgs_license_key'];
					$wgs_license_login_options = get_option( Config::gen_options_name( "license-login" ) );
					if( !empty( $wgs_license_login_options ) ){
						if( !empty( $wgs_license_login_options['secret_key_license_verif'] ) )
							$secret_key_license_verif = $wgs_license_login_options['secret_key_license_verif'];
						if( !empty( $wgs_license_login_options['license_server_url'] ) )
							$license_server_url = $wgs_license_login_options['license_server_url'];
					}
				}
			}else{
				if( empty( $license_server_url ) ){
					$wgs_license_login_options = get_option( Config::gen_options_name( "license-login" ) );
					if( !empty( $wgs_license_login_options ) ){
						if( !empty( $wgs_license_login_options['license_server_url'] ) )
							$license_server_url = $wgs_license_login_options['license_server_url'];
					}
				}
			}
			if(
				!isset( $secret_key_license_verif )
				|| !isset( $license_server_url )
			){
				return false;
			}else{
				if ( ! empty( $license_key ) ) {
					$api_params = array(
						'slm_action'  => "slm_check",
						'secret_key'  => $secret_key_license_verif,
						'license_key' => $license_key,
					);

					// Send query to the license manager server
					$response = wp_remote_get( add_query_arg( $api_params, $license_server_url ),
						array( 'timeout'   => 100,
						       'sslverify' => true
						) );
					if ( !is_wp_error( $response ) ) {
						$license_data = json_decode( wp_remote_retrieve_body( $response ) );
						if ( !empty( $license_data ) ){
							if ( $license_data->result == 'success' ) {
								wp_debug_log( serialize( $license_data->status ) );
								wp_debug_log( basename( get_site_url() ) );
								wp_debug_log( serialize( $license_data->registered_domains ) );
								if ( $license_data->status === "active"
								     && ! empty( $license_data->registered_domains )
								) {
									foreach ( $license_data->registered_domains as $o_registered_domain ){
										if( $o_registered_domain->registered_domain === basename( get_site_url() ) ){
											if ( !isset( $trans_lisence_status ) || $trans_lisence_status !== "activated" ) {
												set_transient( H4A_WGS_LICENSE_STATUS, "activated" );
											}
											return true;
										}
									}
								}
							}
						}

					}
				}

			}
		}else if ( isset( $trans_lisence_status ) && $trans_lisence_status === "activated" ) {
			return true;
		}
		set_transient( H4A_WGS_LICENSE_STATUS, "deactivated" );
		return false;
	}
}

if( !function_exists( "H4APlugin\Core\get_license_status" ) ){
	function get_license_status( $license_key = null, $license_server_url = null ){
		if( empty( $license_key ) ){
			$wgs_premium_options = get_option( "wgs-premium-options" );
			if ( ! empty( $wgs_premium_options ) ) {
				$license_key = $wgs_premium_options['wgs_license_key'];
				$wgs_license_login_options = get_option( Config::gen_options_name( "license-login" ) );
				if( !empty( $wgs_license_login_options ) ){
					if( !empty( $wgs_license_login_options['secret_key_license_verif'] ) )
						$secret_key_license_verif = $wgs_license_login_options['secret_key_license_verif'];
					if( !empty( $wgs_license_login_options['license_server_url'] ) )
						$license_server_url = $wgs_license_login_options['license_server_url'];
				}
			}
		}else{
			$wgs_license_login_options = get_option( Config::gen_options_name( "license-login" ) );
			if( !empty( $wgs_license_login_options ) ){
				if( !empty( $wgs_license_login_options['secret_key_license_verif'] ) )
					$secret_key_license_verif = $wgs_license_login_options['secret_key_license_verif'];
				if( empty( $license_server_url ) ){
					if( !empty( $wgs_license_login_options['license_server_url'] ) )
						$license_server_url = $wgs_license_login_options['license_server_url'];
				}
			}
		}
		if(
			!empty( $secret_key_license_verif )
			&& !empty( $license_server_url )
			&& !empty( $license_key )
		){
			$api_params = array(
				'slm_action'  => "slm_check",
				'secret_key'  => $secret_key_license_verif,
				'license_key' => $license_key,
			);

			// Send query to the license manager server
			$response = wp_remote_get( add_query_arg( $api_params, $license_server_url ),
				array( 'timeout'   => 100,
				       'sslverify' => true
				) );
			if ( !is_wp_error( $response ) ) {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				if ( !empty( $license_data ) ){
					if ( $license_data->result == 'success' ) {
						return $license_data->status;
					}
				}
			}
		}
		return false;
	}
}

