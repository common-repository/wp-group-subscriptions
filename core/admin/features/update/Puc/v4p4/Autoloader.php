<?php
namespace H4APlugin\Core\Admin;
if ( !class_exists('H4APlugin\\Core\\Admin\\Puc_v4p4_Autoloader', false) ):

	class Puc_v4p4_Autoloader {
		private $prefix = '';
		private $rootDir = '';
		private $libraryDir = '';

		private $staticMap;

		public function __construct() {
			$this->rootDir = dirname(__FILE__) . '/';
			$nameParts = explode('_', __CLASS__, 3);
			$this->prefix = $nameParts[0] . '_' . $nameParts[1] . '_';

			$this->libraryDir = realpath($this->rootDir . '../..') . '/';
			$this->staticMap = array(
				'PucReadmeParser' => 'vendor/readme-parser.php',
				//'Parsedown' => 'vendor/ParsedownLegacy.php',
				'Parsedown' => 'vendor/Parsedown.php',
			);
			/*if ( version_compare(PHP_VERSION, '5.3.0', '>=') ) {
				$this->staticMap['Parsedown'] = 'vendor/Parsedown.php';
			}*/

			try {
				\spl_autoload_register( array( $this, 'autoload' ) );
			} catch ( \Exception $e ) {
				return;
			}
		}

		public function autoload($className) {
			if ( isset($this->staticMap[$className]) && file_exists($this->libraryDir . $this->staticMap[$className]) ) {
				/** @noinspection PhpIncludeInspection */
				include ($this->libraryDir . $this->staticMap[$className]);
				return;
			}

			if (strpos($className, $this->prefix) === 0) {
				$path = substr($className, strlen($this->prefix));
				$path = str_replace('_', '/', $path);
				$path = $this->rootDir . $path . '.php';

				if (file_exists($path)) {
					/** @noinspection PhpIncludeInspection */
					include $path;
				}
			}
		}
	}

endif;