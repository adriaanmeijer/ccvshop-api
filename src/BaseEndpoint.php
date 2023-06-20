<?php
declare(strict_types=1);

namespace CCVShop\Api;

use Carbon\Carbon;
use CCVShop\Api\Exceptions\InvalidHashOnResult;
use CCVShop\Api\Exceptions\InvalidResponseException;
use CCVShop\Api\Factory\ExceptionFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use JsonException;
use stdClass;

abstract class BaseEndpoint
{
	protected ApiClient $client;
	private ?\stdClass $parent = null;
	protected ?int $parentId = null;
	protected ?string $parentResourcePath = null;
	protected string $resourcePath;
	private ?string $currentMethod = null;
	private ?string $currentDate = null;
	private const DELETE = 'DELETE';
	private const GET = 'GET';
	private const POST = 'POST';
	private const PUT = 'PUT';
	private const PATCH = 'PATCH';
	private const API_PREFIX = '/api/rest/v1/';

	abstract protected function getResourceObject(): BaseResource;

	abstract protected function getResourceCollectionObject(): BaseResourceCollection;

	/**
	 * @param ApiClient $api
	 */
	public function __construct(ApiClient $api)
	{
		$this->client = $api;
	}

	/**
	 * @param int $id
	 * @param array $filters
	 *
	 * @return BaseResource
	 * @throws Exceptions\InvalidHashOnResult
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	protected function rest_getOne(int $id, array $filters): BaseResource
	{
		$this->setCurrentMethod(self::GET)
			->setCurrentDate();

		$uri = $this->getUri() . '/' . $id . $this->filtersToQuery($filters);

		$headers = [
			'headers' => [
				'x-public' => $this->client->apiCredentials->GetPublic(),
				'x-hash' => $this->getHash($uri),
				'x-date' => $this->getCurrentDate(),
			],
		];
		$result  = $this->doCall($uri, $headers);

		return Factory\ResourceFactory::createFromApiResult($result, $this->getResourceObject());
	}

	/**
	 * @param $from
	 * @param $limit
	 * @param array $filters
	 *
	 * @return BaseResourceCollection
	 * @throws Exceptions\InvalidHashOnResult
	 * @throws GuzzleException
	 */
	protected function rest_getAll($from = null, $limit = null, array $filters = []): BaseResourceCollection
	{
		$this->setCurrentMethod(self::GET)->setCurrentDate();

		$uri = $this->getUri() . $this->filtersToQuery($filters);

		$headers = [
			'headers' => [
				'x-public' => $this->client->apiCredentials->GetPublic(),
				'x-hash' => $this->getHash($uri),
				'x-date' => $this->getCurrentDate(),
			],

		];

		$result = $this->doCall($uri, $headers);

		$collection = $this->getResourceCollectionObject();

		if (!isset($result->items)) {
			$collection[] = Factory\ResourceFactory::createFromApiResult($result, $this->getResourceObject());
		} else {
			foreach ($result->items as $item) {
				$collection[] = Factory\ResourceFactory::createFromApiResult($item, $this->getResourceObject());
			}
		}

		return $collection;
	}

