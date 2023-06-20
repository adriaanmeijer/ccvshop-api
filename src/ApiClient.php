<?php
declare(strict_types=1);

namespace CCVShop\Api;

use CCVShop\Api\Endpoints\Credentials;
use CCVShop\Api\Endpoints\Webshops;
use CCVShop\Api\Resources\Merchant;

class ApiClient
{
	/**
	 * Stores Credentials of the API connection.
	 * @var ApiCredentials
	 */
	public ApiCredentials $apiCredentials;
	public Credentials $credentials;
	public Endpoints\Merchant $merchant;
	public Webshops $webshops;

	/**
	 * @param string|null $hostName Fallback in .env['CCVSHOP_API_HOSTNAME']
	 * @param string|null $apiPublic Fallback in .env['CCVSHOP_API_PUBLIC']
	 * @param string|null $apiSecret Fallback in .env['CCVSHOP_API_SECRET']
	 */
	public function __construct(?string $hostName = null, ?string $apiPublic = null, ?string $apiSecret = null)
	{
		$this->apiCredentials = new ApiCredentials(
			$hostName ?? $_ENV['CCVSHOP_API_HOSTNAME'],
			$apiPublic ?? $_ENV['CCVSHOP_API_PUBLIC'],
			$apiSecret ?? $_ENV['CCVSHOP_API_SECRET']
		);

		$this->credentials = new Credentials($this);
		$this->merchant    = new Endpoints\Merchant($this);
		$this->webshops    = new Webshops($this);
	}
}
