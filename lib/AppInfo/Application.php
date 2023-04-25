<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Archive\AppInfo;

use OCP\AppFramework\App;
use OCP\EventDispatcher\IEventDispatcher; 
use OCP\Util;

use OCA\Files\Event\LoadAdditionalScriptsEvent;

class Application extends App {
	public const APP_ID = 'archive';

	public function __construct() {
		parent::__construct(self::APP_ID);

		/* This code is executed every time Nextcloud loads a page if this app is enabled */
		$container = $this->getContainer();
		$eventDispatcher = $container->get(IEventDispatcher::class);
				
				
		/* Load files plugin script when the Files app triggers the LoadAdditionalScriptsEvent event */
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			/* Load archive-filesplugin.js script once the Files app has done loading its scripts */
			Util::addscript(self::APP_ID, self::APP_ID . '-' . 'filesplugin', 'files');
		});
	}
}
