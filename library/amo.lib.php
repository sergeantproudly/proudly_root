<?php

	krnLoadLib('settings');

	class AmoApi {
		protected static $subdomain = 'romanproudlyru'; // поддомен AmoCRM
		protected static $clientSecret = 'twGeYuKEbFTXofHkoHKW5Tk7N79lugDc6YaboUQjoGlGHCQA8P78YbP0lU8r8GuB'; // Секретный ключ
		protected static $clientId = 'c9602e66-f38a-4251-9d71-ca29a9e985cb'; // ИД интеграции
		protected static $authCode = 'def50200dc34f3e7c85d299ddadd69d57c559ed75f7595f1bd5f0101f002ceb4d3093f8237eee75be0ca31877cb495f123b974ab62cc3efd5b34f12fbbf35a2482903b3e00f59dd4761be81e2c2b103ef151f5f6ea9b0a67841f823ee95594fb7190444a67a3b7602f43dd726658a88b2213168d1eec2d21a9540e83317c3c5ed9507032966baee654207d044013486eb40dde417ca89ded57579f5c7431be7a4f232644e56dccea842f8bf11284631935d7b9ad179e6d09144c1262eadbd286e28117a5ae2781c4e2b7a0b342e710eb8a1329f6723d21164549ca8278cda80b473535b983f756c8aa5c6ed2920d99c8c945f9bb597d34d276532447b917bc132b0b32f31e7b464da40f1259deef5d566a3e12c923551a8ccdc9292998b6e16624f58bce4fc4c3ebea03ccb5b269db90242c561b4882ce4a207d6c11c19aefbe655579021eaabab929b0ae50703d9d4d370b3c3c3fba62eb45f9790a9a5cfdbcfd473d2b5d79971f448790a3c4f08c1d1906513e8d1f73f53b50e898a2a04916d993acd047691062c55261786a473f3d58e0f44c2e3934acf1fafa153bdd2ee3c542161fa48b667cf7b2651ebcf4c52ceedc5af5e4422888e18fc5ae2c71a2f3ad60ee173c0841e1cb71dfe955a46d64843892b87b1c71b331f4'; // Код авторизации
		protected static $pipelineId = 6439062;
		protected static $amoUserId = 9230734;

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
			self::$tokenData = $tokenData ? json_decode($tokenData, true) : false;
			return self::$tokenData;
		}

		protected static function SetTokenData($tokenData) {
			self::$tokenData = $tokenData;
			stSetSetting('amoToken', json_encode($tokenData), true);
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

			//var_dump($amoData);

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

			//var_dump($out);

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

			return array($preparedData);
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

				echo $result;
			}
		}
	}

?>