<?php

declare(strict_types=1);

namespace phpDocumentor\Reflection\Php\Factory;

use InvalidArgumentException;
use Override;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Class_;
use phpDocumentor\Reflection\Php\Enum_;
use phpDocumentor\Reflection\Php\ProjectFactoryStrategy;
use phpDocumentor\Reflection\Php\StrategyContainer;
use phpDocumentor\Reflection\Php\Trait_;
use PhpParser\Node\Stmt\TraitUse as TraitUseNode;

final class TraitUse implements ProjectFactoryStrategy
{
    #[Override]
    public function matches(ContextStack $context, object $object): bool
    {
        return $object instanceof TraitUseNode;
    }

    /**
     * @param ContextStack $context of the created object
     * @param TraitUseNode $object
     */
    #[Override]
    public function create(ContextStack $context, object $object, StrategyContainer $strategies): void
    {
        if ($this->matches($context, $object) === false) {
            throw new InvalidArgumentException('Does not match expected node');
        }

        $class = $context->peek();

        if (
            $class instanceof Class_ === false
            && $class instanceof Trait_ === false
            && $class instanceof Enum_ === false
        ) {
            throw new InvalidArgumentException('Traits can only be used in classes, enums or other traits');
        }

        foreach ($object->traits as $trait) {
            $class->addUsedTrait(new Fqsen($trait->toCodeString()));
        }
    }
}