	/**
	 * @param array $data
	 *
	 * @return BaseResource
	 * @throws Exceptions\InvalidHashOnResult
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	protected function rest_post(array $data): BaseResource
	{
		$this->setCurrentMethod(self::POST)->setCurrentDate();

		$uri = $this->getUri();

		$headers = [
			'headers' => [
				'x-public' => $this->client->apiCredentials->GetPublic(),
				'x-hash' => $this->getHash($uri, $data),
				'x-date' => $this->getCurrentDate(),
			],
			'json' => $data,

		];
		$result  = $this->doCall($uri, $headers);

		return Factory\ResourceFactory::createFromApiResult($result, $this->getResourceObject());
	}

	/**
	 * @param int $id
	 * @param array $data
	 *
	 * @return void
	 * @throws Exceptions\InvalidHashOnResult
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	protected function rest_patch(int $id, array $data): void
	{
		$this->setCurrentMethod(self::PATCH)->setCurrentDate();

		$uri = $this->getUri() . '/' . $id;

		$headers = [
			'headers' => [
				'x-public' => $this->client->apiCredentials->GetPublic(),
				'x-hash' => $this->getHash($uri, $data),
				'x-date' => $this->getCurrentDate(),
			],
			'json' => $data,

		];
		$this->doCall($uri, $headers);
	}

	/**
	 * @return string
	 */
	public function getUri(): string
	{
		if ($this->parent !== null) {
			return sprintf('%s%s/%s/%s', self::API_PREFIX, $this->parent->path, $this->parent->id, $this->resourcePath);
		}

		return sprintf('%s%s', self::API_PREFIX, $this->resourcePath);
	}

	/**
	 * @param Response $res
	 * @param string $uri
	 *
	 * @return void
	 * @throws Exceptions\InvalidHashOnResult
	 */
	protected function validateResponse(Response $res, string $uri): void
	{
		$dataToHash = [
			$this->client->apiCredentials->GetPublic(),
			$this->getCurrentMethod(),
			$uri,
			$res->getBody(),
			$res->getHeader('x-date')[0],

		];

		$xHash = hash_hmac('sha512', implode('|', $dataToHash), $this->client->apiCredentials->GetSecret());

		if ($xHash !== $res->getHeader('x-hash')[0]) {
			throw new InvalidHashOnResult('Result hash not equal');
		}
	}

	/**
	 * @param array $filters
	 *
	 * @return string
	 */
	protected function filtersToQuery(array $filters = []): string
	{
		if (empty($filters)) {
			return '';
		}

		return '?' . http_build_query($filters);
	}

	/**
	 * @param string $uri
	 * @param array $data
	 *
	 * @return null|stdClass
	 * @throws Exceptions\InvalidHashOnResult
	 * @throws GuzzleException
	 */
	private function doCall(string $uri, array $data): ?stdClass
	{
		$client = new Client([
			'base_uri' => $this->client->apiCredentials->GetHostName(),
		]);
		try {
			$res = $client->request($this->getCurrentMethod(), $uri, $data);

			$this->validateResponse($res, $uri);

			if (empty((string)$res->getBody())) {
				return null;
			}

			try {
				return json_decode((string)$res->getBody(), false, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				throw new InvalidResponseException($e->getMessage());
			}
		} catch (ServerException|ClientException $e) {
			throw  ExceptionFactory::createFromApiResult((string)$e->getResponse()->getBody());
		}
	}

	/**
	 * @throws JsonException
	 */
	private function getHash(string $uri, ?array $data = null): string
	{
		$dataToHash = [
			$this->client->apiCredentials->GetPublic(),
			$this->getCurrentMethod(),
			$uri,
			$data !== null ? json_encode($data, JSON_THROW_ON_ERROR) : '',
			$this->getCurrentDate(),

		];

		return hash_hmac('sha512', implode('|', $dataToHash), $this->client->apiCredentials->GetSecret());
	}

	/**
	 * @return string|null
	 */
	private function getCurrentMethod(): ?string
	{
		return $this->currentMethod;
	}

	/**
	 * @param string|null $currentMethod
	 *
	 * @return $this
	 */
	private function setCurrentMethod(?string $currentMethod): self
	{
		$this->currentMethod = $currentMethod;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getCurrentDate(): string
	{
		return $this->currentDate;
	}

	/**
	 * @return $this
	 */
	public function setCurrentDate(): self
	{
		$this->currentDate = Carbon::Now('utc')->format('c');

		return $this;
	}

	/**
	 * @return string
	 */
	public function getResourcePath(): string
	{
		return $this->resourcePath;
	}

	/**
	 * @param stdClass|null $parent
	 */
	protected function SetParent(?stdClass $parent): void
	{
		$this->parent = $parent;
	}
}
