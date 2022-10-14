<?php

declare(strict_types=1);

namespace LaminasTest\Hydrator;

use Closure;
use Laminas\Hydrator;
use Laminas\Hydrator\StandaloneHydratorPluginManager;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

use function array_pop;
use function sprintf;

class StandaloneHydratorPluginManagerTest extends TestCase
{
    private StandaloneHydratorPluginManager $manager;

    protected function setUp(): void
    {
        $this->manager = new StandaloneHydratorPluginManager();
    }

    /**
     * @return mixed
     */
    public function reflectProperty(object $class, string $property)
    {
        $r = new ReflectionProperty($class, $property);
        $r->setAccessible(true);
        return $r->getValue($class);
    }

    /**
     * @psalm-return iterable<string, array{0: string}>
     */
    public function hydratorsWithoutConstructors(): iterable
    {
        yield 'ArraySerializable'               => [Hydrator\ArraySerializableHydrator::class];
        yield 'ArraySerializableHydrator'       => [Hydrator\ArraySerializableHydrator::class];
        yield 'ClassMethods'                    => [Hydrator\ClassMethodsHydrator::class];
        yield 'ClassMethodsHydrator'            => [Hydrator\ClassMethodsHydrator::class];
        yield Hydrator\ArraySerializable::class => [Hydrator\ArraySerializableHydrator::class];
        yield Hydrator\ClassMethods::class      => [Hydrator\ClassMethodsHydrator::class];
        yield Hydrator\ObjectProperty::class    => [Hydrator\ObjectPropertyHydrator::class];
        yield Hydrator\Reflection::class        => [Hydrator\ReflectionHydrator::class];
        yield 'ObjectPropertyHydrator'          => [Hydrator\ObjectPropertyHydrator::class];
        yield 'ObjectProperty'                  => [Hydrator\ObjectPropertyHydrator::class];
        yield 'ReflectionHydrator'              => [Hydrator\ReflectionHydrator::class];
        yield 'Reflection'                      => [Hydrator\ReflectionHydrator::class];
    }

    /**
     * @dataProvider hydratorsWithoutConstructors
     */
    public function testInstantiationInitializesFactoriesForHydratorsWithoutConstructorArguments(string $class): void
    {
        $factories = $this->reflectProperty($this->manager, 'factories');

        $this->assertArrayHasKey($class, $factories);
        $this->assertInstanceOf(Closure::class, $factories[$class]);
    }

    public function testDelegatingHydratorFactoryIsInitialized(): void
    {
        $factories = $this->reflectProperty($this->manager, 'factories');
        $this->assertInstanceOf(
            Hydrator\DelegatingHydratorFactory::class,
            $factories[Hydrator\DelegatingHydrator::class]
        );
    }

    public function testHasReturnsFalseForUnknownNames(): void
    {
        $this->assertFalse($this->manager->has('unknown-service-name'));
    }

    public function knownServices(): iterable
    {
        foreach ($this->hydratorsWithoutConstructors() as $key => $data) {
            $class = array_pop($data);
            $alias = sprintf('%s alias', $key);
            $fqcn  = sprintf('%s class', $key);

            yield $alias => [$key, $class];
            yield $fqcn  => [$class, $class];
        }

        yield 'DelegatingHydrator alias' => ['DelegatingHydrator', Hydrator\DelegatingHydrator::class];
        yield 'DelegatingHydrator class' => [Hydrator\DelegatingHydrator::class, Hydrator\DelegatingHydrator::class];
    }

    /**
     * @dataProvider knownServices
     */
    public function testHasReturnsTrueForKnownServices(string $service): void
    {
        $this->assertTrue($this->manager->has($service));
    }

    public function testGetRaisesExceptionForUnknownService(): void
    {
        $this->expectException(Hydrator\Exception\MissingHydratorServiceException::class);
        $this->manager->get('unknown-service-name');
    }

    /**
     * @dataProvider knownServices
     */
    public function testGetReturnsExpectedTypesForKnownServices(string $service, string $expectedType): void
    {
        $instance = $this->manager->get($service);
        $this->assertInstanceOf($expectedType, $instance);
    }
}
