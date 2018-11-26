<?php

namespace App\Tests\src\Application\Service;

use App\Application\Service\CommandHandler;
use App\Application\Service\CreatePairSlotsCommand;
use App\Application\Service\ReadSecretCommand;
use App\Application\Service\WriteSecretCommand;
use App\Domain\ReadSlot\ReadSlot;
use App\Domain\WriteSlot\WriteSlot;
use App\Infrastructure\SlotsManagerRedis;
use PHPUnit\Framework\TestCase;
use Predis\Client;

class CommandHandlerTest extends TestCase
{
	private $manager;
	private $redis;
	private $handler;

	public function setUp()
	{
		$this->redis = new Client([
			"host" => "localhost",
			"port" => 6379
		]);

		$this->manager = new SlotsManagerRedis($this->redis);
		$this->handler = (new CommandHandler($this->manager));
	}


	/**
	 *
	 * Create pair
	 *
	 */
	public function testCreatePair()
	{
		$command = new CreatePairSlotsCommand('writeuid', 'readuid', 'sesame1234');

		$this->handler->handle($command);

		$write = $this->manager->fetchSlot('writeuid');
		$read = $this->manager->fetchSlot('readuid');

		$this->assertInstanceOf(WriteSlot::class, $write);
		$this->assertInstanceOf(ReadSlot::class, $read);
	}

	/**
	 *
	 * write secret
	 *
	 */
	public function testWriteSecretCheckFirstlyTypeOfSlot()
	{
		$command = new CreatePairSlotsCommand('writeuid', 'readuid', 'sesame1234');
		$this->handler->handle($command);
		$command = new WriteSecretCommand('writeuid', 'this is my secret');
		$this->handler->handle($command);

		$write = $this->manager->fetchSlot('writeuid');
		$read = $this->manager->fetchSlot('readuid');

		$this->assertNull($write);
		$this->assertTrue($read->getPassword() !== 'sesame1234');
		$this->assertInstanceOf(ReadSlot::class, $read);
	}

	public function testWriteSecretCheckFirstlyTypeOfSlotForWrongType()
	{
		$command = new WriteSecretCommand('writeuid', 'this is my secret');

		$stubManager = $this->createConfiguredMock(SlotsManagerRedis::class, [
			'fetchSlot' => new ReadSlot('writeuid', 'readUid')
		]);

		$result = (new CommandHandler($stubManager))->handle($command);
		$this->assertFalse($result, 'a secret cannot be written in a write read slot');
	}

	public function testWhenSlotDoesntExist()
	{
		$command = new WriteSecretCommand('writeuid', 'this is my secret');

		$stubManager = $this->createConfiguredMock(SlotsManagerRedis::class, [
			'fetchSlot' => null
		]);

		$result = (new CommandHandler($stubManager))->handle($command);
		$this->assertFalse($result, 'a secret cannot be written in a write read slot');
	}

	/**
	 *
	 * Read secret
	 *
	 */
	public function testReadSecret()
	{
		$command = new ReadSecretCommand('readuid', 'sesame1234');

		$stubManager = $this->createConfiguredMock(SlotsManagerRedis::class, [
			'fetchSlot' => new ReadSlot('writeuid', 'readUid')
		]);

		$secret = (new CommandHandler($stubManager))->handle($command);

	}
}