<?php
namespace OCA\Archive\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;
use OCP\Util;

use OCA\Archive\AppInfo\Application;

class Admin implements ISettings {

	public function __construct() {
		
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
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