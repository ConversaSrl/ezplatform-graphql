<?php
/**
 * Created by PhpStorm.
 * User: bdunogier
 * Date: 07/04/2019
 * Time: 00:07
 */

namespace EzSystems\EzPlatformGraphQL\GraphQL\InputMapper;

interface QueryMapper
{
    /**
     * @param array $inputArray
     * @return \eZ\Publish\API\Repository\Values\Content\Query
     */
    public function mapInputToQuery(array $inputArray);
}