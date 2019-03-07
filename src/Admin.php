<?php
/**
 * Copyright (C) 2019 Adshares sp. z o.o.
 *
 * This file is part of Adshares WordPress Plugin
 *
 * Adshares WordPress Plugin is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Adshares WordPress Plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Adshares WordPress Plugin. If not, see
 * <https://www.gnu.org/licenses/>
 */

namespace Adshares\WordPress;

use Psr\Http\Message\ResponseInterface;

class Admin
{
    const SLUG = 'adshares-config';

    private static $instance = null;
    private $initiated = false;
    private $title = 'Adshares Plugin Settings';
    private $errorMessage = null;
    private $savedInfo = null;

    /**
     * Create singleton.
     *
     * @return Admin
     */
    private static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Handle initiating event.
     */
    public static function handleInit()
    {
        $admin = self::getInstance();
        $admin->init();
    }

    /**
     * Prepare page title.
     *
     * @return string
     */
    public function getTitle()
    {
        return __($this->title, 'adshares');
    }

    /**
     * Init admin plugin.
     */
    public function init()
    {
        $this->initHooks();

        $action = isset($_POST['action']) ? $_POST['action'] : null;

        switch ($action) {
            case 'synchronize':
                $this->synchronize();
                break;
            case 'configure':
                $this->configure();
                break;
            case 'save-settings':
                $this->saveSettings();
                break;
        }
    }

    /**
     * Init hooks.
     */
    public function initHooks()
    {
        if ($this->initiated) {
            return;
        }
        $this->initiated = true;

        add_action('admin_init', [$this, 'initAdmin']);
        add_action('admin_menu', [$this, 'createMenu'], 1);
        add_action('admin_notices', [$this, 'displayNotices']);
        add_action('admin_enqueue_scripts', [$this, 'loadResources']);
        add_filter('plugin_action_links', [$this, 'filterActionLinks'], 10, 2);
    }

    /**
     * Initiating admin hook.
     */
    public function initAdmin()
    {
        //not implemented
    }

    /**
     * Creating menu hook.
     */
    public function createMenu()
    {
        $hook = add_options_page(
            $this->getTitle(),
            __('Adshares', 'adshares'),
            'manage_options',
            self::SLUG,
            [$this, 'renderPage']
        );
    }

    /**
     * Displaying notices hook.
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function displayNotices()
    {
        $data = [
            'errorMessage' => __($this->errorMessage, 'adshares'),
            'savedInfo' => __($this->savedInfo, 'adshares'),
        ];

        $this->view('notices', $data);
    }

    /**
     * Loading resources hook.
     *
     * @param $hook hook name
     */
    public function loadResources($hook)
    {
        if ($hook !== 'settings_page_adshares-config') {
            return;
        }

        wp_register_style('adshares-admin', ADSHARES_ASSETS . '/admin.css', [], ADSHARES_VERSION);
        wp_enqueue_style('adshares-admin');
    }

    /**
     * Filter admin links list.
     *
     * @param $links
     * @param $file
     * @return array
     */
    public function filterActionLinks($links, $file)
    {
        if ($file == plugin_basename(ADSHARES_PLUGIN)) {
            $settingsLink = '<a href="' . esc_url($this->getUrl()) . '">' . __('Settings', 'adshares') . '</a>';
            array_unshift($links, $settingsLink);
        }

        return $links;
    }

    /**
     * Prepare admin URL.
     *
     * @param string $view view name
     * @return string
     */
    public function getUrl($view = 'config')
    {
        $args = [
            'page' => self::SLUG,
            'view' => $view,
        ];

        if ($view == 'delete_key') {
            $args += [
                'view' => 'start',
                'action' => 'delete-key',
                '_wpnonce' => wp_create_nonce(self::NONCE)
            ];
        }

        $url = add_query_arg($args, admin_url('options-general.php'));

        return $url;
    }

