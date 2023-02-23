<?php

	krnLoadLib('settings');

	class AmoApi {
		protected static $subdomain = 'romanproudlyru'; // поддомен AmoCRM
		protected static $clientId = 'c9602e66-f38a-4251-9d71-ca29a9e985cb'; // ИД интеграции
		protected static $clientSecret = 'twGeYuKEbFTXofHkoHKW5Tk7N79lugDc6YaboUQjoGlGHCQA8P78YbP0lU8r8GuB'; // Секретный ключ
		protected static $authCode = 'def50200dbd6a031701b882f5f616f23fa98847cfbccd4c75cfdffbc9093abfebe3b8cc39804f6d7ea389c9bf6f313ca3bd2a011bb688e337ccf9bcb67f96bda323ef8e038e9310d18ec5c8c8244f330b58eb05ee715c301b5602307adb719ba4ff37b22eef99358f889dc984fd525635e8caf6ccac9342cee1d06e4db539c4b41f91ec99d53f8114397b1972a51d1c45a44f05bc66291e1a7853854a892e0a229396795aae2d64724009a31fdf5e4e046c807610ab31d74c0c044c4b4246acb0693f35ccce0f43bce7e5d3395bf228aafc7d25f4e0e1e4556c893641c85ffbff8bdd21b701f71bc8b8631dac6df904e283d06af51b5ede724fee0416347ed0ff9cbbe3febeeaf90d8854e109f2475755b6663231c485f9dcb21a17d8f90b2420caa398296f99d4e6cc0ef23478d9ba5de48dd86475f3390fd2893aa251c5bbea3163cff1c44faa69af8f7442c12f90ad66b1b80e791000240e9408d47071f9dff7a2c282ddc6680b8dfde2a78527f74c196610a6a8199c6672cf9a4d2d18b7d1affe615e0c4f7bd8f4d6412f2920e992dcd9096a567e291b22a7d2b29d4ea40ed885197870234afbb19bedc4db2571dc0a69d83c6fbe45aad82b1939cd45d8f3e4d6223e95cc1279c194493cb68019415a1c4744a64905e'; // Код авторизации
		protected static $pipelineId = 0;
		protected static $amoUserId = 0;

		protected static $authLink = '';
		protected static $redirectLink = '';
		protected static $postLink = '';
		protected static $leadsCustomFieldsLink = '';
		protected static $contactsCustomFieldsLink = '';
		protected static $usersLink = '';
		protected static $errors = [
			301 => 'Moved permanently.',
			400 => 'Wrong structure of the array of transmitted data, or invalid identifiers of custom fields.',
			401 => 'Not Authorized. There is no account information on the server. You need to make a request to another server on the transmitted IP.',
			403 => 'The account is blocked, for repeatedly exceeding the number of requests per second.',
			404 => 'Not found.',
			500 => 'Internal server error.',
			502 => 'Bad gateway.',
			503 => 'Service unavailable.'
		];

		protected static $inited = false;
		protected static $tokenData;
		protected static $postData;
		
		public function __construct() {
		}

		protected static function Init() {
			global $Settings;
			global $Config;

			self::$authLink = 'https://' . self::$subdomain . '.amocrm.ru/oauth2/access_token';
			self::$redirectLink = rtrim(stGetSetting('SiteUrl', $Config['Site']['Url']), '/') . '/amo/';
			self::$postLink = 'https://' . self::$subdomain . '.amocrm.ru/api/v4/leads/complex';
			self::$leadsCustomFieldsLink = 'https://' . self::$subdomain . '.amocrm.ru/api/v4/leads/custom_fields';
			self::$contactsCustomFieldsLink = 'https://' . self::$subdomain . '.amocrm.ru/api/v4/contacts/custom_fields';
			self::$usersLink = 'https://' . self::$subdomain . '.amocrm.ru/api/v4/users';

			self::$inited = true;
		}

		protected static function CheckInited() {
			if (!self::$inited) self::Init();
		}
		
		protected static function GetTokenData() {
			$tokenData = stGetSetting('amoToken');
			self::$tokenData = $tokenData ? json_decode($tokenData) : false;
			return self::$tokenData;
		}

		protected static function SetTokenData($tokenData) {
			self::$tokenData = $tokenData;
			stSetSetting('amoToken', json_encode($tokenData));
		}

		protected static function Authorise($callback = false) {
			//self::CheckInited();

			$amoData = [
				'client_id'     => self::$clientId,
			    'client_secret' => self::$clientSecret,
			  	'grant_type'    => 'authorization_code',
			  	'code'          => self::$authCode,
			  	'redirect_uri'  => self::$redirectLink,
			];

			var_dump($amoData);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
			curl_setopt($curl, CURLOPT_URL, self::$authLink);
			curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($amoData));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			$out = curl_exec($curl);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$httpCode = (int) $httpCode;

			var_dump($out);

			if ($httpCode < 200 || $httpCode > 204) die( "Error $httpCode. " . (isset(self::$errors[$httpCode]) ? self::$errors[$httpCode] : 'Undefined error') );

			$response = json_decode($out, true);

			$responseData = [
				'access_token'  => $response['access_token'],
				'refresh_token' => $response['refresh_token'],
				'token_type'    => $response['token_type'],
				'expires_in'    => $response['expires_in'],
				'expires_time'  => $response['expires_in'] + time(),
			];

			self::SetTokenData($responseData);

			if ($callback) self::$callback();
		}

		protected static function Refresh($callback = false) {
			//self::CheckInited();

			$tokenData = self::GetTokenData();

			$amoData = [
			    'client_id'     => self::$clientId,
			    'client_secret' => self::$clientSecret,
			    'grant_type'    => 'refresh_token',
			    'refresh_token' => $tokenData['refresh_token'],
			    'redirect_uri'  => self::$redirectLink,
			];

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-oAuth-client/1.0');
			curl_setopt($curl, CURLOPT_URL, $link);
			curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
			$out = curl_exec($curl);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
			$httpCode = (int) $httpCode;

			if ($httpCode < 200 || $httpCode > 204) die( "Error $httpCode. " . (isset(self::$errors[$httpCode]) ? self::$errors[$httpCode] : 'Undefined error') );

			$response = json_decode($out, true);

			$responseData = [
			 	'access_token'  => $response['access_token'],
				'refresh_token' => $response['refresh_token'],
				'token_type'    => $response['token_type'],
				'expires_in'    => $response['expires_in'],
				'expires_time'  => $response['expires_in'] + time(),
			];

			self::SetTokenData($responseData);

			if ($callback) self::$callback();
		}

		protected static function GetIp() {
			$keys = [
				'HTTP_CLIENT_IP',
			    'HTTP_X_FORWARDED_FOR',
			    'REMOTE_ADDR',
			];
			foreach ($keys as $k) {
				if (!empty($_SERVER[$k])) {
					$ip = trim(end(explode(',', $_SERVER[$k])));
					if (filter_var($ip, FILTER_VALIDATE_IP)) {
						return $ip;
					}
				}
			}
		}

		protected static function ReadyToWork($callback) {
			self::CheckInited();

			self::$tokenData = self::GetTokenData();
			// если нет токен-данных, нужна первичная авторизация
			if (!self::$tokenData) {
				self::Authorise($callback);
				return false;

			// если данные есть, но их срок истек, нужен рефреш токена
			} elseif (self::$tokenData['expires_time'] - 60 < time()) {
				self::Refresh($callback);
				return false;

			} else {
				return true;
			}
		}

		protected static function PreparePostData() {
			$ip = self::GetIp();

			$preparedData = [
				'responsible_user_id' => (int) self::$amoUserId,
				'pipeline_id' => (int) self::$pipelineId,
			];

			if (self::$postData) {
				$preparedData['name'] = self::$postData['name'] ?: self::$postData['phone'] ?: $ip;
				$preparedData['price'] = self::$postData['price'] ?: 0;
				$preparedData['_embedded']['metadata'] = [
					'category' => 'forms',
			        'form_sent_at' => time(),
			        'ip' => $ip,
			        'referer' => $_SERVER['HTTP_REFERER'],
				];
				if (self::$postData['form_id']) $preparedData['_embedded']['metadata']['form_id'] = self::$postData['form_id'];
				if (self::$postData['form_name']) $preparedData['_embedded']['metadata']['form_name'] = self::$postData['form_name'];
				if (self::$postData['page_name']) $preparedData['_embedded']['metadata']['form_page'] = self::$postData['page_name'];
				if (self::$postData['name']) {
					$preparedData['_embedded']['contacts']['first_name'] = self::$postData['name'];
					if (self::$postData['email']) {
						$preparedData['_embedded']['contacts']['custom_fields_values'][] = [
							'field_code' => 'EMAIL',
			                'values' => [
			                    [
			                        'enum_code' => 'WORK',
			                        'value' => self::$postData['email'],
			                    ]
			                ]
						];
					}
					if (self::$postData['phone']) {
						$preparedData['_embedded']['contacts']['custom_fields_values'][] = [
							'field_code' => 'PHONE',
			                'values' => [
			                    [
			                        'enum_code' => 'WORK',
			                        'value' => self::$postData['phone'],
			                    ]
			                ]
						];
					}
				}
				if (self::$postData['utm']) {
					if (self::$postData['utm']['source']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_SOURCE',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['source']
			                    ]
			                ]
						];
					}
					if (self::$postData['utm']['content']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_CONTENT',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['content']
			                    ]
			                ]
						];
					}
					if (self::$postData['utm']['medium']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_MEDIUM',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['medium']
			                    ]
			                ]
						];
					}
					if (self::$postData['utm']['campaign']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_CAMPAIGN',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['campaign']
			                    ]
			                ]
						];
					}
					if (self::$postData['utm']['term']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_TERM',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['term']
			                    ]
			                ]
						];
					}
					if (self::$postData['utm']['referer']) {
						$preparedData['custom_fields_values'][] = [
							'field_code' => 'UTM_REFERRER',
			                'values' => [
			                    [
			                        'value' => self::$postData['utm']['referer']
			                    ]
			                ]
						];
					}
				}
			}

			return $preparedData;
		}

		public static function SendData($data = false) {
			if (self::ReadyToWork('SendData')) {
				if ($data) self::$postData = $data;

				$postData = self::PreparePostData();

				$headers = [
				    'Content-Type: application/json',
				    'Authorization: Bearer ' . self::$tokenData['access_token'],
				];

				$curl = curl_init();
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
				curl_setopt($curl, CURLOPT_URL, self::$postLink);
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postData));
				curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLOPT_COOKIEFILE, 'amo_cookie.txt');
				curl_setopt($curl, CURLOPT_COOKIEJAR, 'amo_cookie.txt');
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
				$out = curl_exec($curl);
				$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				$code = (int) $code;

				if ($httpCode < 200 || $httpCode > 204) die( "Error $httpCode. " . (isset(self::$errors[$httpCode]) ? self::$errors[$httpCode] : 'Undefined error') );

				$response = json_decode($out, true);
				var_dump($response);
			}
		}
		
		public static function PrintInfo() {
			if (self::ReadyToWork('PrintInfo')) {
				$result = '<h1>Техническая информация</h1>';
				$result .= '<a href="' . self::$usersLink . '" target="_blank">Список пользователей</a><br>';
				$result .= '<a href="' . self::$contactsCustomFieldsLink . '" target="_blank">Список полей контакта</a><br>';
				$result .= '<a href="' . self::$leadsCustomFieldsLink . '" target="_blank">Список utm меток</a><br>';
				$result .= '<br><br>';
				$result .= '<a href="https://www.amocrm.ru/developers/content/crm_platform/custom-fields target="_blank">Документация</a>';
			}
		}
	}

?>