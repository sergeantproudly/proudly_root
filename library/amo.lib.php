<?php

	krnLoadLib('settings');

	class AmoApi {
		protected static $subdomain = 'romanproudlyru'; // поддомен AmoCRM
		protected static $clientSecret = 'NAydfNPW1q0Ht76tIFrQ6MPbTnNdOeU5OFuCUbSZncCI4QJjo3qZddW0CrlT5vVN'; // Секретный ключ
		protected static $clientId = 'c9602e66-f38a-4251-9d71-ca29a9e985cb'; // ИД интеграции
		protected static $authCode = 'def5020087204048eaca41891f68e301125c5fa21d7d87fe872a01cdad79dd4d29a2ea41b53b0ed28a0dc0dd7d035d62398c70bb512d2b2a9610f866ce96c7027a006b3e9839f7b0d8235abd8472bc0675e0038e50856c028b4c9871e76c6fb8d790f2bebb828d6cb9de1ff8a7dd5a91cddca9bcfe4db57950dd67ab1dbdab750f990f171b5c0339ea4f8a13bd222ff5a1490add98c11d24281f462e3291c6356046b8080f543e05098bf5c2bd244f27f94182374ad50f39ea4bcc9749b7c7e27987e7daf150535270a844bbaacca076b3760f42e64528356c78041ad94d8f26900844898d4bce269655b617cdb682fe0a46e21ae5e1f5c15796cb71fb47b38fbde67a35829e8e0849b9138da3d9981d9a426d45c18852e0e43e4a21ab74d132f3fcbfe43386249d3f33e985813bb527eae02444d69fde425cfc210aaf6c79e25ab804b787562c9756df0d14878fdd58c28807fcc843215e011213a89aafde4f8b49067c88144279f1ae08b3d8d1b72cd5b4915d059f6c9b1df5e1f66cec0c9d16a56fec1897425b9f17ed7a301d6d7b86d889999368f9ca830a5bb1850afefdec1acba4cdbea7170b7d7923f2a0fb9a9d01f4da599ef48139b4a264b554f2831e8257ee85f0455f08cb784e11dd002b9e8e20ea9ccc605669a0'; // Код авторизации
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

			var_dump('Authorise: ' . $out);

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

			var_dump('Refresh: ' . $out);

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
				];
				if (!empty($_SERVER['HTTP_REFERER'])) $preparedData['_embedded']['metadata']['referer'] = $_SERVER['HTTP_REFERER'];
				if (self::$postData['form_id']) $preparedData['_embedded']['metadata']['form_id'] = self::$postData['form_id'];
				if (self::$postData['form_name']) $preparedData['_embedded']['metadata']['form_name'] = self::$postData['form_name'];
				if (self::$postData['page_name']) $preparedData['_embedded']['metadata']['form_page'] = self::$postData['page_name'];
				if (self::$postData['name']) {
					$contacts['first_name'] = self::$postData['name'];
					if (self::$postData['email']) {
						$contacts['custom_fields_values'][] = [
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
						$contacts['custom_fields_values'][] = [
							'field_code' => 'PHONE',
			                'values' => [
			                    [
			                        'enum_code' => 'WORK',
			                        'value' => self::$postData['phone'],
			                    ]
			                ]
						];
					}
					$preparedData['_embedded']['contacts'][] = $contacts;
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

				var_dump('SendData: ' . $out);

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