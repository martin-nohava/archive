<?php
namespace OCA\Archive\Settings;

use OCP\AppFramework\Services\IInitialState;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\IConfig;
use OCP\Util;

use OCA\Archive\AppInfo\Application;

class Admin implements ISettings {

	/**
	 * @var IConfig
	 */
	private $config;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;

	public function __construct(IConfig $config,
								IInitialState $initialStateService
	) {
		$this->config = $config;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		/* Get values from config */
		$adminSettings = [
			'url' => $this->config->getAppValue(Application::APP_ID, 'url', ''),
			'secret' => boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false')),
			'selfsigned' => $this->config->getSystemValue(Application::APP_ID, 'secret', ''),
		];

		/* Pass values to initial state service. Values than can be consumed by Vue frontend via @nextcloud/initial-state */
		$this->initialStateService->provideInitialState('admin-settings', $adminSettings);

        /* Load minified front-end script from js/ */
        Util::addScript(Application::APP_ID, Application::APP_ID . '-' . 'adminSettings');
		/* Return template from templates/ */
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'archive';
	}

	public function getPriority(): int {
		return 10;
	}
}