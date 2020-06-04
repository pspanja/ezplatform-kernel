<?php

namespace eZ\Publish\Core\MVC\Symfony\Component\Serializer;

use eZ\Publish\Core\MVC\Symfony\SiteAccess\Matcher;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;

class CompoundMatcherNormalizer extends PropertyNormalizer
{
    public function normalize($object, string $format = null, array $context = array())
    {
        $data = parent::normalize($object, $format, $context);
        $data['config'] = [];
        $data['matchersMap'] = [];

        return $data;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Matcher\Compound;
    }
}
