<?php

declare(strict_types=1);

namespace LaminasTest\Hydrator;

use Laminas\Hydrator\NamingStrategy\NamingStrategyInterface;
use Laminas\Hydrator\Strategy\StrategyInterface;
use LaminasTest\Hydrator\TestAsset\SimpleEntity;

use function sprintf;

trait HydratorTestTrait
{
    public function testHydrateWithNamingStrategyAndStrategy(): void
    {
        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $namingStrategy
            ->expects($this->any())
            ->method('hydrate')
            ->with($this->anything())
            ->will($this->returnValue('value'));

        $strategy = $this->createMock(StrategyInterface::class);
        $strategy
            ->expects($this->any())
            ->method('hydrate')
            ->with($this->anything())
            ->will($this->returnValue('hydrate'));

        $this->hydrator->setNamingStrategy($namingStrategy);
        $this->hydrator->addStrategy('value', $strategy);

        $entity = $this->hydrator->hydrate(['foo_bar_baz' => 'blub'], new SimpleEntity());
        $this->assertSame(
            'hydrate',
            $entity->getValue(),
            sprintf('Hydrator: %s', $this->hydrator::class)
        );
    }

    public function testExtractWithNamingStrategyAndStrategy(): void
    {
        $entity = new SimpleEntity();
        $entity->setValue('foo');

        $namingStrategy = $this->createMock(NamingStrategyInterface::class);
        $namingStrategy
            ->expects($this->any())
            ->method('extract')
            ->with($this->anything())
            ->will($this->returnValue('extractedName'));

        $strategy = $this->createMock(StrategyInterface::class);
        $strategy
            ->expects($this->any())
            ->method('extract')
            ->with($this->anything())
            ->will($this->returnValue('extractedValue'));

        $this->hydrator->setNamingStrategy($namingStrategy);
        $this->hydrator->addStrategy('extractedName', $strategy);

        $data = $this->hydrator->extract($entity);

        $this->assertSame(['extractedName' => 'extractedValue'], $data);
    }
}
