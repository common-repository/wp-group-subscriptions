<?php
namespace H4APlugin\Core\Admin;
/**
 * Plugin Update Checker Library 4.4
 * http://w-shadow.com/
 * 
 * Copyright 2017 Janis Elsts
 * Released under the MIT license. See license.txt for details.
 */

require dirname( __FILE__ ) . '/Puc/v4p4/Factory.php';
require dirname( __FILE__ ) . '/Puc/v4/Factory.php';
require dirname( __FILE__ ) . '/Puc/v4p4/Autoloader.php';
new Puc_v4p4_Autoloader();

//Register classes defined in this file with the factory.
Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\Plugin_UpdateChecker', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Plugin_UpdateChecker', '4.4');
Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\Theme_UpdateChecker', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Theme_UpdateChecker', '4.4');

Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\Vcs_PluginUpdateChecker', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Vcs_PluginUpdateChecker', '4.4');
Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\Vcs_ThemeUpdateChecker', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Vcs_ThemeUpdateChecker', '4.4');

Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\GitHubApi', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Vcs_GitHubApi', '4.4');
Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\BitBucketApi', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Vcs_BitBucketApi', '4.4');
Puc_v4_Factory::addVersion('H4APlugin\\Core\\Admin\\GitLabApi', 'H4APlugin\\Core\\Admin\\Puc_v4p4_Vcs_GitLabApi', '4.4');