<?php
namespace EzSystems\EzPlatformGraphQL\GraphQL\InputMapper;

use eZ\Publish\API\Repository\Values\Content\Query;
use EzSystems\EzPlatformGraphQL\GraphQL\DataLoader\ContentTypeLoader;
use GraphQL\Error\UserError;
use InvalidArgumentException;

/**
 * Pre-processes the input to change fields passed using their identifier to the Field input key.
 */
class FieldsQueryMapper implements QueryMapper
{
    /**
     * @var QueryMapper
     */
    private $innerMapper;
    /**
     * @var ContentTypeLoader
     */
    private $contentTypeLoader;

    public function __construct(ContentTypeLoader $contentTypeLoader, QueryMapper $innerMapper)
    {
        $this->innerMapper = $innerMapper;
        $this->contentTypeLoader = $contentTypeLoader;
    }

    /**
     * @param array $inputArray
     * @return \eZ\Publish\API\Repository\Values\Content\Query
     */
    public function mapInputToQuery(array $inputArray)
    {
        if (isset($inputArray['ContentTypeIdentifier'])) {
            $contentType = $this->contentTypeLoader->loadByIdentifier($inputArray['ContentTypeIdentifier']);
            $fieldsArgument = [];

            foreach ($inputArray as $argument => $value) {
                if (($fieldDefinition = $contentType->getFieldDefinition($argument)) === null) {
                    continue;
                }

                if (!$fieldDefinition->isSearchable) {
                    continue;
                }

                $fieldFilter = $this->buildFieldFilter($argument, $value);
                if ($fieldFilter !== null) {
                    $fieldsArgument[] = $fieldFilter;
                }
            }

            $queryArg['Fields'] = $fieldsArgument;
        }

        return $this->innerMapper->mapInputToQuery($inputArray);
    }

    private function buildFieldFilter($fieldDefinitionIdentifier, $value)
    {
        if (is_array($value) && count($value) === 1) {
            $value = $value[0];
        }
        $operator = 'eq';

        // @todo if 3 items, and first item is 'between', use next two items as value
        if (is_array($value)) {
            $operator = 'in';
        } else if (is_string($value)) {
            if ($value[0] === '~') {
                $operator = 'like';
                $value = substr($value, 1);
                if (strpos($value, '%') === false) {
                    $value = "%$value%";
                }
            } elseif ($value[0] === '<') {
                $value = substr($value, 1);
                if ($value[0] === '=') {
                    $operator = 'lte';
                    $value = substr($value, 2);
                } else {
                    $operator = 'lt';
                    $value = substr($value, 1);
                }
            } elseif ($value[0] === '<') {
                $value = substr($value, 1);
                if ($value[0] === '=') {
                    $operator = 'gte';
                    $value = substr($value, 2);
                } else {
                    $operator = 'gt';
                    $value = substr($value, 1);
                }
            }
        }

        return ['target' => $fieldDefinitionIdentifier, $operator => trim($value)];
    }
}
