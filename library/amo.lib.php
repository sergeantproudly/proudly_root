<?php

	krnLoadLib('settings');

	class AmoApi {
		protected static $subdomain = 'romanproudlyru'; // поддомен AmoCRM
		protected static $clientSecret = 'twGeYuKEbFTXofHkoHKW5Tk7N79lugDc6YaboUQjoGlGHCQA8P78YbP0lU8r8GuB'; // Секретный ключ
		protected static $clientId = 'c9602e66-f38a-4251-9d71-ca29a9e985cb'; // ИД интеграции
		protected static $authCode = 'def502005bd39e13a9883321c7586974a554b24fdc7054c9f2d336bd719d4acda95080a0de62e49401f247d7fc6b3a5b024a3d28e65df92dd39c03818537c81b99c160f5d5d838d5688af867f8358be07d888e00da01433d9894c3444d35a78b40513e125220f5fb09aacc1be482c52decf0bb3638291ff702080b6722d9a8653dc47e728727ed2fd486bad0f19ecea20d937ec87f220158ff458d4e3bc7569e16f6c945c499a9ecd4508c2fb5f58d54b0d1d8ef5c983a66c8fd66992fcf66ef2fdf1607ff4b690f8bc0ff66ebd43d548a486b864f0608f4301d57a2406df40efd623db82d50f8a169f30011d8d25ece93053bcc7150d83592fb61e3953d30a46eacd08291cdb08c246811e545ed6c32661cfa789ff005fe6ff738a229fcfa826142d1881c2273747d533286da49265e14ca1e7b6bbf994a83eb3e195eddf3bf2bfe68a01d8aee636e2214cf3f212de69895043e53f38bca6999b56cd69cddd34128fe8ac72de89c433423374a21e58648f9e6a9c0f85ec7b424badc1083266981eec7288aaa04d26230ceefca5fd7545584e3aa63984518bb26aad78d5b06adf0b0301ceb970d59665104cafe955da6c19c7577167ced229b83dc9ba70f226f633fa265607332bc70db2dcb3087d5bba0f819a621a8b60cc645'; // Код авторизации
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
			stSetSetting('asdasd', '!', true);
			die();

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