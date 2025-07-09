<?php
// JSONヘッダーの設定（直接実行の場合のみ）
if (basename($_SERVER['PHP_SELF']) === 'post.php') {
	header( 'Content-Type: application/json' );
}

class MultiPoster {
	private $slackWebhookUrl;
	private $discordWebhookUrl;
	private $uploadsDir;
	private $baseUrl;
	private $scheduledPostsFile;

	public function __construct() {
		$this->loadEnv();
		$this->uploadsDir        = __DIR__ . '/uploads/';
		$this->scheduledPostsFile = __DIR__ . '/scheduled_posts.json';
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
		$host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
		$path     = dirname( $_SERVER['REQUEST_URI'] ?? '/post' );
		return $protocol . '://' . $host . $path;
	}

	public function handleRequest() {
		try {
			if ( ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' ) {
				throw new Exception( 'POSTメソッドのみ対応しています' );
			}

			$message = $_POST['message'] ?? '';
			if ( empty( $message ) ) {
				throw new Exception( 'メッセージが入力されていません' );
			}

			$selectedServices = $_POST['services'] ?? array();
			if ( empty( $selectedServices ) ) {
				throw new Exception( '少なくとも1つの投稿先を選択してください' );
			}

			$imageUrl = null;
			if ( isset( $_FILES['image'] ) && $_FILES['image']['error'] === UPLOAD_ERR_OK ) {
				$imageUrl = $this->uploadImage( $_FILES['image'] );
			}

			$postTiming = $_POST['post_timing'] ?? 'immediate';
			$scheduleTime = $_POST['schedule_time'] ?? '';

			if ( $postTiming === 'scheduled' ) {
				if ( empty( $scheduleTime ) ) {
					throw new Exception( '予約投稿の日時を選択してください' );
				}

				$scheduleDateTime = new DateTime( $scheduleTime );
				$now = new DateTime();
				if ( $scheduleDateTime <= $now ) {
					throw new Exception( '予約投稿の日時は現在時刻より未来を選択してください' );
				}

				$this->schedulePost( $message, $imageUrl, $selectedServices, $scheduleDateTime );
				
				echo json_encode(
					array(
						'success' => true,
						'message' => '予約投稿が設定されました（投稿予定: ' . $scheduleDateTime->format('Y-m-d H:i') . '）',
					)
				);
			} else {
				$results = $this->postToServices( $message, $imageUrl, $selectedServices );

				echo json_encode(
					array(
						'success' => true,
						'message' => '投稿が完了しました',
						'results' => $results,
					)
				);
			}

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

	private function postToServices( $message, $imageUrl, $selectedServices = array() ) {
		$results = array();

		// デフォルトですべてのサービスに投稿（下位互換性のため）
		if ( empty( $selectedServices ) ) {
			$selectedServices = array( 'slack', 'discord' );
		}

		if ( in_array( 'slack', $selectedServices ) && ! empty( $this->slackWebhookUrl ) ) {
			$results['slack'] = $this->postToSlack( $message, $imageUrl );
		}

		if ( in_array( 'discord', $selectedServices ) && ! empty( $this->discordWebhookUrl ) ) {
			$results['discord'] = $this->postToDiscord( $message, $imageUrl );
		}

		if ( empty( $results ) ) {
			throw new Exception( '選択されたサービスのWebhook URLが設定されていません' );
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

	private function schedulePost( $message, $imageUrl, $selectedServices, $scheduleDateTime ) {
		$scheduledPosts = $this->loadScheduledPosts();
		
		$postData = array(
			'id' => uniqid(),
			'message' => $message,
			'image_url' => $imageUrl,
			'services' => $selectedServices,
			'schedule_time' => $scheduleDateTime->format('Y-m-d H:i:s'),
			'created_at' => date('Y-m-d H:i:s'),
			'status' => 'pending'
		);

		$scheduledPosts[] = $postData;
		$this->saveScheduledPosts( $scheduledPosts );
	}

	private function loadScheduledPosts() {
		if ( ! file_exists( $this->scheduledPostsFile ) ) {
			return array();
		}

		$content = file_get_contents( $this->scheduledPostsFile );
		if ( $content === false ) {
			return array();
		}

		$posts = json_decode( $content, true );
		return $posts === null ? array() : $posts;
	}

	public function getScheduledPosts() {
		return $this->loadScheduledPosts();
	}

	private function saveScheduledPosts( $posts ) {
		$content = json_encode( $posts, JSON_PRETTY_PRINT );
		if ( file_put_contents( $this->scheduledPostsFile, $content ) === false ) {
			throw new Exception( '予約投稿データの保存に失敗しました' );
		}
	}

	public function processPendingPosts() {
		$scheduledPosts = $this->loadScheduledPosts();
		$now = new DateTime();
		$updatedPosts = array();

		foreach ( $scheduledPosts as $post ) {
			if ( $post['status'] === 'pending' ) {
				$scheduleTime = new DateTime( $post['schedule_time'] );
				
				if ( $scheduleTime <= $now ) {
					try {
						$this->postToServices( $post['message'], $post['image_url'], $post['services'] );
						$post['status'] = 'completed';
						$post['executed_at'] = date('Y-m-d H:i:s');
					} catch ( Exception $e ) {
						$post['status'] = 'failed';
						$post['error'] = $e->getMessage();
						$post['failed_at'] = date('Y-m-d H:i:s');
					}
				}
			}
			$updatedPosts[] = $post;
		}

		$this->saveScheduledPosts( $updatedPosts );
	}
}

// リクエスト処理（直接実行の場合のみ）
if (basename($_SERVER['PHP_SELF']) === 'post.php') {
	$multiPoster = new MultiPoster();
	$multiPoster->handleRequest();
}
