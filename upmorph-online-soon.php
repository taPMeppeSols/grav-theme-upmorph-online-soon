<?php
/**
 * @see https://learn.getgrav.org/16/forms/forms/reference-form-actions
 * $this->grav['language']->translate('PLUGIN_FORM.ERROR_VALIDATING_CAPTCHA');
 */
namespace Grav\Theme;

use Grav\Common\{Theme, Grav, Utils};
use Grav\Common\User\User;
use RocketTheme\Toolbox\Event\Event;


class UpmorphOnlineSoon extends Theme {
	const sep = "|#|", json = '.json';
	private static $recipients = [], $routes = [];
	

	##### EVENT functions - start #####

	/**
	 * 
	 */
	public static function getSubscribedEvents(){
		return [
			'onFormProcessed' => ['onFormProcessed', 0]
		];
	}

	public function onFormProcessed(Event $event){
		/** @var Form $form *//*
		$event->stopPropagation();
		$form = $event['form'];
		$action = $event['action'];
		$params = $event['params'];
		*/
	}

	##### EVENT functions - end #####


	##### ADMIN functions - start #####

	/**
	 * @since PM (20.12.2019) this function is used to get the list of all users (email addresses, language) available in the system
	 * @since PM (25.01.2020) - the dependency from the plugin 'admin-addon-user-manager' has become optional
	 * 												- additionally an internal simple buffer is now used to speed up multiple access to the list
	 * @source /user/plugins/admin-addon-user-manager/src/Users/Manager.php :: userNames
	 * @source /user/plugins/admin-addon-user-manager/src/Users/Manager.php :: users
	 */
	static function recipients():array {
		if(!self::$recipients){
			self::$recipients = []; //simple security measure

			if(class_exists("\\AdminAddonUserManager\\Users\\Manager")){
				$users = \AdminAddonUserManager\Users\Manager::$instance->users();
				foreach($users as $user) if($user['email']) self::$recipients[$user['email'] . self::sep . $user['language'] . self::sep . $user['title']] = $user['email'] ." - ". $user['title']; //alternative: $user['fullname']
			}else{
				$files = ($dir = Grav::instance()['locator']->findResource('account://')) ? 
					array_diff(scandir($dir), ['.', '..']) : 
					[]
				;
				foreach ($files as $file) if(Utils::endsWith($file, YAML_EXT)){
					$user = User::load( trim(pathinfo($file, PATHINFO_FILENAME)) );
					//$users[$user->username] = $user;
					if($user['email']) self::$recipients[$user['email'] . self::sep . $user['language'] . self::sep . $user['title']] = $user['email'] ." - ". $user['title']; //alternative: $user['fullname']
				}
			}

			ksort(self::$recipients);
		}
		
		return self::$recipients;
	}

	/**
	 * @since PM (18.12.2019) this function is used to get the list of all current pages in `blueprints.yaml`
	 * @since PM (25.01.2020) - the dependency from the plugin 'admin' has become optional
	 * 												- additionally an internal simple buffer is now used to speed up multiple access to the list
	 * @source https://github.com/getgrav/grav-plugin-sitemap/blob/develop/sitemap.php :: onPagesInitialized
	 * @source /user/plugins/admin/classes/admin.php :: siteLanguages
	 * @return array
	 */
	static function pages():array { 
		if(!self::$routes){
			$grav = Grav::instance();
			$pages = $grav['pages'];
			$routes = array_unique($pages->routes());
			ksort($routes);
			
			$config = $grav['config'];
			$languages = (array) $config->get('system.languages.supported', []); //the supported languages
				//$languages = array_keys(\Grav\Plugin\Admin\Admin::siteLanguages()); //the supported languages
			$hideHomeRoute = !$config->get('system.home.hide_in_urls', false); //the oppsite is on purpose

			self::$routes = []; //the enriched list of the pages
			foreach($routes as $route => $path){
				$page = $pages->get($path); //the current page
				$langs = $page->translatedLanguages(); //the languages in which the current page is available

				//$this->hide_home_route
				//$this->home_route
				self::$routes[$page->home() && $hideHomeRoute ? $route[0] : $route] = $page->title() ." (". implode(", ", array_keys($langs) ?: $languages) .")";
			}
			//array_keys(self::$routes);
		}

		return self::$routes;
	}

	##### ADMIN functions - end #####


	##### REGISTRATION functions - start #####

	static function str2Ar(string $data):array { return json_decode($data, true) ?: []; }

	/**
	 * this function is used to read stored arrays from a given file
	 * @param string $name the filename without the .json format
	 * @return array
	 */
	static function read(string $name):array {
		return self::str2Ar(@file_get_contents(UPMORPH_DIR . DIRECTORY_SEPARATOR . $name . self::json));
	}

