<?php
/*
Plugin Name: Download Plugin
Plugin URI: http://github.com/johnthegreat/wp-download-plugin
Description: Allows you to download a plugin.
Version: 0.1.0
Author: John Nahlen
Author URI: http://johnnahlen.com/
License: GPLv3 or later
*/

define("DOWNLOAD_PLUGIN_SLUG","download_plugin");

add_action("admin_menu",array("DownloadPlugin","_init"));

if (isset($_POST["dl_plugin"])) {
	DownloadPlugin::doDownloadPlugin($_POST["dl_plugin"]);
}

class DownloadPlugin {
	public static function _init() {
		// Add menus, hooks, etc
		add_submenu_page("tools.php", "Download Plugin", "Download Plugin", "install_plugins", DOWNLOAD_PLUGIN_SLUG, array(__CLASS__,"showGUI"));
	}
	
	public static function showGUI() {
		// This page allows the user to choose which plugin he or she wants to download.
		// This function requires that WordPress be loaded
		
		$plugins_list = get_plugins();
		$num_plugins = count($plugins_list);
		
		echo "<h2>Download Plugin</h2>";
		echo "<form action=\"" . admin_url("tools.php?page=".DOWNLOAD_PLUGIN_SLUG) . "\" method=\"post\" />";
			echo "<select name=\"dl_plugin\">";
			foreach($plugins_list as $key=>$value) {
				echo "<option value=\"$key\">" . $value["Name"] . (is_plugin_active($key) ? " (Active)" : " (Inactive)") . "</option>";
			}
			echo "</select> <input type=\"submit\" value=\"Download\" />"; 
		echo "</form>";
		// simple one-liner showing who wrote the plugin
		echo "<div style=\"margin-top: 50px;\">Copyright (c) 2013 John Nahlen (john.nahlen@gmail.com)</div>";
	}
	
	public static function doDownloadPlugin($plugin) {
		// Download a plugin
		// Because it modifies headers, WordPress should not have echo'd anything yet
		// Function returns false if plugins directory is not writable or zip file could not be closed (e.g. if a file you're trying to zip doesnt exist)
		
		$cwd = getcwd();
		chdir(".." . DIRECTORY_SEPARATOR . basename(WP_CONTENT_DIR) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR);
		
		if (is_writable(getcwd())) {
			$zip = new ZipArchiveFinal();
			if (strpos($plugin, "/") === false) {
				// A plugin file such as hello.php
				
				$filename = basename($plugin) . ".zip";
				
				$zip->open($filename, ZipArchive::CREATE);
				$zip->addFile(basename($plugin));
			} else {
				// A plugin file such as gravityforms/gravityforms.php
				
				$filename = dirname($plugin) . ".zip";
				
				$zip->open($filename, ZipArchive::CREATE);
				$zip->addDir(dirname($plugin));
			}
			
			$closed = $zip->close();
			if (!$closed) {
				return false;
			}
		} else {
			return false;
		}
		
		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=\"$filename\"");
		readfile($filename);
		unlink($filename);
		
		chdir($cwd);
	}
}


class ZipArchiveFinal extends ZipArchive {
	// http://us3.php.net/manual/en/ziparchive.addfile.php#93090
	public function addDir($path) {
		$this->addEmptyDir($path);
		$nodes = glob($path . '/*');
		foreach ($nodes as $node) {
			if (is_dir($node) && is_readable($node)) {
				$this->addDir($node);
			} else if (is_file($node) && is_readable($node)) {
				$this->addFile($node);
			}
		}
	}
}
?>