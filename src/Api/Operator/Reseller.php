<?php
// Copyright 1999-2022. Plesk International GmbH.

namespace PleskX\Api\Operator;

use PleskX\Api\Struct\Reseller as Struct;

class Reseller extends \PleskX\Api\Operator
{
    public function create(array $properties, string $planName): Struct\Info
    {
        $packet = $this->client->getPacket();
        $add = $packet->addChild($this->wrapperTag)->addChild('add');
        $info = $add->addChild('gen-info');

        $add->{'plan-name'} = $planName;

        foreach ($properties as $name => $value) {
            $info->{$name} = $value;
        }

        $response = $this->client->request($packet);

        return new Struct\Info($response);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function delete(string $field, $value): bool
    {
        return $this->deleteBy($field, $value);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\GeneralInfo
     */
    public function get(string $field, $value): Struct\GeneralInfo
    {
        $items = $this->getAll($field, $value);

        return reset($items);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function enable(string $field, $value): bool
    {
        return $this->setProperties($field, $value, ['status' => 0]);
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return bool
     */
    public function disable(string $field, $value): bool
    {
        return $this->setProperties($field, $value, ['status' => 1]);
    }

    /**
     * @param string $field
     * @param int|string $value
     * @param array $properties
     *
     * @return bool
     */
    public function setProperties(string $field, $value, array $properties): bool
    {
        $packet = $this->client->getPacket();
        $setTag = $packet->addChild($this->wrapperTag)->addChild('set');
        $setTag->addChild('filter')->addChild($field, (string) $value);
        $genInfoTag = $setTag->addChild('values')->addChild('gen-info');
        foreach ($properties as $property => $propertyValue) {
            $genInfoTag->addChild($property, (string) $propertyValue);
        }

        $response = $this->client->request($packet);

        return 'ok' === (string) $response->status;
    }

    /**
     * @param string $field
     * @param int|string $value
     *
     * @return Struct\GeneralInfo[]
     */
    public function getAll($field = null, $value = null): array
    {
        $packet = $this->client->getPacket();
        $getTag = $packet->addChild($this->wrapperTag)->addChild('get');

        $filterTag = $getTag->addChild('filter');
        if (!is_null($field)) {
            $filterTag->addChild($field, (string) $value);
        }

        $datasetTag = $getTag->addChild('dataset');
        $datasetTag->addChild('gen-info');
        $datasetTag->addChild('permissions');

        $response = $this->client->request($packet, \PleskX\Api\Client::RESPONSE_FULL);

        $items = [];
        foreach ($response->xpath('//result') as $xmlResult) {
            $item = new Struct\GeneralInfo($xmlResult->data);
            $item->id = (int) $xmlResult->id;
            $items[] = $item;
        }

        return $items;
    }
}
