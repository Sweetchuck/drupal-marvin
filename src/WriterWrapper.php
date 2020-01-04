<?php

declare(strict_types = 1);

namespace Drupal\marvin;

use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\Filesystem\Filesystem;

class WriterWrapper {

  /**
   * @var null|\Symfony\Component\Console\Output\OutputInterface
   */
  protected $destinationInstance = NULL;

  /**
   * File handler.
   *
   * @var null|resource
   */
  protected $destinationResource = NULL;

  /**
   * @var null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  protected $destination = NULL;

  /**
   * @return null|string|\Symfony\Component\Console\Output\OutputInterface
   */
  public function getDestination() {
    return $this->destination;
  }

  /**
   * @param null|string|\Symfony\Component\Console\Output\OutputInterface $destination
   *
   * @return $this
   */
  public function setDestination($destination) {
    $this->reset();
    $this->destination = $destination;

    return $this;
  }

  /**
   * @var string
   */
  protected $destinationMode = 'w';

  public function getDestinationMode(): string {
    return $this->destinationMode;
  }

  public function setDestinationMode(string $destinationMode) {
    $this->reset();
    $this->destinationMode = $destinationMode;

    return $this;
  }

  /**
   * @see \Symfony\Component\Console\Output\OutputInterface::write
   */
  public function write($messages, $newLine = FALSE, $options = 0) {
    $this->init();
    // @todo Error if the not initialized.
    if ($this->destinationInstance) {
      $this->destinationInstance->write($messages, $newLine, $options);
    }

    return $this;
  }

  /**
   * Close the destination resource if it was opened here.
   *
   * @return $this
   */
  public function close() {
    if ($this->destinationResource) {
      fclose($this->destinationResource);
    }

    return $this;
  }

  /**
   * Initialize the output destination instance.
   *
   * @return $this
   */
  protected function init() {
    if ($this->destinationInstance) {
      return $this;
    }

    $destination = $this->getDestination();
    if (is_string($destination)) {
      $fs = new Filesystem();
      $fs->mkdir(dirname($destination));

      $this->destinationResource = fopen($destination, $this->getDestinationMode());
      if ($this->destinationResource === FALSE) {
        throw new RuntimeException("File '$destination' could not be opened.");
      }

      $this->destinationInstance = new StreamOutput(
        $this->destinationResource,
        OutputInterface::VERBOSITY_NORMAL,
        FALSE
      );

      return $this;
    }

    $this->destinationInstance = $destination;

    return $this;
  }

  /**
   * @return $this
   */
  protected function reset() {
    $this->destinationInstance = NULL;
    $this->destinationResource = NULL;

    return $this;
  }

}
