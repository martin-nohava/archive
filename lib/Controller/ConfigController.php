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
	 * Set admin configuration to config.php file
	 *
	 * @param array $state
	 * @return DataResponse
	 */
	public function setAdminSettings(array $state): array {
        /* Array containing application settings */
        $array = array();
        /* Populate array with 'key => value' configuration pairs */
		foreach ($state as $key => $value) {
            $this->logger->warning('Key: '.$key, ['archive' => Application::APP_ID]);
			$array[$key] = $value;
		}
        /* Try to write configuration array to config.php file */
        try {
            $this->config->setSystemValue('archive', $array);
        } catch (HintException $e) {
            return ['error' => $e->getMessage()];
        }
		return ['status' => 'success'];
	}

}