<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Archive\Controller;

use OCA\Archive\AppInfo\Application;
use OCA\Archive\Service\ArchiveApiService;
use OCP\AppFramework\ApiController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;

class ArchiveApiController extends ApiController {

	/**
	 * @var ArchiveApiService
	 */
	private ArchiveApiService $service;
	/**
	 * @var string|null
	 */
	private ?string $userId;

	public function __construct(IRequest $request,
								ArchiveApiService $service,
								?string $userId) {

		parent::__construct(Application::APP_ID, $request);
		$this->service = $service;
		$this->userId = $userId;
	}

    /**
	 * @NoAdminRequired
	 *
	 * @param int $fileId
	 * @param string $comment
	 * @return DataResponse
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws NoUserException
	 */
	public function submitFile(int $fileId, string $comment) {
		$result = $this->service->submitFile($this->userId, $fileId, $comment);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws NoUserException
	 */
	public function connected() {
		$result = $this->service->connected();
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws NoUserException
	 */
	public function listFiles() {
		$result = $this->service->listfiles($this->userId);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return DataResponse
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws NoUserException
	 */
	public function validateFile($id) {
		$result = $this->service->validatefile($id);
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return DataResponse
	 * @throws NotPermittedException
	 * @throws LockedException
	 * @throws NoUserException
	 */
	public function validateFiles() {
		$result = $this->service->validatefiles();
		if (isset($result['error'])) {
			return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
		} else {
			return new DataResponse($result);
		}
	}

}