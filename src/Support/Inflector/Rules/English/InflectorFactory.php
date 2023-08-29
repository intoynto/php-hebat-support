<?php

declare(strict_types=1);

namespace Intoy\HebatSupport\Inflector\Rules\English;

use Intoy\HebatSupport\Inflector\GenericLanguageInflectorFactory;
use Intoy\HebatSupport\Inflector\Rules\Ruleset;

final class InflectorFactory extends GenericLanguageInflectorFactory
{
    protected function getSingularRuleset() : Ruleset
    {
        return Rules::getSingularRuleset();
    }

    protected function getPluralRuleset() : Ruleset
    {
        return Rules::getPluralRuleset();
    }
}