	static function readTexts():array {
		return array_merge_recursive(
			json_decode(@file_get_contents(__DIR__ . DIRECTORY_SEPARATOR .'languages'. self::json), true), //standard
			self::read('languages') //custom
		);
	}

	/**
	 * this function is used to store arrays in a given file
	 * @param string $name the filename without the .json format
	 * @param array $data
	 * @return int|FALSE
	 */
	static function write(string $name, array $data)/*:int|FALSE*/{
		return file_put_contents(
			UPMORPH_DIR . DIRECTORY_SEPARATOR . $name . self::json, 
			preg_replace("/ {4}/", "\t", json_encode($data, JSON_PRETTY_PRINT)), 
			LOCK_EX
		);
	}

	##### REGISTRATION functions - end #####
}


/**
 * PM (18.12.2019) registration
 * the sign up process is done according to the GDPR directive
 */
//echo '<pre>'.print_r($_SERVER, true);die();
if( isset($_REQUEST[$key = 'upmorph_auth_code']) ){
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	
	/**
	 * @since PM (22.12.2019)
	 * @see https://www.php.net/manual/en/function.php-uname.php
	 * #available 
	 * - `GRAV_TESTING` @source /system/defines.php
	 * - `DATA_DIR` @source /system/defines.php but @deprecated
	 */
	define(
		"UPMORPH_DEV_ENV", 
		preg_match( "/^192\.168\.\\d+\.\\d+$/", gethostbyname($host = gethostname()) ) && $host == "tapmeppe" &&
			php_uname('s') == "Windows NT" && php_uname('n') == "TAPMEPPE" && php_uname('m') == "AMD64"
	);
	define("UPMORPH_DIR", dirname(__DIR__, 2) . DIRECTORY_SEPARATOR .'data'. DIRECTORY_SEPARATOR . basename(__DIR__)); // the directory in which the system specific data will by stored
	if(!is_dir(UPMORPH_DIR)) mkdir(UPMORPH_DIR, 0775, true); //create the directory if not existant

	$PAGE = ($_SERVER["HTTPS"] == "on" ? "https://" : "http://") . $_SERVER["SERVER_NAME"] . explode('?', $_SERVER["REQUEST_URI"])[0];
	$AUTH_CODE = preg_replace(
		$regex = "|\\W+|",
		'',
		base64_encode(preg_replace(
			$regex,
			'@',
			base64_encode(preg_replace(
				$regex, 
				'#', 
				$PAGE
			))
		))
	);
	$REGEX_EMAIL = "/^[\\w.%+-]+@[\\w.-]+\.[A-Za-z]{2,}$/"; //email regex
	$NOW = date("Y-m-d H:i:s");
	$LANG = 'en'; //default language
	$SUCCESS = 'success';
	$ERROR = 'error';
	$ERRORS = function(string $errors) use($ERROR){ 
		if(UPMORPH_DEV_ENV) file_put_contents(
			UPMORPH_DIR . DIRECTORY_SEPARATOR . $ERROR ."s.log", 
			$errors, 
			FILE_APPEND | LOCK_EX
		);
	};
	$EMAILS = 'emails';
	$ALIASES = 'aliases';

	//...
	if($_POST[$key] == $AUTH_CODE){ //first step on the site
		
		$response = $ERROR;
		$error = "";
		if( $_POST['name'] === "" && preg_match($REGEX_EMAIL, $email = $_POST['email']) ){ //name is the honeypot
			$emails = UpmorphOnlineSoon::read($EMAILS);
			if($emails[$email]) $response = 'exists';
			else{
				$aliases = UpmorphOnlineSoon::read($ALIASES);
				do $alias = uniqid($NOW ."_", true); while($aliases[$alias]); //making sure the alias is really unique

				$aliases[$alias] = base64_encode(implode( //request date & email address
					UpmorphOnlineSoon::sep, 
					[
						$NOW,
						$email,
						$_POST['qwertzuiop'], //email address, username & language used by the admin
						$title = $_POST['title'],
					]
				));
				if(UpmorphOnlineSoon::write($ALIASES, $aliases)){ //TRUE if the data were successfully stored
					$url = "$PAGE?$key=". urlencode($AUTH_CODE) ."&data=". urlencode($alias);
					if(UPMORPH_DEV_ENV) file_put_contents(UPMORPH_DIR . DIRECTORY_SEPARATOR .'urls.log.md', "[$alias]($url)<br>\n", FILE_APPEND | LOCK_EX);
					
					if($texts = UpmorphOnlineSoon::readTexts()){
						$text = $texts[$_POST["lang"] ?: $LANG] ?: $texts[$LANG]; //`$LANG` security measure
						try{
							$sent = mail( //send the confirmation mail to the visitor
								$email,
								"$title: ". $text['subject_visitor'],
								str_replace(
									['_URL_PAGE_', '_URL_CONFIRMATION_', '_TITLE_'], 
									[$PAGE, $url, $title], 
									implode("<br>", $text['message_visitor'])
								),
								[
									'MIME-Version' => '1.0', 
									'Content-type' => 'text/html; charset=UTF-8', 
									'From' => "info@". $_SERVER["SERVER_NAME"],
									'X-Mailer' => 'PHP/'. phpversion()
								]
							);
							if($sent) $response = $SUCCESS;
							else $error = "mail-not-sent";
						}catch(\Exception $e){ 
							$error = "mail-exception [exception:". str_replace("\n", "-", $e->getMessage()) ."]"; 
							if(UPMORPH_DEV_ENV) $response = $SUCCESS;
						}
					}else $error = "texts-missing";
				}else $error = "alias-storage-failed";
			}
		}else $error = "email-match-failed [email:$email]";

		$ERRORS("POST $NOW $email $error\n");
		die($response);

	}elseif(urldecode($_GET[$key]) == $AUTH_CODE){ //opt-in step from the confirmation email
		$redirect = function($target) use($PAGE){ //this function is used to redirect the page
			header("Location: $PAGE?registration=$target");
			exit;
		};

		if($alias = urldecode( trim($_GET['data']) )){
			$aliases = UpmorphOnlineSoon::read($ALIASES);
			if($data = $aliases[$alias]){
				list($date, $email, $qwertzuiop, $title) = explode(UpmorphOnlineSoon::sep, base64_decode($data));
				
				$d = "\\d{2}";
				if(preg_match("@$d$d-$d-$d $d:$d:$d@", $date) && preg_match($REGEX_EMAIL, $email)){
					
					$emails = UpmorphOnlineSoon::read($EMAILS);
					if($emails[$email]){
						unset($aliases[$alias]); //remove the alias
						UpmorphOnlineSoon::write($ALIASES, $aliases);

						$redirect('exists'); //the address exists already
					}else{ //add the address to list
						
						$emails[$email] = ['request' => $date, 'opt_in' => $NOW]; //new entry
						if(UpmorphOnlineSoon::write($EMAILS, $emails)){ //TRUE if the new email was successfully stored
							if( ($texts = UpmorphOnlineSoon::readTexts()) && ($recipients = UpmorphOnlineSoon::str2Ar( base64_decode(base64_decode($qwertzuiop)) )) ){ //try to notify & forget
								$slug = basename(__DIR__);
								$errors = "";

								foreach($recipients as $recipient){
									list($admin, $lang, $username) = explode(UpmorphOnlineSoon::sep, $recipient); //email address, username & language used by the admin
									if(preg_match($REGEX_EMAIL, $admin)){ //TRUE if the recipient email is valid
										$text = $texts[$lang ?: $LANG] ?: $texts[$LANG]; //`$LANG` security measure
										try{
											$sent = mail( //notify the system user about the new email address
												$admin,
												"$title: $slug: ". $text['subject_admin'],
												str_replace(
													['__USERNAME__', '__EMAIL_ADDRESS__', '__THEME__'], 
													[$username, $email, "$title: $slug"], 
													implode("<br>", $text['message_admin'])
												),
												[
													'MIME-Version' => '1.0', 
													'Content-type' => 'text/html; charset=UTF-8', 
													'From' => "info@". $_SERVER["SERVER_NAME"],
													'X-Mailer' => 'PHP/'. phpversion()
												]
											);
											if(!$sent) $errors .= "mail-not-sent [admin:$admin]\n";
										}catch(\Exception $e){
											$errors .= "GET $NOW mail-exception [admin:$admin], [exception:". str_replace("\n", "-", $e->getMessage()) ."]\n"; 
										}
									}
								}

								if($errors) $ERRORS($errors);
							}

							unset($aliases[$alias]); //remove the alias
							UpmorphOnlineSoon::write($ALIASES, $aliases);
							$redirect($SUCCESS);
						}else $error = "email-storage-failed [email:$email]";
					} //endelse
				}else $error = "date-invalid [date:$date], [email:$email]";
			}else $error = "alias-invalid [alias:$alias]";
		}else $error = "alias-empty";
		
		$ERRORS("GET $NOW $error\n");
		$redirect($ERROR);
	}
}