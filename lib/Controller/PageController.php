<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Archive\Controller;

use OCA\Archive\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
	public function __construct(IRequest $request) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index(): TemplateResponse {
		/* Load minified front-end script from js/ */
        Util::addScript(Application::APP_ID, Application::APP_ID . '-' . 'main');
		/* Return template from templates/ */
		return new TemplateResponse(Application::APP_ID, 'main');
	}
}
