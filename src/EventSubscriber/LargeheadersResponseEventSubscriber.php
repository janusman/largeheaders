<?php

namespace Drupal\largeheaders\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Logger\LoggerChannelTrait;

/**
 * Response Event Subscriber for Large Headers.
 */
class LargeheadersResponseEventSubscriber implements EventSubscriberInterface {

  use LoggerChannelTrait;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * Constructs a new response event subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher) {
    $this->config = $config_factory->get('largeheaders.settings');
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
  }

  public function processHeaders(ResponseEvent $event) {
    // Threshold for length of a single header.
    $length_threshold = intval($this->config->get("length_threshold"));
    // Threshold for length of the total header payload (in bytes).
    $total_data_threshold = intval($this->config->get("total_data_threshold"));
    // Threshold for number of individual headers.
    $num_headers_threshold = intval($this->config->get("num_headers_threshold"));

    // Get the headers.
    $response = $event->getResponse();
    $response_headers = $response->headers->all();

    // Determine if anything in the headers exceeds thresholds.
    $reasons = [];
    $full_header_data = '';
    foreach ($response_headers as $key => $value) {
      $value = implode(" ", $value);
      if (strlen($value) > $length_threshold) {
        $reasons['one_or_more_large_headers'] = 1;
      }
      $full_header_data .= "$key: $value\n";
    }
    if (count($response_headers) > $num_headers_threshold) {
      $reasons['too_many_headers'] = 1;
    }
    if (strlen($full_header_data) > $total_data_threshold) {
      $reasons['large_combined_header_size'] = 1;
    }

    // Log if any reason found.
    if (count($reasons)) {
      $this->logLargeHeader($event, $full_header_data, $reasons);
    }
  }

  public function logLargeHeader(ResponseEvent $event, string $full_header_data, array $reasons) {
    static $current_path = null;

    if (empty($current_path)) {
      $request = $event->getRequest();
      $path_info = $request->getPathInfo();
      $request_method = $request->getMethod();
      $current_path = $this->aliasManager->getPathByAlias($path_info);
    }
    else {
      // Do not log things twice!
      return;
    }

    // Build a unique ID to identify this request.
    // If the request contains an x-request-id header, use that value.
    $uuid = md5(sprintf("%f", microtime(TRUE)));
    if ($request->headers->get("x-request-id")) {
      $uuid = $request->headers->get("x-request-id");
    }

    // Log contents.
    $log_contents = gmdate("Y-m-d H:i:s T") . "\n";
    $log_contents .= " Request UUID: $uuid\n";
    $log_contents .= " Request: $request_method $current_path\n";
    $log_contents .= " Reasons: " . implode(" ", array_keys($reasons)) . "\n";
    $log_contents .= " Headers:\n\n";
    $log_contents .= $full_header_data . "\n\n";
    $log_contents .= "----------------------------\n";

    // Write log file.
    $this->addToLog($log_contents);

    // Log watchdog informing about large headers found.
    $this->getLogger('largeheaders')->error(
      'Found large header(s): request @request ; UUID @uuid ; reasons [@reasons]',
      [
        '@request' => "$request_method $current_path",
        '@uuid' => $uuid,
        '@reasons' => implode(",", array_keys($reasons))
      ]
    );
  }

  // Return the max size of the log file (in bytes).
  function getMaxLogSize() {
    return 512000;
  }

  // Add content to the log file.
  function addToLog($data) {
    // Log folder and filename.
    $log_filename = $this->getLogFilename();
    if (!$log_filename) {
      // Stop processing if could not determine log filename.
      return;
    }

    // Rotate log file?
    if ($this->shouldRotateLog()) {
      $this->rotateLog();
    }

    // Write data to log.
    file_put_contents($log_filename, $data, FILE_APPEND);
  }

  // Rotate the log file.
  function rotateLog() {
    $log_filename = $this->getLogFilename();
    $rotated_filename = $log_filename . ".1";
    if (file_exists($rotated_filename)) {
      @unlink($rotated_filename);
    }
    rename($log_filename, $rotated_filename);
  }

  // Determine if the log file should be rotated.
  function shouldRotateLog() {
    $max_size = $this->getMaxLogSize();
    if (@filesize($this->getLogFilename()) > $max_size) {
      return true;
    }
    return false;
  }

  // Return the full path to the log file.
  function getLogFilename() {

    $tmp = \Drupal::service("file_system")->getTempDirectory();
    if ($tmp && file_exists($tmp)) {
      return $tmp . '/largeheaders.log';
    }
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['processHeaders'];
    return $events;
  }

}
