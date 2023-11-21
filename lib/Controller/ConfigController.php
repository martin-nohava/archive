<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Archive\Controller;

use OCA\Archive\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\HintException;
use OCP\IRequest;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class ConfigController extends Controller {
    /**
	 * @var LoggerInterface
	 */
	private $logger;
    /**
	 * @var IConfig
	 */
	private $config;

	public function __construct(IRequest $request, 
                                LoggerInterface $logger,
                                IConfig $config) {

        $this->logger = $logger;
        $this->config = $config;

		parent::__construct(Application::APP_ID, $request);
	}

    /**
	 * Set admin configuration
	 *
	 * @param array $state
	 * @return DataResponse
	 */
	public function setAdminSettings(array $state): array {
		foreach ($state as $key => $value) {
            $this->logger->warning('Setting Archive config with key: ' . $key);
			try {
				if (is_bool($value)) {
					/* If value is a bool convert it to 0 or 1 */
					$this->config->setAppValue(Application::APP_ID, $key, (int)$value);
				} else {
					$this->config->setAppValue(Application::APP_ID, $key, $value);
				}
			} catch (HintException $e) {
				return ['error' => $e->getMessage()];
			}
		}
        
		return ['status' => 'success'];
	}

}