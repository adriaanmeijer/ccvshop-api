<?php

namespace CCVShop\Api\Resources;

use CCVShop\Api\BaseResource;
use CCVShop\Api\Endpoints\Credentials;

class Webshop extends BaseResource
{
	public ?string $href = null;
	public ?int $id = null;
	public ?string $name = null;
	public ?bool $is_multishop_system = null;
	public ?int $product_limit = null;
	public ?int $product_limit_left = null;
	public ?string $api_root = null;

	public function getMerchant(): Merchant
	{
		return $this->client->merchant->getFor($this);
	}

	public function getCredentials(): Credential
	{
		return $this->client->credentials->getFor($this);
	}

	public function postCredentials(array $data): Credential
	{
		return $this->client->credentials->postFor($this, $data);
	}
}