    /**
     * Render admin page.
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderPage()
    {
        $view = isset($_GET['view']) ? $_GET['view'] : null;

        switch ($view) {
            default:
                $this->renderConfigPage();
                break;
        }
    }

    /**
     * Render configuration page.
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function renderConfigPage()
    {
        $data = [
            'positions' => $this->getPositions(),
            'sites' => $this->getSites(),
            'visibility' => $this->getVisibility(),
            'exceptions' => $this->getExceptions(),
            'adserver' => $this->getAdServerSettings(),
        ];

        $this->view('config', $data);
    }

    /**
     * Render Twig template.
     *
     * @param $name template name
     * @param array $data rendered data
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    private function view($name, array $data = [])
    {
        $name .= '.twig';
        $data = array_merge([
            'admin' => $this,
            'nonce' => wp_nonce_field(self::SLUG),
        ], $data);

        $loader = new \Twig_Loader_Filesystem(ADSHARES_TEMPLATES);
        $twig = new \Twig_Environment($loader, [
            'cache' => ADSHARES_CACHE,
            'auto_reload' => true,
        ]);

        echo $twig->render($name, $data);
    }

    /**
     * Get AdServer setting.
     *
     * @param string $name setting name
     * @param mixed $default default value
     * @return mixed
     */
    private function getAdServerSettings($name = null, $default = null)
    {
        $settings = get_option('adshares_settings');
        $adserver = isset($settings['adserver']) ? $settings['adserver'] : [];

        if ($name === null) {
            return $adserver;
        }

        return isset($adserver[$name]) ? $adserver[$name] : $default;
    }

    /**
     * Get ads positions.
     *
     * @return array
     */
    private function getPositions()
    {
        return [
            $this->createPosition('post_beginning', 'Beginning of post'),
            $this->createPosition('post_middle', 'Middle of post'),
            $this->createPosition('post_end', 'End of post'),
            $this->createPosition('post_excerpt', 'After the excerpt'),
            $this->createPosition('paragraph_first', 'After the first paragraph '),
            $this->createPosition('paragraph_second', 'After the second paragraph '),
            $this->createPosition('paragraph_third', 'After the third paragraph '),
            $this->createPosition('paragraph_last', 'Before the last paragraph '),
        ];
    }

    /**
     * Get ads visibility.
     *
     * @return array
     */
    private function getVisibility()
    {
        return [
            $this->createVisibility('homepage', 'Homepage'),
            $this->createVisibility('categories', 'Categories '),
            $this->createVisibility('archives', 'Archives '),
            $this->createVisibility('tags', 'Tags'),
        ];
    }

    /**
     * Get ads exceptions.
     *
     * @return array
     */
    private function getExceptions()
    {
        return [
            $this->createException('logged_user', 'Hide ads when user is logged in'),
        ];
    }

    /**
     * Create ad position option.
     * @param $id ad id
     * @param $label ad label
     * @return array
     */
    private function createPosition($id, $label)
    {
        return $this->createOption('positions', $id, $label);
    }

    /**
     * Create ad visibility option.
     * @param $id ad id
     * @param $label ad label
     * @return array
     */
    private function createVisibility($id, $label)
    {
        return $this->createOption('visibility', $id, $label);
    }

    /**
     * Create ad visibility option.
     * @param $id ad id
     * @param $label ad label
     * @return array
     */
    private function createException($id, $label)
    {
        return $this->createOption('exceptions', $id, $label);
    }

    /**
     * Create ad option.
     * @param $group group name
     * @param $id ad id
     * @param $label ad label
     * @return array
     */
    private function createOption($group, $id, $label)
    {
        $settings = get_option('adshares_settings');

        return [
            'id' => $id,
            'label' => __($label, 'adshares'),
            'value' => isset($settings[$group][$id]) ? $settings[$group][$id] : null,
        ];
    }

    /**
     * Get synchronized sites.
     *
     * @return array
     */
    private function getSites()
    {
        return get_option('adshares_sites');
    }

    /**
     * Perform API request.
     *
     * @param $method
     * @param string $uri
     * @param array $headers
     * @param array $options
     * @return ResponseInterface
     */
    private function apiRequest($method, $uri = '', array $headers = [], array $options = [])
    {
        $client = new \GuzzleHttp\Client();
        try {
            return $client->request($method, $uri, array_merge([
                'http_errors' => false,
                'headers' => array_merge([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ], $headers),
            ], $options));
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            $this->errorMessage = sprintf('Cannot connect to the server: %s.', $e->getMessage());
            return null;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->errorMessage = $e->getMessage();
            return null;
        }
    }

    /**
     * Prepare server URL
     *
     * @param $path URL path
     * @return string
     */
    private function getServerUrl($path)
    {
        $url = $this->getAdServerSettings('url');

        if (empty($url)) {
            $this->errorMessage = 'Missing AdServer URL';

            return null;
        }

        $res = $this->apiRequest('GET', $url . '/info.json');

        if (null === $res) {
            $this->errorMessage = 'Failed to fetch INFO';

            return null;
        }

        if (200 !== $res->getStatusCode()) {
            $this->errorMessage = sprintf('INFO endpoint returned an error [%d]', $res->getStatusCode());

            return null;
        }

        $info = json_decode($res->getBody(), true);

        if (JSON_ERROR_NONE !== json_last_error()) {
            $this->errorMessage = sprintf('Error parsing INFO response. %s', json_last_error_msg());

            return null;
        }

        if (!isset($info['module'])) {
            $this->errorMessage = 'Invalid INFO format (Missing module name)';

            return null;
        }

        if ('adserver-user-panel' === $info['module']) {
            if (!isset($info['serverUrl'])) {
                $this->errorMessage = 'Invalid INFO format (Missing server url)';

                return null;
            }

            $url = $info['serverUrl'];
        } elseif ('adserver' !== $info['module']) {
            $this->errorMessage = 'Invalid module.';

            return null;
        }

        return $url . $path;
    }

