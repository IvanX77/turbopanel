<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Reflection\Php;

use Override;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Element;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Location;
use phpDocumentor\Reflection\Metadata\MetaDataContainer as MetaDataContainerInterface;

/**
 * Descriptor representing a Trait.
 *
 * @api
 */
final class Trait_ implements Element, MetaDataContainerInterface, AttributeContainer
{
    use MetadataContainer;
    use HasAttributes;

    /** @var Property[] $properties */
    private array $properties = [];

    /** @var Method[] $methods */
    private array $methods = [];

    /** @var Fqsen[] $usedTraits References to traits consumed by this trait */
    private array $usedTraits = [];

    private readonly Location $location;

    private readonly Location $endLocation;

    /** @var Constant[] */
    private array $constants = [];

    /**
     * Initializes the all properties
     */
    public function __construct(
        /** @var Fqsen Full Qualified Structural Element Name */
        private readonly Fqsen $fqsen,
        private readonly DocBlock|null $docBlock = null,
        Location|null $location = null,
        Location|null $endLocation = null,
    ) {
        if ($location === null) {
            $location = new Location(-1);
        }

        if ($endLocation === null) {
            $endLocation = new Location(-1);
        }

        $this->location = $location;
        $this->endLocation = $endLocation;
    }

    /**
     * Returns the methods of this Trait.
     *
     * @return Method[]
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Add a method to this Trait
     */
    public function addMethod(Method $method): void
    {
        $this->methods[(string) $method->getFqsen()] = $method;
    }

    /**
     * Returns the properties of this trait.
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Add a property to this Trait.
     */
    public function addProperty(Property $property): void
    {
        $this->properties[(string) $property->getFqsen()] = $property;
    }

    /**
     * Returns the Fqsen of the element.
     */
    #[Override]
    public function getFqsen(): Fqsen
    {
        return $this->fqsen;
    }

    /**
     * Returns the name of the element.
     */
    #[Override]
    public function getName(): string
    {
        return $this->fqsen->getName();
    }

    public function getDocBlock(): DocBlock|null
    {
        return $this->docBlock;
    }

    /**
     * Returns fqsen of all traits used by this trait.
     *
     * @return Fqsen[]
     */
    public function getUsedTraits(): array
    {
        return $this->usedTraits;
    }

    /**
     * Add reference to trait used by this trait.
     */
    public function addUsedTrait(Fqsen $fqsen): void
    {
        $this->usedTraits[(string) $fqsen] = $fqsen;
    }

    public function getLocation(): Location
    {
        return $this->location;
    }

    public function getEndLocation(): Location
    {
        return $this->endLocation;
    }

    /**
     * Returns the constants of this class.
     *
     * @return Constant[]
     */
    public function getConstants(): array
    {
        return $this->constants;
    }

    /**
     * Add Constant to this class.
     */
    public function addConstant(Constant $constant): void
    {
        $this->constants[(string) $constant->getFqsen()] = $constant;
    }
}
