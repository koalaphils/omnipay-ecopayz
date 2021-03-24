<?php

namespace ApiBundle\Parser;

use ApiBundle\Parser\JmsMetadataParser;
use Nelmio\ApiDocBundle\Parser\ParserInterface;

class CollectionParser implements ParserInterface
{
    private $jmsMetadataParser;

    public function __construct(JmsMetadataParser $jmsMetadataParser)
    {
        $this->jmsMetadataParser = $jmsMetadataParser;
    }

    public function parse(array $item): array
    {
        list($className, $type) = $this->getClassType($item);

        if (empty($className) || empty($type)) {
            return false;
        }

        $exp = explode("\\", $className);

        $item['class'] = $className;

        $returnData = [
            'dataType' => $type,
            'required' => true,
            'description' => sprintf("%s of objects (%s)", $type, end($exp)),
            'readonly' => false,
            'children' => $this->jmsMetadataParser->parse($item),
        ];

        $output = [];
        $output['items[]'] = $returnData;
        $output['total'] = [
            'dataType' => 'integer',
            'required' => true,
            'description' => 'Total records',
            'readonly' => false,
        ];

        $output['total_filtered'] = [
            'dataType' => 'integer',
            'required' => true,
            'description' => 'Total filtered records',
            'readonly' => false,
        ];

        $output['page'] = [
            'dataType' => 'integer',
            'required' => true,
            'description' => 'Current Page',
            'readonly' => false,
        ];

        $output['limit'] = [
            'dataType' => 'integer',
            'required' => true,
            'description' => 'Total items per page',
            'readonly' => false,
        ];

        return $output;
    }

    public function supports(array $item): bool
    {
        list($className, $type) = $this->getClassType($item);

        if (empty($className) || empty($type)) {
            return false;
        }

        $item['class'] = $className;

        return $this->jmsMetadataParser->supports($item);
    }

    private function getClassType(array $item): array
    {
        $className = $type = '';
        if (preg_match('/(.+)\<(.+)\>/', $item['class'], $match)) {
            $className = $match[2];
            $type = $match[1];
        }

        return array($className, $type);
    }
}
