<?php namespace peer\ftp\unittest;

use lang\{FormatException, IllegalArgumentException};
use peer\URL;
use peer\ftp\FtpConnection;
use unittest\{Expect, Test, Values};

class FtpConnectionTest extends \unittest\TestCase {

  /** @return var[][] */
  private function dsns() {
    return [
      ['ftp://localhost'],
      ['ftp://localhost:21'],
      ['ftp://test:test@localhost'],
      ['ftp://test:test@localhost:21'],
      ['ftp://localhost?passive=true'],
      ['ftp://localhost?passive=false'],
      ['ftp://localhost?timeout=1.0'],
      ['ftp://localhost?passive=false&timeout=2.0'],
      ['ftps://localhost']
    ];
  }

  #[Test, Values('dsns')]
  public function can_create($dsn) {
    new FtpConnection($dsn);
  }

  #[Test, Values('dsns')]
  public function can_create_with_url($dsn) {
    new FtpConnection(new URL($dsn));
  }

  #[Test, Expect(FormatException::class)]
  public function raises_error_for_malformed() {
    new FtpConnection('');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_for_unsupported_scheme() {
    new FtpConnection('http://localhost');
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function raises_error_for_unsupported_passive_mode() {
    new FtpConnection('ftp://localhost?passive=@INVALID@');
  }

  #[Test]
  public function timeout_defaults_to_four_seconds() {
    $this->assertEquals(4.0, (new FtpConnection('ftp://localhost'))->timeout());
  }

  #[Test]
  public function timeout_can_be_set_via_dsn() {
    $this->assertEquals(1.0, (new FtpConnection('ftp://localhost?timeout=1.0'))->timeout());
  }

  #[Test]
  public function passive_mode_defaults_to_true() {
    $this->assertEquals(true, (new FtpConnection('ftp://localhost'))->passive());
  }

  #[Test, Values([['false', false], ['off', false], ['no', false], ['0', false], ['true', true], ['on', true], ['yes', true], ['1', true]])]
  public function passive_mode_can_be_set_via_dsn($value, $result) {
    $this->assertEquals($result, (new FtpConnection('ftp://localhost?passive='.$value))->passive());
  }

  #[Test]
  public function remote_endpoint() {
    $this->assertEquals('localhost:21', (new FtpConnection('ftp://localhost'))->remoteEndpoint()->getAddress());
  }

  #[Test]
  public function remote_endpoint_with_non_default_port() {
    $this->assertEquals('localhost:2121', (new FtpConnection('ftp://localhost:2121'))->remoteEndpoint()->getAddress());
  }

  #[Test]
  public function anonymous_user() {
    $this->assertNull((new FtpConnection('ftp://localhost'))->user());
  }

  #[Test]
  public function authenticated_user() {
    $this->assertEquals('test', (new FtpConnection('ftp://test:pass@localhost'))->user());
  }
}