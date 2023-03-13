<?php
/**
 * Nextcloud - Archive
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 * 
 * @author Martin Nohava <martin.nohava@vut.cz>
 * @copyright Martin Nohava 2023
 * 
 * Code inspired by Mattermost integration into Nextcloud availble from
 * https://github.com/julien-nc/integration_mattermost by Julien Veyssier 2022
 */

namespace OCA\Archive\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OC\Files\Node\File;
use OCA\Archive\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;

class ArchiveApiService {
	/**
	 * @var LoggerInterface
	 */
	private $logger;
	/**
	 * @var IL10N
	 */
	private $l10n;
	/**
	 * @var \OCP\Http\Client\IClient
	 */
	private $client;
	/**
	 * @var IRootFolder
	 */
	private $root;

	/**
	 * Service to comunicate with Archive server API
	 */
	public function __construct (LoggerInterface $logger,
								IL10N $l10n,
								IRootFolder $root,
								IClientService $clientService) {
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->client = $clientService->newClient();
		$this->root = $root;
	}

	/**
	 * @param string $userId
	 * @param string $mattermostUrl
	 * @param int $fileId
	 * @param string $channelId
	 * @return array|string[]
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Lock\LockedException
	 * @throws \OC\User\NoUserException
	 */
	public function submitFile(string $userId, int $fileId, string $comment): array {
		$userFolder = $this->root->getUserFolder($userId);
		$files = $userFolder->getById($fileId);
		if (count($files) > 0 && $files[0] instanceof File) {
			$file = $files[0];
			$serverUrl = 'http://nextcloudserver.nohavovi.cz:5000';
			$endpoint = '/submit-file';
			$sendResult = $this->postFile($serverUrl, $endpoint, $comment, $file);
			if (isset($sendResult['error'])) {
				return $sendResult;
			}

			if (isset($sendResult['status'])) {
				$status = $sendResult['status'];

				return [
					'remote_file_id' => $status,
				];
			} else {
				return ['error' => 'File upload error'];
			}
		} else {
			return ['error' => 'File not found'];
		}
	}

	/**
	 * @param string $url
	 * @param string $endPoint
	 * @param string $comment
	 * @param $file
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function postFile(string $url, string $endPoint, string $comment, $file) {
		//TODO: Implement token
		try {
			$url = $url . '/api' . $endPoint;
			$options = [
				'headers' => [
					'Transfer-Encoding' => 'chunked'
				],
				'multipart' => [ 
					[
						'name'     => 'file',
						'contents' => $file->fopen('r'),
						'filename' => $file->getName(),
					],
					[
						'name'     => 'comment',
						'contents' => $comment,
					]
				],
				'timeout' => 0,
			];

			$response = $this->client->post($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$this->logger->warning('Failed to submit file: '.$e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

}