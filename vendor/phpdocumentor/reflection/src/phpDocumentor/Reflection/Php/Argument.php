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

use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\Mixed_;

/**
 * Descriptor representing a single Argument of a method or function.
 *
 * @api
 */
final class Argument
{
    /** @var Type a normalized type that should be in this Argument */
    private readonly Type $type;

    /**
     * Initializes the object.
     */
    public function __construct(
        /** @var string name of the Argument */
        private readonly string $name,
        Type|null $type = null,
        /** @var string|null the default value for an argument or null if none is provided */
        private readonly string|null $default = null,
        /** @var bool whether the argument passes the parameter by reference instead of by value */
        private readonly bool $byReference = false,
        /** @var bool Determines if this Argument represents a variadic argument */
        private readonly bool $isVariadic = false,
    ) {
        if ($type === null) {
            $type = new Mixed_();
        }

        $this->type = $type;
    }

    /**
     * Returns the name of this argument.
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type|null
    {
        return $this->type;
    }

    public function getDefault(): string|null
    {
        return $this->default;
    }

    public function isByReference(): bool
    {
        return $this->byReference;
    }

    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }
}