    /**
     * Create API token.
     *
     * @param $login
     * @param $password
     * @return string|null
     */
    private function getApiToken($login, $password)
    {
        $serverUrl = $this->getServerUrl('/auth/login');

        if ($serverUrl === null) {
            return null;
        }

        $res = $this->apiRequest(
            'POST',
            $serverUrl,
            [],
            [
                'body' => json_encode([
                    'email' => $login,
                    'password' => $password,
                ]),
            ]
        );

        if ($res === null) {
            return null;
        }

        if ($res->getStatusCode() === 400) {
            $this->errorMessage = 'Invalid email address or password.';

            return null;
        }

        if ($res->getStatusCode() !== 200) {
            $this->errorMessage = sprintf('Cannot connect to the AdServer: %s.', $this->getErrorMessage($res));

            return null;
        }

        $data = json_decode($res->getBody(), true);

        return isset($data['apiToken']) ? $data['apiToken'] : null;
    }

    /**
     * Extract error message from response.
     *
     * @param ResponseInterface $res
     * @return string
     */
    private function getErrorMessage(ResponseInterface $res)
    {
        $data = json_decode($res->getBody(), true);

        return !empty($data['message']) ? $data['message'] : sprintf('Unknown error (%d)', $res->getStatusCode());
    }

    /**
     * Synchroniza account action.
     * @return bool
     */
    public function synchronize()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], self::SLUG)) {
            return false;
        }

        $apiToken = $this->getApiToken(
            $this->getAdServerSettings('login'),
            $this->getAdServerSettings('password')
        );

        if (!$apiToken) {
            return false;
        }

        $serverUrl = $this->getServerUrl('/api/sites');

        if ($serverUrl === null) {
            return false;
        }

        $response = $this->apiRequest(
            'GET',
            $serverUrl,
            [
                'Authorization' => sprintf('Bearer %s', $apiToken),
            ]
        );

        if ($response === null) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            $this->errorMessage = $this->getErrorMessage($response);

            return false;
        }

        $activeSites = [];
        if ($sites = json_decode($response->getBody(), true)) {
            $activeSites = array_filter($sites,
                function ($site) {
                    return isset($site['status']) && isset($site['adUnits']) && $site['status'] === 2;
                });
            foreach ($activeSites as $site) {
                $site['adUnits'] = array_filter($site['adUnits'],
                    function ($unit) {
                        return isset($unit['status']) && $unit['status'] === 1;
                    });
            }
        }

        update_option('adshares_sites', $activeSites);
        $this->savedInfo = 'Successful synchronized.';

        return true;
    }

    /**
     * Configure account action.
     *
     * @return bool
     */
    public function configure()
    {
        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], self::SLUG)) {
            return false;
        }

        if (!isset($_POST['adserver']) ||
            !isset($_POST['adserver']['url']) ||
            !isset($_POST['adserver']['login']) ||
            !isset($_POST['adserver']['password'])
        ) {
            $this->errorMessage = 'Invalid form data.';
            return false;
        }

        $settings = (array)get_option('adshares_settings');
        $settings['adserver'] = array_merge(
            (array)$settings['adserver'],
            array_filter($_POST['adserver'])
        );
        update_option('adshares_settings', $settings);

        if ($this->synchronize() === false) {
            return false;
        }

        $settings['adserver']['configured'] = true;
        update_option('adshares_settings', $settings);

        $this->savedInfo = 'Successful connected.';

        return true;
    }

    /**
     * Save settings action.
     *
     * @return bool
     */
    public function saveSettings()
    {

        if (!current_user_can('manage_options')) {
            return false;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], self::SLUG)) {
            return false;
        }

        $settings = get_option('adshares_settings');
        $settings['positions'] = $_POST['positions'];
        $settings['visibility'] = $_POST['visibility'];
        $settings['exceptions'] = $_POST['exceptions'];
        update_option('adshares_settings', $settings);

        $this->savedInfo = 'Successful saved.';

        return true;
    }
}
