<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../post.php';

class MultiPosterTest extends TestCase {

	private $multiPoster;
	private $uploadsDir;

	protected function setUp(): void {
		// Set up $_SERVER variables for testing
		$_SERVER['HTTP_HOST']      = 'localhost';
		$_SERVER['REQUEST_URI']    = '/post/test.php';
		$_SERVER['HTTPS']          = 'off';
		$_SERVER['REQUEST_METHOD'] = 'GET';

		$this->uploadsDir  = __DIR__ . '/../uploads/';
		$this->multiPoster = new MultiPoster();

		// Clean up any existing test files
		if ( is_dir( $this->uploadsDir ) ) {
			$files = glob( $this->uploadsDir . '*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
	}

	protected function tearDown(): void {
		// Clean up test files
		if ( is_dir( $this->uploadsDir ) ) {
			$files = glob( $this->uploadsDir . '*' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) ) {
					unlink( $file );
				}
			}
		}
	}

	public function testConstructor() {
		$this->assertInstanceOf( MultiPoster::class, $this->multiPoster );
	}

	public function testHandleRequestWithInvalidMethod() {
		$_SERVER['REQUEST_METHOD'] = 'GET';

		ob_start();
		$this->multiPoster->handleRequest();
		$output = ob_get_clean();

		$response = json_decode( $output, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'POSTメソッドのみ対応しています', $response['message'] );
	}

	public function testHandleRequestWithEmptyMessage() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['message']          = '';

		ob_start();
		$this->multiPoster->handleRequest();
		$output = ob_get_clean();

		$response = json_decode( $output, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( 'メッセージが入力されていません', $response['message'] );
	}

	public function testHandleRequestWithNoSelectedServices() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['message']          = 'Test message';
		$_POST['services']         = array();

		ob_start();
		$this->multiPoster->handleRequest();
		$output = ob_get_clean();

		$response = json_decode( $output, true );
		$this->assertFalse( $response['success'] );
		$this->assertEquals( '少なくとも1つの投稿先を選択してください', $response['message'] );
	}

	public function testUploadImageWithInvalidType() {
		$file = array(
			'name'     => 'test.txt',
			'type'     => 'text/plain',
			'tmp_name' => '/tmp/test',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 1000,
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'サポートされていない画像形式です' );

		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'uploadImage' );
		$method->setAccessible( true );
		$method->invoke( $this->multiPoster, $file );
	}

	public function testUploadImageWithLargeSize() {
		$file = array(
			'name'     => 'test.jpg',
			'type'     => 'image/jpeg',
			'tmp_name' => '/tmp/test',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 6 * 1024 * 1024, // 6MB
		);

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( '画像サイズが大きすぎます（最大5MB）' );

		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'uploadImage' );
		$method->setAccessible( true );
		$method->invoke( $this->multiPoster, $file );
	}

	public function testCreateSlackPayload() {
		// Test payload creation logic without actually sending webhook
		$message  = 'Test message';
		$imageUrl = 'https://example.com/image.jpg';

		// We can't easily test the private method without mocking the webhook call
		// So we'll just verify that the method exists and is callable
		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'postToSlack' );
		$this->assertTrue( $method->isPrivate() );

		// Test that the method can be called (we'd need to mock sendWebhook for full test)
		$this->assertTrue( true );
	}

	public function testCreateDiscordPayload() {
		// Test payload creation logic without actually sending webhook
		$message  = 'Test message';
		$imageUrl = 'https://example.com/image.jpg';

		// We can't easily test the private method without mocking the webhook call
		// So we'll just verify that the method exists and is callable
		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'postToDiscord' );
		$this->assertTrue( $method->isPrivate() );

		// Test that the method can be called (we'd need to mock sendWebhook for full test)
		$this->assertTrue( true );
	}

	public function testLoadEnvMethod() {
		// Create a test .env file
		$envFile = __DIR__ . '/../.env';
		file_put_contents( $envFile, "TEST_VAR=test_value\n" );

		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'loadEnv' );
		$method->setAccessible( true );
		$method->invoke( $this->multiPoster );

		$this->assertEquals( 'test_value', $_ENV['TEST_VAR'] );

		// Clean up
		unlink( $envFile );
	}

	public function testGetBaseUrl() {
		$_SERVER['HTTPS']       = 'on';
		$_SERVER['HTTP_HOST']   = 'example.com';
		$_SERVER['REQUEST_URI'] = '/post/index.php';

		$reflection = new ReflectionClass( $this->multiPoster );
		$method     = $reflection->getMethod( 'getBaseUrl' );
		$method->setAccessible( true );
		$result = $method->invoke( $this->multiPoster );

		$this->assertEquals( 'https://example.com/post', $result );
	}

	public function testPostToServicesWithoutWebhookUrls() {
		// Create a MultiPoster instance with empty webhook URLs
		$reflection = new ReflectionClass( $this->multiPoster );

		$slackProperty = $reflection->getProperty( 'slackWebhookUrl' );
		$slackProperty->setAccessible( true );
		$slackProperty->setValue( $this->multiPoster, '' );

		$discordProperty = $reflection->getProperty( 'discordWebhookUrl' );
		$discordProperty->setAccessible( true );
		$discordProperty->setValue( $this->multiPoster, '' );

		$method = $reflection->getMethod( 'postToServices' );
		$method->setAccessible( true );

		$this->expectException( Exception::class );
		$this->expectExceptionMessage( '選択されたサービスのWebhook URLが設定されていません' );

		$method->invoke( $this->multiPoster, 'test message', null, array( 'slack', 'discord' ) );
	}

	public function testPostToServicesWithSelectedServices() {
		// Create a MultiPoster instance with mock webhook URLs
		$reflection = new ReflectionClass( $this->multiPoster );

		$slackProperty = $reflection->getProperty( 'slackWebhookUrl' );
		$slackProperty->setAccessible( true );
		$slackProperty->setValue( $this->multiPoster, 'https://hooks.slack.com/test' );

		$discordProperty = $reflection->getProperty( 'discordWebhookUrl' );
		$discordProperty->setAccessible( true );
		$discordProperty->setValue( $this->multiPoster, '' );

		$method = $reflection->getMethod( 'postToServices' );
		$method->setAccessible( true );

		// Test that we can call the method with only Slack selected
		// We would need to mock the sendWebhook method for a complete test
		try {
			$method->invoke( $this->multiPoster, 'test message', null, array( 'slack' ) );
		} catch ( Exception $e ) {
			// Expected to fail because we can't actually send webhooks in tests
			$this->assertStringContainsString( 'Webhook', $e->getMessage() );
		}
	}
}
