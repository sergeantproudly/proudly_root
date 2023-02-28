<?php

	krnLoadLib('settings');

	class AmoApi {
		const LOGLEVELMIN = 0;
		const LOGLEVELMAX = 3;

		protected static $subdomain = 'romanproudlyru'; // поддомен AmoCRM
		protected static $clientSecret = 'z8n1ByYJ9ShS3d7NRpMOUzQZELzxEAEzaXYaGhyJyRvHPM5FV8p49POhRc20c3Is'; // Секретный ключ
		protected static $clientId = 'c9602e66-f38a-4251-9d71-ca29a9e985cb'; // ИД интеграции
		protected static $authCode = 'def50200cbaad842166c935b469d12d622369971a381134da4a38b09315b1ec3c93d9ed4772fd4be6fd211a5bce9c7d248f5482591e199b3599d870753b04cc52d9efd221dfb61897a6544dc6100db0450e6a49309df44390319c398d7a4235ef974256ea3aa757516e5294e976022fe992608a39ad9702e3db4013b7aa3f7e38b868b1c4362ab2a3ebf3229b9d4ce6bf3c7fcf282fddcb35b94466923486711e4a0a6c8eebb235b6eb5142c9e3b6758700e749a747929f46b9c8c2e3fbf8a6b86d1c16cebe7f9aed3105c0cd3e98c4d120a488f198d5a4504524984e2f6998a4b86c8f8162e48c46f4415400947a19029760685f4ae572b5652f983b7d4fd186b6d91ffbf62d03dd1383abdd17a7eab342bcd64fea96da2eab8b1d8cf81e541d2de6aa931653c47aadc1face7bd1bff95912dc36551761ed9870706a22c06f199f9a022e46870c11b98911a9f62643d413e83ae7f41d54ce0f87c74d208969b98f6b1c64948d368b3a4ca5ec9dc9ac71118bcea6ba32d723aee8379f6cfeb2096e7b340ce1ed43e4ccbfef9d7204cb902a2ac0a9f036b24aa28b8b134e667615620fdf969e5e9a78ae68f8725478afc09cba8a608f958931f76c63183a2499f209d032d0c3202f1fe909ce54f9e71fb144d9fe8eb271224850d'; // Код авторизации
		protected static $pipelineId = 6439062;
		protected static $amoUserId = 9230734;
		protected static $statusId = 55025766;
		protected static $messageCustomFieldId = 1018487;

		protected static $debug = true;
		protected static $logFile = './amo.log';
		protected static $logLevel = self::LOGLEVELMIN;

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
		protected static $attemptsCount = 0;
		protected static $attemptsLimit = 1;
		
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

		public static function SetLogLevel($level) {
			self::$logLevel = $level;
		}

		protected static function CheckLogLevel($level) {
			return (self::$logLevel >= (int) $level);
		}

		protected static function LogToDebugFile($str) {
			return file_put_contents(self::$logFile, '[' . date('d.m.Y H:i:s') . '] ' . $str . PHP_EOL, FILE_APPEND) ? true : false;
		}

		protected static function DebugDump($varname, $var, $level) {
			if (self::CheckLogLevel($level)) {
				if (!self::$logFile) {
					echo $varname . ($var !== false ? ': ' : '<br>');
					if ($var !== false) {
						//var_dump($var);
						echo nl2br(str_replace(' ', '&nbsp;', var_export($var, true)));
						echo '<br>';
					}
					return true;

				} else {
					self::LogToDebugFile($varname . ($var !== false ? ': ' : ''));
					if ($var !== false) self::LogToDebugFile(var_export($var, true));
				}
			}
		}

		protected static function DebugHttpCode($code) {
			if ($code < 200 || $code > 204) return self::DebugDump('Error ' . (int) $code, (isset(self::$errors[$httpCode]) ? self::$errors[$httpCode] : 'Undefined error'), self::LOGLEVELMIN);
			else return self::DebugDump('Http status', $code, self::LOGLEVELMIN);
		}
		
		protected static function GetTokenData() {
			$tokenData = stGetSetting('amoToken');
			self::$tokenData = $tokenData ? json_decode($tokenData, true) : false;
			return self::$tokenData;
		}

		protected static function SetTokenData($tokenData) {
			self::$tokenData = $tokenData;
			stSetSetting('amoToken', json_encode($tokenData), true, 'Техническое - Хэш AmoCrm');
		}

		protected static function Authorise($callback = false) {
			//self::CheckInited();
			if (self::$debug) self::DebugDump('Authorisation going', false, self::LOGLEVELMAX);

			$amoData = [
				'client_id'     => self::$clientId,
			    'client_secret' => self::$clientSecret,
			  	'grant_type'    => 'authorization_code',
			  	'code'          => self::$authCode,
			  	'redirect_uri'  => self::$redirectLink,
			];

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

			if (self::$debug) self::DebugHttpCode($httpCode);
			if (self::$debug) self::DebugDump('Authorise', $out, self::LOGLEVELMIN);

			$response = json_decode($out, true);

			$responseData = [
				'access_token'  => $response['access_token'],
				'refresh_token' => $response['refresh_token'],
				'token_type'    => $response['token_type'],
				'expires_in'    => $response['expires_in'],
				'expires_ts'  	=> $response['expires_in'] + time(),
				'expires_time'  => date('d.m.Y H:i', $response['expires_in'] + time()),
			];

			self::SetTokenData($responseData);

			if ($callback) self::$callback();
		}

		protected static function Refresh($callback = false) {
			//self::CheckInited();
			if (self::$debug) self::DebugDump('Refresh going', false, self::LOGLEVELMAX);

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

			if (self::$debug) self::DebugHttpCode($httpCode);
			if (self::$debug) self::DebugDump('Refresh', $out, self::LOGLEVELMIN);

			$response = json_decode($out, true);

			$responseData = [
			 	'access_token'  => $response['access_token'],
				'refresh_token' => $response['refresh_token'],
				'token_type'    => $response['token_type'],
				'expires_in'    => $response['expires_in'],
				'expires_ts'  	=> $response['expires_in'] + time(),
				'expires_time'  => date('d.m.Y H:i', $response['expires_in'] + time()),
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

			if (self::$debug) {
				self::DebugDump('ReadyToWork', false, self::LOGLEVELMAX);
				self::DebugDump('Is tokenData exists', self::$tokenData, self::LOGLEVELMAX);
				if (self::$tokenData) self::DebugDump('Is token expired', self::$tokenData['expires_ts'] - 60 < time(), self::LOGLEVELMAX);
			}

			// если нет токен-данных, нужна первичная авторизация
			if (!self::$tokenData) {
				self::$attemptsCount++;
				if (self::$attemptsCount > self::$attemptsLimit) {
					self::DebugDump('Authorisation attempts count exceeded', self::$attemptsLimit, self::LOGLEVELMIN);
					return false;
				}
				self::Authorise($callback);
				return false;

			// если данные есть, но их срок истек, нужен рефреш токена
			} elseif (self::$tokenData['expires_ts'] - 60 < time()) {
				self::$attemptsCount++;
				if (self::$attemptsCount > self::$attemptsLimit) {
					self::DebugDump('Refresh attempts count exceeded', self::$attemptsLimit, self::LOGLEVELMIN);
					return false;
				}
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
				'created_by' => (int) self::$amoUserId,
				'pipeline_id' => (int) self::$pipelineId,
			];
			if ((int) self::$statusId) $preparedData['status_id'] = (int) self::$statusId;

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
					if (self::$postData['text']) {
						$contacts['custom_fields_values'][] = [
							'field_id' => self::$messageCustomFieldId,
							'value' => self::$postData['text'],
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
			if ($data) self::$postData = $data;

			if (self::ReadyToWork('SendData')) {
				if (self::$debug) self::DebugDump('SendData going', false, self::LOGLEVELMAX);

				$postData = self::PreparePostData();

				$headers = [
				    'Content-Type: application/json',
				    'Authorization: Bearer ' . self::$tokenData['access_token'],
				];

				if (self::$debug) self::DebugDump('SendData post', $postData, self::LOGLEVELMIN);

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
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				curl_close($curl);
				$httpCode = (int) $httpCode;

				if (self::$debug) self::DebugHttpCode($httpCode);
				if (self::$debug) self::DebugDump('SendData out', $out, self::LOGLEVELMIN);

				$response = json_decode($out, true);
			}
		}
		
		public static function PrintInfo() {
			if (self::ReadyToWork('PrintInfo')) {
				$result = '<h1>Техническая информация</h1>';
				$result .= '<a href="' . self::$usersLink . '" target="_blank">Список пользователей</a><br>';
				$result .= '<a href="' . self::$contactsCustomFieldsLink . '" target="_blank">Список полей контакта</a><br>';
				$result .= '<a href="' . self::$leadsCustomFieldsLink . '" target="_blank">Список utm меток</a><br>';
				$result .= '<br><br>';
				$result .= '<a href="https://www.amocrm.ru/developers/content/crm_platform/custom-fields" target="_blank">Документация</a>';

				echo $result;
			}
		}
	}

?>