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
use OCP\Files\File;
use OCA\Archive\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\IConfig;
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
	 * @var IConfig
	 */
	private $config;

	/**
	 * Service to comunicate with Archive server API (Endpoint)
	 */
	public function __construct (LoggerInterface $logger,
								IL10N $l10n,
								IRootFolder $root,
								IConfig $config,
								IClientService $clientService) {
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->client = $clientService->newClient();
		$this->root = $root;
	}

	/**
	 * @param string $userId
	 * @param int $fileId
	 * @param string $comment
	 * @return array|string[]
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\Lock\LockedException
	 * @throws \OC\User\NoUserException
	 */
	public function submitFile(string $userId, int $fileId, string $comment): array {
		/* Get required info */
		$userFolder = $this->root->getUserFolder($userId);
		$files = $userFolder->getById($fileId);
		$url = $this->config->getSystemValue('archive', '')['url'];
		$selfsigned = $this->config->getSystemValue('archive', false)['selfsigned'];
		$secret = $this->config->getSystemValue('archive', '')['secret'];
		
		/* Post file to remote API */
		if (count($files) > 0 && $files[0] instanceof File) {
			$file = $files[0];
			$url = $url.'/api/submit-file';
			$sendResult = $this->postFile($url, $selfsigned, $userId, $secret, $file);
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
	 * @param bool $selfsigned
	 * @param string $owner
	 * @param string $secret
	 * @param $file
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function postFile(string $url, bool $selfsigned, string $owner, string $secret, $file) {
		try {
			$options = [
				'headers' => [
					'Transfer-Encoding' => 'chunked',
					'x-access-secret' => $secret
				],
				'multipart' => [ 
					[
						'name'     => 'file',
						'contents' => $file->fopen('r'),
						'filename' => $file->getName(),
					],
					[
						'name'     => 'owner',
						'contents' => $owner,
					]
				],
				'timeout' => 0,
				'verify' => !$selfsigned,
				'nextcloud' => [
					'allow_local_address' => true,
				]
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
			$this->logger->warning('Failed to submit file: '.$e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function connected() {
		try {
			$url = $this->config->getSystemValue('archive', '')['url'].'/api/status';
			$selfsigned = $this->config->getSystemValue('archive', false)['selfsigned'];
			$secret = $this->config->getSystemValue('archive', false)['secret'];
			
			$options = [
				'headers' => [
					'x-access-secret' => $secret
				],
				'verify' => !$selfsigned,
				'nextcloud' => [
					'allow_local_address' => true,
				]
			];

			$response = $this->client->get($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$this->logger->warning('Failed to connect: '.$e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $owner
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function listfiles(string $owner) {
		try {
			$url = $this->config->getSystemValue('archive', '')['url'].'/api/list-files';
			$selfsigned = $this->config->getSystemValue('archive', false)['selfsigned'];
			$secret = $this->config->getSystemValue('archive', false)['secret'];
			
			$options = [
				'headers' => [
					'x-access-secret' => $secret
				],
				'multipart' => [
					[
						'name'     => 'owner',
						'contents' => $owner,
					]
				],
				'verify' => !$selfsigned,
				'nextcloud' => [
					'allow_local_address' => true,
				]
			];

			$response = $this->client->get($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$this->logger->warning('Failed to connect: '.$e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $id
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function validatefile(int $id) {
		try {
			$url = $this->config->getSystemValue('archive', '')['url'].'/api/validate-file';
			$selfsigned = $this->config->getSystemValue('archive', false)['selfsigned'];
			$secret = $this->config->getSystemValue('archive', false)['secret'];
			
			$options = [
				'headers' => [
					'x-access-secret' => $secret
				],
				'multipart' => [
					[
						'name'     => 'fileid',
						'contents' => $id,
					]
				],
				'verify' => !$selfsigned,
				'nextcloud' => [
					'allow_local_address' => true,
				]
			];

			$response = $this->client->get($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$this->logger->warning('Failed to connect: '.$e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function validatefiles() {
		try {
			$url = $this->config->getSystemValue('archive', '')['url'].'/api/validate-files';
			$selfsigned = $this->config->getSystemValue('archive', false)['selfsigned'];
			$secret = $this->config->getSystemValue('archive', false)['secret'];
			
			$options = [
				'headers' => [
					'x-access-secret' => $secret
				],
				'verify' => !$selfsigned,
				'nextcloud' => [
					'allow_local_address' => true,
				]
			];

			$response = $this->client->get($url, $options);
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true);
			}
		} catch (ServerException | ClientException $e) {
			$this->logger->warning('Failed to connect: '.$e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

}