<?php
defined('ABSPATH') or die;

require_once get_template_directory() . '/library/class-tgm-plugin-activation.php';

function theme_register_required_plugins() {

	$plugin_source = get_template_directory() . '/plugins/nicepage.zip';
	if (!file_exists($plugin_source)) {
		// noting to install
		return;
	}

	$plugins = array(
      		array(
      			'name'               => 'Nicepage',     // The plugin name.
      			'slug'               => 'nicepage',     // The plugin slug (typically the folder name).
      			'source'             => $plugin_source, // The plugin source.
      			'required'           => false,          // If false, the plugin is only 'recommended' instead of required.
      			'version'            => '',             // E.g. 1.0.0. If set, the active plugin must be this version or higher. If the plugin version is higher than the plugin version installed, the user will be notified to update the plugin.
      			'force_activation'   => false,          // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
      			'force_deactivation' => false,          // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
      			'external_url'       => '',             // If set, overrides default API URL and points to an external URL.
      			'is_callable'        => '',             // If set, this callable will be be checked for availability to determine if a plugin is active.
      		)
      	);

	$config = array(
		'id'           => 'tgmpa-nicepage',        // Unique ID for hashing notices for multiple instances of TGMPA.
		'default_path' => '',                      // Default absolute path to bundled plugins.
		'menu'         => 'tgmpa-install-plugins', // Menu slug.
		'parent_slug'  => 'themes.php',            // Parent menu slug.
		'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
		'has_notices'  => true,                    // Show admin notices or not.
		'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
		'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
		'is_automatic' => true,                    // Automatically activate plugins after installation or not.
		'message'      => '',                      // Message to output right before the plugins table.
		'strings' => array(
			'nag_type' => 'updated',
		),
	);
	tgmpa($plugins, $config);
}
add_action('tgmpa_register', 'theme_register_required_plugins');

function theme_redirect_if_plugin_installed() {
    if (isset($_GET['page']) && $_GET['page'] === 'tgmpa-install-plugins') {
        wp_redirect(admin_url('plugins.php'));
        die;
    }
}
add_action('admin_page_access_denied', 'theme_redirect_if_plugin_installed');
