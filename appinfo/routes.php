<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Archive\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],

		['name' => 'archiveApi#connected', 'url' => '/connected', 'verb' => 'GET'],
		['name' => 'archiveApi#submitFile', 'url' => '/submit-file', 'verb' => 'POST'],
		['name' => 'archiveApi#listFiles', 'url' => '/list-files', 'verb' => 'GET'],
		['name' => 'archiveApi#validateFile', 'url' => '/validate-file/{id}', 'verb' => 'GET'],
		['name' => 'archiveApi#validateFiles', 'url' => '/validate-files', 'verb' => 'GET'],

		['name' => 'config#setAdminSettings', 'url' => '/admin-settings', 'verb' => 'PUT'],
		
		['name' => 'files#getFileImage', 'url' => '/preview', 'verb' => 'GET'],
	]
];
