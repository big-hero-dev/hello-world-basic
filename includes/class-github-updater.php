<?php

/**
 * GitHub Updater Class
 */
class HW_GitHub_Updater
{
	private $file;
	private $plugin;
	private $basename;
	private $active;
	private $username;
	private $repository;
	private $authorize_token;
	private $github_response;

	public function __construct($config = [])
	{
		$this->file = $config['plugin_file'];
		$this->username = $config['github_username'];
		$this->repository = $config['github_repo'];
		$this->authorize_token = $config['github_token'] ?? '';

		add_filter('pre_set_site_transient_update_plugins', [$this, 'modify_transient'], 10, 1);
		add_filter('plugins_api', [$this, 'plugin_popup'], 10, 3);
		add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);

		$this->initialize();
	}

	private function initialize()
	{
		$this->plugin = get_plugin_data($this->file);
		$this->basename = plugin_basename($this->file);
		$this->active = is_plugin_active($this->basename);
	}

	private function get_repository_info()
	{
		if (is_null($this->github_response)) {
			$args = [];
			if ($this->authorize_token) {
				$args['headers']['Authorization'] = "Bearer {$this->authorize_token}";
			}

			$request_uri = sprintf(
				'https://api.github.com/repos/%s/%s/releases/latest',
				$this->username,
				$this->repository
			);

			$response = wp_remote_get($request_uri, $args);

			if (is_wp_error($response)) {
				return false;
			}

			$response = json_decode(wp_remote_retrieve_body($response));

			if ($response) {
				$this->github_response = $response;
			}
		}
	}

	public function modify_transient($transient)
	{
		if (property_exists($transient, 'checked')) {
			if ($checked = $transient->checked) {
				$this->get_repository_info();

				if ($this->github_response) {
					$out_of_date = version_compare(
						str_replace('v', '', $this->github_response->tag_name),
						$checked[$this->basename],
						'gt'
					);

					if ($out_of_date) {
						$new_files = $this->github_response->zipball_url;
						$slug = current(explode('/', $this->basename));

						$plugin = [
							'url' => $this->plugin["PluginURI"],
							'slug' => $slug,
							'package' => $new_files,
							'new_version' => str_replace('v', '', $this->github_response->tag_name)
						];

						$transient->response[$this->basename] = (object) $plugin;
					}
				}
			}
		}

		return $transient;
	}

	public function plugin_popup($result, $action, $args)
	{
		if (!empty($args->slug)) {
			if ($args->slug == current(explode('/', $this->basename))) {
				$this->get_repository_info();

				if ($this->github_response) {
					$plugin = [
						'name'              => $this->plugin["Name"],
						'slug'              => $this->basename,
						'version'           => str_replace('v', '', $this->github_response->tag_name),
						'author'            => $this->plugin["AuthorName"],
						'author_profile'    => $this->plugin["AuthorURI"],
						'last_updated'      => $this->github_response->published_at,
						'homepage'          => $this->plugin["PluginURI"],
						'short_description' => $this->plugin["Description"],
						'sections'          => [
							'Description'   => $this->plugin["Description"],
							'Updates'       => $this->github_response->body
						],
						'download_link'     => $this->github_response->zipball_url
					];

					return (object) $plugin;
				}
			}
		}
		return $result;
	}

	public function after_install($response, $hook_extra, $result)
	{
		global $wp_filesystem;

		$install_directory = plugin_dir_path($this->file);
		$wp_filesystem->move($result['destination'], $install_directory);
		$result['destination'] = $install_directory;

		if ($this->active) {
			activate_plugin($this->basename);
		}

		return $result;
	}
}
