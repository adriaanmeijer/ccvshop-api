<?php

namespace CCVShop\Api\Endpoints;

use CCVShop\Api\BaseEndpoint;
use CCVShop\Api\Exceptions\InvalidHashOnResult;
use CCVShop\Api\Factory\ResourceFactory;
use CCVShop\Api\Interfaces\Endpoints\Get;
use CCVShop\Api\Interfaces\Endpoints\GetAll;
use CCVShop\Api\Resources\Credential;
use CCVShop\Api\Resources\CredentialCollection;
use CCVShop\Api\Resources\Webshop;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class Credentials extends BaseEndpoint implements
    Get,
    GetAll
{
    protected string $resourcePath = 'credentials';
    protected ?string $parentResourcePath = 'webshops';

    /**
     * @return Credential
     */
    protected function getResourceObject(): Credential
    {
        return new Credential($this->client);
    }

    /**
     * @return CredentialCollection<Credential>
     */
    protected function getResourceCollectionObject(): CredentialCollection
    {
        return new CredentialCollection();
    }

    /**
     * @param int $id
     *
     * @return Credential
     * @throws InvalidHashOnResult
     * @throws GuzzleException
     * @throws JsonException
     */
    public function get(int $id): Credential
    {
        /** @var Credential $result */
        $result = $this->rest_getOne($id, []);

        return $result;
    }

    /**
     * @param array $parameters
     *
     * @return CredentialCollection<Credential>
     * @throws InvalidHashOnResult
     * @throws GuzzleException
     */
    public function getAll(array $parameters = []): CredentialCollection
    {
        /** @var CredentialCollection<Credential> $collection */
        $collection = $this->rest_getAll(null, null, $parameters);

        return $collection;
    }

    /**
     * @param Webshop $webshop
     * @param array $data
     *
     * @return Credential
     * @throws InvalidHashOnResult
     * @throws GuzzleException
     * @throws JsonException
     */
    public function postFor(Webshop $webshop, array $data): Credential
    {
        $this->SetParent(ResourceFactory::createParentFromResource($webshop));

        /** @var Credential $result */
        $result = $this->rest_post($data);

        return $result;
    }

    /**
     * @param int $webshopId
     * @param array $data
     *
     * @return Credential
     * @throws InvalidHashOnResult
     * @throws GuzzleException
     * @throws JsonException
     */
    public function postForId(int $webshopId, array $data): Credential
    {
        $this->SetParent(ResourceFactory::createParent($this->client->webshops->getResourcePath(), $webshopId));

        /** @var Credential $result */
        $result = $this->rest_post($data);

        return $result;
    }
}
