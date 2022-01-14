<?php

/*
 * UserFrosting Framework (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/framework
 * @copyright Copyright (c) 2013-2021 Alexander Weissman, Louis Charette, Jordan Mele
 * @license   https://github.com/userfrosting/framework/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Tests\Bakery;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Symfony\Component\Console\Command\Command;
use UserFrosting\Bakery\SprinkleCommandsRepository;
use UserFrosting\Sprinkle\SprinkleManager;
use UserFrosting\Sprinkle\SprinkleRecipe;
use UserFrosting\Support\Exception\BadClassNameException;
use UserFrosting\Support\Exception\BadInstanceOfException;

class SprinkleCommandsRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetAll(): void
    {
        $command = Mockery::mock(Command::class);

        /** @var SprinkleRecipe */
        $sprinkle = Mockery::mock(SprinkleRecipe::class)
            ->shouldReceive('getBakeryCommands')->andReturn([$command::class])
            ->getMock();

        /** @var SprinkleManager */
        $sprinkleManager = Mockery::mock(SprinkleManager::class)
            ->shouldReceive('getSprinkles')->andReturn([$sprinkle])
            ->getMock();

        /** @var ContainerInterface */
        $ci = Mockery::mock(ContainerInterface::class)
            ->shouldReceive('get')->with($command::class)->andReturn($command)
            ->getMock();

        $repository = new SprinkleCommandsRepository($sprinkleManager, $ci);
        $classes = $repository->all();

        $this->assertCount(1, $classes);
        $this->assertSame($command, $classes[0]);
    }

    public function testGetAllWithCommandNotFound(): void
    {
        /** @var SprinkleRecipe */
        $sprinkle = Mockery::mock(SprinkleRecipe::class)
            ->shouldReceive('getBakeryCommands')->andReturn(['/Not/Command'])
            ->getMock();

        /** @var SprinkleManager */
        $sprinkleManager = Mockery::mock(SprinkleManager::class)
            ->shouldReceive('getSprinkles')->andReturn([$sprinkle])
            ->getMock();

        /** @var ContainerInterface */
        $ci = Mockery::mock(ContainerInterface::class);

        $repository = new SprinkleCommandsRepository($sprinkleManager, $ci);

        $this->expectException(BadClassNameException::class);
        $this->expectExceptionMessage('Bakery command class `/Not/Command` not found.');
        $repository->all();
    }

    public function testGetAllWithCommandWrongInterface(): void
    {
        $command = Mockery::mock(stdClass::class);

        /** @var SprinkleRecipe */
        $sprinkle = Mockery::mock(SprinkleRecipe::class)
            ->shouldReceive('getBakeryCommands')->andReturn([$command::class])
            ->getMock();

        /** @var SprinkleManager */
        $sprinkleManager = Mockery::mock(SprinkleManager::class)
            ->shouldReceive('getSprinkles')->andReturn([$sprinkle])
            ->getMock();

        /** @var ContainerInterface */
        $ci = Mockery::mock(ContainerInterface::class)
            ->shouldReceive('get')->with($command::class)->andReturn($command)
            ->getMock();

        $repository = new SprinkleCommandsRepository($sprinkleManager, $ci);

        $this->expectException(BadInstanceOfException::class);
        $this->expectExceptionMessage('Bakery command class `'.$command::class."` doesn't implement Symfony\Component\Console\Command\Command.");
        $repository->all();
    }
}