<?php

namespace Acquia\Orca\Tests\Console\Command\Internal;

use Acquia\Orca\Console\Command\Internal\InternalLogJobCommand;
use Acquia\Orca\Enum\StatusCode;
use Acquia\Orca\Enum\TelemetryEventName;
use Acquia\Orca\Log\TelemetryClient;
use Acquia\Orca\Log\TelemetryEventPropertiesBuilder;
use Acquia\Orca\Tests\Console\Command\CommandTestBase;
use Prophecy\Argument;
use Symfony\Component\Console\Command\Command;

/**
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Log\TelemetryClient $telemetryClient
 * @property \Prophecy\Prophecy\ObjectProphecy|\Acquia\Orca\Log\TelemetryEventPropertiesBuilder $telemetryEventBuilder
 */
class InternalLogJobCommandTest extends CommandTestBase {

  public function setUp() {
    $this->telemetryClient = $this->prophesize(TelemetryClient::class);
    $this->telemetryClient
      ->isReady()
      ->willReturn(TRUE);
    $this->telemetryEventBuilder = $this->prophesize(TelemetryEventPropertiesBuilder::class);
    $this->telemetryClient
      ->logEvent(Argument::any())
      ->shouldNotBeCalled();
  }

  protected function createCommand(): Command {
    $telemetry_client = $this->telemetryClient->reveal();
    $telemetry_event_builder = $this->telemetryEventBuilder->reveal();
    return new InternalLogJobCommand($telemetry_client, $telemetry_event_builder);
  }

  public function testHappyPath() {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1);
    $event = TelemetryEventName::TRAVIS_CI_JOB();
    $properties = ['key' => 'value'];
    $this->telemetryEventBuilder
      ->build($event)
      ->shouldBeCalledTimes(1)
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent($event->getValue(), $properties)
      ->shouldBeCalledTimes(1);

    $this->executeCommand();

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testWithTelemetryDisabled() {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1)
      ->willReturn(FALSE);

    $this->executeCommand();

    $this->assertEquals('Notice: Nothing logged. Telemetry is disabled.' . PHP_EOL .
      'Hint: https://github.com/acquia/orca/blob/master/docs/advanced-usage.md#ORCA_TELEMETRY_ENABLE' . PHP_EOL, $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testSimulateOption() {
    $properties = ['test' => 'example'];
    $this->telemetryEventBuilder
      ->build(TelemetryEventName::TRAVIS_CI_JOB())
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent(Argument::any())
      ->shouldNotBeCalled();

    $this->executeCommand(['--simulate' => TRUE]);

    $this->assertEquals(print_r($properties, TRUE), $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

  public function testTestOption() {
    $this->telemetryClient
      ->isReady()
      ->shouldBeCalledTimes(1);
    $event = TelemetryEventName::TEST();
    $properties = ['key' => 'value'];
    $this->telemetryEventBuilder
      ->build($event)
      ->shouldBeCalledTimes(1)
      ->willReturn($properties);
    $this->telemetryClient
      ->logEvent($event->getValue(), $properties)
      ->shouldBeCalledTimes(1);

    $this->executeCommand(['--test' => TRUE]);

    $this->assertEquals('', $this->getDisplay(), 'Displayed correct output.');
    $this->assertEquals(StatusCode::OK, $this->getStatusCode(), 'Returned correct status code.');
  }

}
