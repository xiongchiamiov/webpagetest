<?php

// To invoke this test, download PHPUnit 4.1 from phpunit.de, and from the
// directory where this file is located, run:
// php $PHPUNIT_LOCATION/phpunit.phar graphPageDataTest

include_once '../graph_page_data.inc';

class GraphPageDataTest extends PHPUnit_Framework_TestCase
{

  public function setUp() {
    $run1 = array('result' => 0, 'TTFB' => 100, 'docTime' => 1000);
    $run2 = array('result' => 404, 'TTFB' => 200, 'docTime' => 4000);
    $run3 = array('result' => 99999, 'TTFB' => 300, 'docTime' >= 2000);
    $this->pageData = array(array($run1, $run2), array($run2, $run3), array($run3));

    $this->expectedFromPageData = new ChartColumn(
            array(0 => 100, 2 => 300),
            'blue',
            false,
            'TTFB');
    $this->expectedMedianFromPageData = new ChartColumn(
            array(1 => 100, 2 => 100, 3 => 100),
            'blue',
            true,
            'TTFB Median');
    $this->expectedMedianRunFromPageData = new ChartColumn(
            array(1 => 100, 2 => 100, 3 => 100),
            'lightblue',
            true,
            'TTFB, Run with Median docTime');
    $this->expectedRuns = new ChartColumn(
      array(1 => 1, 2 => 2),
      null,
      null,
      'run');
  }

  public function testFromPageData() {
    $result = ChartColumn::fromPageData($this->pageData, 0, 'TTFB', 'blue', 'TTFB');
    $this->assertEquals($this->expectedFromPageData, $result);
  }

  public function testMedianFromPageData() {
    $result = ChartColumn::medianFromPageData($this->pageData, 0, 'TTFB', 'blue', 'TTFB Median');
    $this->assertEquals($this->expectedMedianFromPageData, $result);
  }

  public function testMedianRunFromPageData() {
    $result = ChartColumn::medianRunFromPageData($this->pageData, 0, 'TTFB', 'docTime', 'lightblue', 'TTFB, Run with Median docTime');
    $this->assertEquals($this->expectedMedianRunFromPageData, $result);
  }

  public function testRuns() {
    $result = ChartColumn::runs(2);
    $this->assertEquals($this->expectedRuns, $result);
  }

  public function testDataMedianColumns() {
    $expectedResult = array(
      $this->expectedFromPageData,
      $this->expectedMedianRunFromPageData,
      $this->expectedMedianFromPageData);
    $result = ChartColumn::dataMedianColumns($this->pageData, 0, 'TTFB', 'docTime', 'blue', 'lightblue', 'TTFB');
    $this->assertEquals($expectedResult, $result);
  }
}

?>
