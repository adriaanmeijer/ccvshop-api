<?php

namespace CCVShop\Api\Factory;

use CCVShop\Api\BaseResource;

class ResourceFactory
{
    public static function createFromApiResult($apiResult, BaseResource $resource): BaseResource
    {
        foreach ($apiResult as $property => $value) {
            if (!property_exists($resource, $property)) {
                continue;
            }
            $resource->{$property} = $value;
        }

        return $resource;
    }

    public static function createParentFromResource(BaseResource $resource)
    {
        $parent       = new \stdClass();
        $parent->path = $resource->getEndpoint()->getResourcePath();
        $parent->id   = $resource->id;

        return $parent;
    }

    public static function createParent(string $path, int $id)
    {
        $parent       = new \stdClass();
        $parent->path = $path;
        $parent->id   = $id;

        return $parent;
    }
}
