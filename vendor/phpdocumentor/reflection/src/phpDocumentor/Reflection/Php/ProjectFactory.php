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
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\Exception;
use phpDocumentor\Reflection\File as SourceFile;
use phpDocumentor\Reflection\Fqsen;
use phpDocumentor\Reflection\Php\Factory\Class_;
use phpDocumentor\Reflection\Php\Factory\ClassConstant;
use phpDocumentor\Reflection\Php\Factory\ConstructorPromotion;
use phpDocumentor\Reflection\Php\Factory\ContextStack;
use phpDocumentor\Reflection\Php\Factory\Define;
use phpDocumentor\Reflection\Php\Factory\Enum_;
use phpDocumentor\Reflection\Php\Factory\EnumCase;
use phpDocumentor\Reflection\Php\Factory\Function_;
use phpDocumentor\Reflection\Php\Factory\GlobalConstant;
use phpDocumentor\Reflection\Php\Factory\IfStatement;
use phpDocumentor\Reflection\Php\Factory\Interface_;
use phpDocumentor\Reflection\Php\Factory\Method;
use phpDocumentor\Reflection\Php\Factory\Noop;
use phpDocumentor\Reflection\Php\Factory\Property;
use phpDocumentor\Reflection\Php\Factory\Reducer\Attribute;
use phpDocumentor\Reflection\Php\Factory\Reducer\Parameter;
use phpDocumentor\Reflection\Php\Factory\Trait_;
use phpDocumentor\Reflection\Php\Factory\TraitUse;
use phpDocumentor\Reflection\Project as ProjectInterface;
use phpDocumentor\Reflection\ProjectFactory as ProjectFactoryInterface;
use PhpParser\PrettyPrinter\Standard as PrettyPrinter;

use function is_array;

use const PHP_INT_MAX;

/**
 * Factory class to transform files into a project description.
 */
final class ProjectFactory implements ProjectFactoryInterface
{
    private readonly ProjectFactoryStrategies $strategies;

    /**
     * Initializes the factory with a number of strategies.
     *
     * @param ProjectFactoryStrategy[]|ProjectFactoryStrategies $strategies
     */
    public function __construct(array|ProjectFactoryStrategies $strategies)
    {
        $this->strategies = is_array($strategies) ? new ProjectFactoryStrategies($strategies) : $strategies;
    }

    /**
     * Creates a new instance of this factory. With all default strategies.
     */
    public static function createInstance(): self
    {
        $docblockFactory = DocBlockFactory::createInstance();

        $attributeReducer = new Attribute();
        $parameterReducer = new Parameter(new PrettyPrinter());

        $methodStrategy =  new Method($docblockFactory, [$attributeReducer, $parameterReducer]);

        $strategies = new ProjectFactoryStrategies(
            [
                new \phpDocumentor\Reflection\Php\Factory\Namespace_(),
                new Class_($docblockFactory, [$attributeReducer]),
                new Enum_($docblockFactory, [$attributeReducer]),
                new EnumCase($docblockFactory, new PrettyPrinter(), [$attributeReducer]),
                new Define($docblockFactory, new PrettyPrinter()),
                new GlobalConstant($docblockFactory, new PrettyPrinter()),
                new ClassConstant($docblockFactory, new PrettyPrinter(), [$attributeReducer]),
                new Factory\File($docblockFactory, NodesFactory::createInstance()),
                new Function_($docblockFactory, [$attributeReducer, $parameterReducer]),
                new Interface_($docblockFactory, [$attributeReducer]),
                $methodStrategy,
                new Property($docblockFactory, new PrettyPrinter(), [$attributeReducer, $parameterReducer]),
                new Trait_($docblockFactory, [$attributeReducer]),

                new IfStatement(),
                new TraitUse(),
            ],
        );

        $strategies->addStrategy(
            new ConstructorPromotion($methodStrategy, $docblockFactory, new PrettyPrinter(), [$attributeReducer, $parameterReducer]),
            1100,
        );
        $strategies->addStrategy(new Noop(), -PHP_INT_MAX);

        return new self($strategies);
    }

    public function addStrategy(
        ProjectFactoryStrategy $strategy,
        int $priority = ProjectFactoryStrategies::DEFAULT_PRIORITY,
    ): void {
        $this->strategies->addStrategy($strategy, $priority);
    }

    /**
     * Creates a project from the set of files.
     *
     * @param SourceFile[] $files
     *
     * @throws Exception When no matching strategy was found.
     */
    #[Override]
    public function create(string $name, array $files): ProjectInterface
    {
        $contextStack = new ContextStack(new Project($name), null);

        foreach ($files as $filePath) {
            $strategy = $this->strategies->findMatching($contextStack, $filePath);
            $strategy->create($contextStack, $filePath, $this->strategies);
        }

        $project = $contextStack->getProject();
        $this->buildNamespaces($project);

        return $project;
    }

    /**
     * Builds the namespace tree with all elements in the project.
     */
    private function buildNamespaces(Project $project): void
    {
        foreach ($project->getFiles() as $file) {
            foreach ($file->getNamespaces() as $namespaceFqsen) {
                $namespace = $this->getNamespaceByName($project, (string) $namespaceFqsen);
                $this->buildNamespace($file, $namespace);
            }
        }
    }

    /**
     * Gets Namespace from the project if it exists, otherwise returns a new namepace
     */
    private function getNamespaceByName(Project $project, string $name): Namespace_
    {
        $existingNamespaces = $project->getNamespaces();

        if (isset($existingNamespaces[$name])) {
            return $existingNamespaces[$name];
        }

        $namespace = new Namespace_(new Fqsen($name));
        $project->addNamespace($namespace);

        return $namespace;
    }

    /**
     * Adds all elements belonging to the namespace to the namespace.
     */
    private function buildNamespace(File $file, Namespace_ $namespace): void
    {
        foreach ($file->getClasses() as $class) {
            if ($namespace->getFqsen() . '\\' . $class->getName() !== (string) $class->getFqsen()) {
                continue;
            }

            $namespace->addClass($class->getFqsen());
        }

        foreach ($file->getInterfaces() as $interface) {
            if ($namespace->getFqsen() . '\\' . $interface->getName() !== (string) $interface->getFqsen()) {
                continue;
            }

            $namespace->addInterface($interface->getFqsen());
        }

        foreach ($file->getFunctions() as $function) {
            if ($namespace->getFqsen() . '\\' . $function->getName() . '()' !== (string) $function->getFqsen()) {
                continue;
            }

            $namespace->addFunction($function->getFqsen());
        }

        foreach ($file->getConstants() as $constant) {
            if (
                $namespace->getFqsen() . '::' . $constant->getName() !== (string) $constant->getFqsen() &&
                $namespace->getFqsen() . '\\' . $constant->getName() !== (string) $constant->getFqsen()
            ) {
                continue;
            }

            $namespace->addConstant($constant->getFqsen());
        }

        foreach ($file->getTraits() as $trait) {
            if ($namespace->getFqsen() . '\\' . $trait->getName() !== (string) $trait->getFqsen()) {
                continue;
            }

            $namespace->addTrait($trait->getFqsen());
        }
    }
}
