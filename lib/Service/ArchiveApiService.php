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

use ZipArchive;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCP\Files\File;
use OCP\Files\Folder;
use OCA\Archive\AppInfo\Application;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;

class ArchiveApiService
{
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
	public function __construct(
		LoggerInterface $logger,
		IL10N $l10n,
		IRootFolder $root,
		IConfig $config,
		IClientService $clientService
	) {
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
	public function submitFile(string $userId, int $fileId, string $comment): array
	{
		/* Get required info */
		$userFolder = $this->root->getUserFolder($userId);
		$files = $userFolder->getById($fileId);

		$url = $this->config->getAppValue(Application::APP_ID, 'url', '');
		$selfsigned = boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false'));
		$secret = $this->config->getAppValue(Application::APP_ID, 'secret', '');

		/* If type of file is 'dir' make .zip archive first */
		if (count($files) > 0 && $files[0] instanceof Folder) {
		 	$dir = $files[0];
			$url = $url.'/api/submit-file';
			/* Generate /tmp destination for .zip file */
			$destination = '/tmp' . '/' . $dir->getName() . '-' . time() . '.zip';
			/* Create new .zip from  directoty in destination */
			$this->createZip($dir, $destination);

			/* Post .zip to archive */
			$sendResult = $this->postZip($url, $selfsigned, $userId, $secret, $destination);
			if (isset($sendResult['error'])) {
				return $sendResult;
			}

			// if (isset($sendResult['status'])) {
			// 	$status = $sendResult['status'];

			// 	return [
			// 		'remote_file_id' => $status,
			// 	];
			// } else {
			// 	return ['error' => 'File upload error'];
			// }

			/* Delete tmp file */
			unlink($destination);
			return [];
		}

		/* Post file to remote API */
		if (count($files) > 0 && $files[0] instanceof File) {
			$file = $files[0];
			$url = $url . '/api/submit-file';
			$sendResult = $this->postFile($url, $selfsigned, $userId, $secret, $file);
			if (isset($sendResult['error'])) {
				return $sendResult;
			}

			// if (isset($sendResult['status'])) {
			// 	$status = $sendResult['status'];

			// 	return [
			// 		'remote_file_id' => $status,
			// 	];
			// } else {
			// 	return ['error' => 'File upload error'];
			// }
			return [];
		} else {
			return ['error' => 'File not found'];
		}
	}

	/**
	 * @param string $directory
	 * @param string $destination
	 */
	public function createZip($directory, $destination)
	{
		if (!extension_loaded('zip')) {
			return false;
		}

		$zip = new ZipArchive();
		if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
			$this->logger->warning('Failed to open: ' . $destination);
			return false;
		}

		/* Recursive function to add all files and sub. dirs. to .zip. */
		/* Directory to zip, zip instance, root of directory. */
		$this->addToZip($directory, $zip, $directory);

		return $zip->close();
	}

	private function addToZip($directory, $zip, $root) {
		/* Loop over directory contents */
		foreach ($directory->getDirectoryListing() as $item) {
			if ($item instanceof File) {
				/* Use str_replace to create root/dir from /other/folder/root/dir */
				$zip->addFromString(str_replace($root->getPath() . '/', '', $item->getPath()), $item->getContent());
			} elseif ($item instanceof Folder) {
				$zip->addEmptyDir(str_replace($root->getPath() . '/', '', $item->getPath()) . '/');
				$this->addToZip($item, $zip, $root);
			}
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
	public function postZip(string $url, bool $selfsigned, string $owner, string $secret, string $file) {
		try {
			$options = [
				'headers' => [
					'Transfer-Encoding' => 'chunked',
					'x-access-secret' => $secret
				],
				'multipart' => [ 
					[
						'name'     => 'file',
						'contents' => fopen($file, 'r'),
						'filename' => explode('-', basename($file))[0] . '.zip',
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
	 * @param string $url
	 * @param bool $selfsigned
	 * @param string $owner
	 * @param string $secret
	 * @param $file
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function postFile(string $url, bool $selfsigned, string $owner, string $secret, File $file) {
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
			$this->logger->warning('Failed to submit file: ' . $e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function connected()
	{
		try {
			$url = $this->config->getAppValue(Application::APP_ID, 'url', '').'/api/status';
			$selfsigned = boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false'));
			$secret = $this->config->getAppValue(Application::APP_ID, 'secret', '');
			
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
			$this->logger->warning('Failed to connect: ' . $e->getMessage(), ['archive' => Application::APP_ID]);
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
	public function listfiles(string $owner)
	{
		try {
			$url = $this->config->getAppValue(Application::APP_ID, 'url', '').'/api/list-files';
			$selfsigned = boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false'));
			$secret = $this->config->getAppValue(Application::APP_ID, 'secret', '');
			
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
			$this->logger->warning('Failed to connect: ' . $e->getMessage(), ['archive' => Application::APP_ID]);
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
	public function validatefile(int $id)
	{
		try {
			$url = $this->config->getAppValue(Application::APP_ID, 'url', '').'/api/validate-file';
			$selfsigned = boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false'));
			$secret = $this->config->getAppValue(Application::APP_ID, 'secret', '');
			
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
			$this->logger->warning('Failed to connect: ' . $e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return array|mixed|resource|string|string[]
	 * @throws Exception
	 */
	public function validatefiles()
	{
		try {
			$url = $this->config->getAppValue(Application::APP_ID, 'url', '').'/api/validate-files';
			$selfsigned = boolval($this->config->getAppValue(Application::APP_ID, 'selfsigned', 'false'));
			$secret = $this->config->getAppValue(Application::APP_ID, 'secret', '');
			
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
			$this->logger->warning('Failed to connect: ' . $e->getMessage(), ['archive' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
