<?php

namespace Drupal\Tests\largeheaders\Unit\EventSubscriber;

use Drupal\Tests\UnitTestCase;
use Drupal\largeheaders\EventSubscriber\LargeheadersResponseEventSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Event Subscriber.
 */
class LargeheadersResponseEventSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'Large Headers Response Event Subscriber',
      'description' => 'Tests the Large Headers response event subscriber',
      'group' => 'largeheaders',
    ];
  }

  /**
   * TODO.
   */
  protected function setupSubscriber() {
    // TODO: Add all the settings.
    $config_factory = $this->getConfigFactoryStub(["largeheaders.settings" => ["length_threshold" => 100]]);
    $alias_manager = $this->getMock('Drupal\Core\Path\AliasManagerInterface');
    $path_matcher = $this->getMock('Drupal\Core\Path\PathMatcherInterface');
    //$path_matcher->expects($this->any())
    //  ->method('matchPath')
    //  ->withAnyParameters()
    //  ->will($this->returnValue(TRUE));

    // Create the response event subscriber.
    $subscriber = new LargeheadersResponseEventSubscriber($config_factory, $alias_manager, $path_matcher);
    return $subscriber;
  }

  /**
   * TODO,
   */
  public function testRequest() {
    $subscriber = $this->setupSubscriber();

    // Create the response event.
    $http_kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = Request::create('/example', 'GET');
    // TODO: add large headers here...
    $response = new Response();
    $event = new FilterResponseEvent($http_kernel, $request, HttpKernelInterface::MASTER_REQUEST, $response);

    // Call the event handler.
    $subscriber->processHeaders($event);

    // How would we check that it worked?
    $this->assertEquals(1, 1);
  }

}
