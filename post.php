<?php
header( 'Content-Type: application/json' );

class MultiPoster {
	private $slackWebhookUrl;
	private $discordWebhookUrl;
	private $uploadsDir;
	private $baseUrl;

	public function __construct() {
		$this->loadEnv();
		$this->uploadsDir        = __DIR__ . '/uploads/';
		$this->baseUrl           = $this->getBaseUrl();
		$this->slackWebhookUrl   = $_ENV['SLACK_WEBHOOK_URL'] ?? '';
		$this->discordWebhookUrl = $_ENV['DISCORD_WEBHOOK_URL'] ?? '';
	}

	private function loadEnv() {
		$envFile = __DIR__ . '/.env';
		if ( file_exists( $envFile ) ) {
			$lines = file( $envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
			foreach ( $lines as $line ) {
				if ( strpos( $line, '=' ) !== false && strpos( $line, '#' ) !== 0 ) {
					list($key, $value)    = explode( '=', $line, 2 );
					$_ENV[ trim( $key ) ] = trim( $value );
				}
			}
		}
	}

	private function getBaseUrl() {
		$protocol = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
		$host     = $_SERVER['HTTP_HOST'];
		$path     = dirname( $_SERVER['REQUEST_URI'] );
		return $protocol . '://' . $host . $path;
	}

	public function handleRequest() {
		try {
			if ( $_SERVER['REQUEST_METHOD'] !== 'POST' ) {
				throw new Exception( 'POSTメソッドのみ対応しています' );
			}

			$message = $_POST['message'] ?? '';
			if ( empty( $message ) ) {
				throw new Exception( 'メッセージが入力されていません' );
			}

			$imageUrl = null;
			if ( isset( $_FILES['image'] ) && $_FILES['image']['error'] === UPLOAD_ERR_OK ) {
				$imageUrl = $this->uploadImage( $_FILES['image'] );
			}

			$results = $this->postToServices( $message, $imageUrl );

			echo json_encode(
				array(
					'success' => true,
					'message' => '投稿が完了しました',
					'results' => $results,
				)
			);

		} catch ( Exception $e ) {
			echo json_encode(
				array(
					'success' => false,
					'message' => $e->getMessage(),
				)
			);
		}
	}

	private function uploadImage( $file ) {
		$allowedTypes = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );

		if ( ! in_array( $file['type'], $allowedTypes ) ) {
			throw new Exception( 'サポートされていない画像形式です' );
		}

		$maxSize = 5 * 1024 * 1024; // 5MB
		if ( $file['size'] > $maxSize ) {
			throw new Exception( '画像サイズが大きすぎます（最大5MB）' );
		}

		$extension   = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$filename    = uniqid() . '.' . $extension;
		$destination = $this->uploadsDir . $filename;

		if ( ! is_dir( $this->uploadsDir ) ) {
			mkdir( $this->uploadsDir, 0755, true );
		}

		if ( ! move_uploaded_file( $file['tmp_name'], $destination ) ) {
			throw new Exception( '画像のアップロードに失敗しました' );
		}

		return $this->baseUrl . '/uploads/' . $filename;
	}

	private function postToServices( $message, $imageUrl ) {
		$results = array();

		if ( ! empty( $this->slackWebhookUrl ) ) {
			$results['slack'] = $this->postToSlack( $message, $imageUrl );
		}

		if ( ! empty( $this->discordWebhookUrl ) ) {
			$results['discord'] = $this->postToDiscord( $message, $imageUrl );
		}

		if ( empty( $results ) ) {
			throw new Exception( 'Webhook URLが設定されていません' );
		}

		return $results;
	}

	private function postToSlack( $message, $imageUrl ) {
		$payload = array(
			'text' => $message,
		);

		if ( $imageUrl ) {
			$payload['attachments'] = array(
				array(
					'image_url' => $imageUrl,
					'fallback'  => '画像',
				),
			);
		}

		return $this->sendWebhook( $this->slackWebhookUrl, $payload );
	}

	private function postToDiscord( $message, $imageUrl ) {
		$payload = array(
			'content' => $message,
		);

		if ( $imageUrl ) {
			$payload['embeds'] = array(
				array(
					'image' => array(
						'url' => $imageUrl,
					),
				),
			);
		}

		return $this->sendWebhook( $this->discordWebhookUrl, $payload );
	}

	private function sendWebhook( $url, $payload ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $payload ) );
		curl_setopt(
			$ch,
			CURLOPT_HTTPHEADER,
			array(
				'Content-Type: application/json',
			)
		);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );

		$response = curl_exec( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

		if ( curl_error( $ch ) ) {
			curl_close( $ch );
			throw new Exception( 'Webhook送信エラー: ' . curl_error( $ch ) );
		}

		curl_close( $ch );

		if ( $httpCode >= 200 && $httpCode < 300 ) {
			return array(
				'success'  => true,
				'response' => $response,
			);
		} else {
			throw new Exception( 'Webhook送信失敗: HTTP ' . $httpCode );
		}
	}
}

// リクエスト処理
$multiPoster = new MultiPoster();
$multiPoster->handleRequest();
