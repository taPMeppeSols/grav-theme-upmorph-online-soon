<?php
	/**
	 * @see https://learn.getgrav.org/16/forms/forms/reference-form-actions
	 * $this->grav['language']->translate('PLUGIN_FORM.ERROR_VALIDATING_CAPTCHA');
	 * 
	 * @example 
	 * - http://upmorph.grav.meppe/en
	 * - http://upmorph.grav.meppe/admin
	 */
	namespace Grav\Theme;

	use Grav\Common\{Theme, Grav, Utils};
	use Grav\Common\User\User;
	use RocketTheme\Toolbox\Event\Event;
	use Grav\Common\Processors\Events\RequestHandlerEvent;


	class UpmorphOnlineSoon extends Theme {
		const 
			sep = "|#|", json = '.json', log = '.log', lang = 'en', //the default language
			regexEmail = "/^[\\w.%+-]+@[\\w.-]+\.[A-Za-z]{2,}$/", //email regex
			admin = 'admin', success = 'success', error = 'error', email = 'email', alias = 'alias',
			errors = self::error .'s', emails = self::email .'s', aliases = self::alias .'es'
		;
		private static $dev, $locator, $thm, $theme, $dir, $slug, $auth, $recipients = [], $routes = [];
		private $url, $now, $lang, $title;
		

		##### construct - start #####
		/**
		 * `$this->config` is set in system\src\Grav\Common\Plugin.php
		 */
		##### construct - end #####


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
				$hideHomeRoute = !$config->get('system.home.hide_in_urls', false); //the opposite is on purpose

				self::$routes = []; //the enriched list of the pages
				foreach($routes as $route => $path){
					$page = $pages->get($path); //the current page
					if($page->visible()){
						$langs = $page->translatedLanguages(); //the languages in which the current page is available

						//$this->hide_home_route
						//$this->home_route
						//self::$routes[$page->home() && $hideHomeRoute ? $route[0] : $route] = $page->menu() ." (". implode(", ", array_keys($langs) ?: $languages) .")";
						self::$routes[$page->home() && $hideHomeRoute ? $route[0] : $route] = $page->title() ." (". implode(", ", array_keys($langs) ?: $languages) .")";
					}
				}
				//array_keys(self::$routes);
			}

			return self::$routes;
		}

		##### ADMIN functions - end #####


		##### EVENT functions - start #####

		/**
		 * @see https://learn.getgrav.org/16/plugins/grav-lifecycle
		 * 
		 * @since PM (22.12.2019) dev mode
		 * @see https://www.php.net/manual/en/function.php-uname.php
		 * 
		 * #available 
		 * - `GRAV_TESTING` @source /system/defines.php
		 * - `DATA_DIR` @source /system/defines.php but @deprecated
		 */
		public static function getSubscribedEvents(){
			self::$dev = 
				preg_match( "/^192\.168\.\\d+\.\\d+$/", gethostbyname($temp = gethostname()) ) && $temp == "tapmeppe" &&
				php_uname('s') == "Windows NT" && php_uname('n') == "TAPMEPPE" && php_uname('m') == "AMD64"
			;
			self::$locator = Grav::instance()['locator'];

			//self::$theme = basename(__DIR__); //the theme name
			//self::$dir = self::path(dirname(__DIR__, 2), 'data', self::$theme); //the directory in which the system specific data will by stored
			self::$theme = basename(self::$thm = self::$locator->findResource('theme://')); //the theme name
			self::$dir = self::$locator->findResource('user-data://'. self::$theme);
			if(!is_dir(self::$dir)) mkdir(self::$dir, 0775, true); //create the directory if not existant

			self::$slug = explode('-', self::$theme)[0];
			self::$auth = self::$slug .'_auth_code';

			return [
				//'onFormProcessed' => ['onFormProcessed', 0],
				'onRequestHandlerInit' => ['onRequestHandlerInit', 0],
				'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
			];
		}

		/**
		 * @version 2020.7 @since PM (07.02.2020) standalone
		 */
		static function path(...$path):string { return implode(DS, $path); }
		//static function path(...$path):string { return implode(DIRECTORY_SEPARATOR, $path); }


		public function onFormProcessed(Event $event){
			/** @var Form $form *//*
			$event->stopPropagation();
			$form = $event['form'];
			$action = $event['action'];
			$params = $event['params'];
			*/
		}

		/**
		 * this function is used to handle all the registration actions;
		 * it is invoked indirectly by the GRAV system has a handler of the GRAV native 'onRequestHandlerInit' event;
		 * by the time this event handler is invoked all resources necessary to handle the registration actions, are available
		 * #die()
		 * #redirect()
		 * @version 2020.8 @since PM (08.02.2020)
		 * @source system\src\Grav\Common\Processors\RequestProcessor.php
		 * @param \Grav\Common\Processors\Events\RequestHandlerEvent $event the object representing the event
		 */
		function onRequestHandlerInit(RequestHandlerEvent $event){
			/**
			 * PM (18.12.2019) registration block
			 * - the sign up process is done according to the GDPR directive
			 */
			//echo '<pre>'.print_r($_SERVER, true);die();
			if( isset($_REQUEST[self::$auth]) ){
				error_reporting(E_ERROR | E_WARNING | E_PARSE);
				
				/**
			 	 * alternative used by GRAV
			 	 * @see URI: https://learn.getgrav.org/16/api#class-gravcommonuri
				 * @example $uri = $this->grav['uri'];
				 * 
				 * I decide to use a custom to be in control with the result
				 */
				$this->url = ($_SERVER["HTTPS"] == "on" ? "https://" : "http://") . $_SERVER["SERVER_NAME"] . explode('?', $_SERVER["REQUEST_URI"])[0];
				$this->now = date("Y-m-d H:i:s");
				$this->lang = $this->grav['language']->getActive() ?: $this->config->get('site.default_lang', self::lang);
				$this->title = $this->config->get('site.title', self::$theme);

				if( $_POST[self::$auth] == ($code = $this->authCode($this->url)) ) $this->register($code);
				elseif(( $auth = $this->clean($_GET[self::$auth]) ) == $code) $this->optIn(); //opt-in step from the confirmation email
				elseif( $this->isEmail($auth) && ($_GET['delete'] || $_GET['deletion'] || $_GET['remove'] || $_GET['removal']) ) $this->delete($auth); //delete option

				$event->stopPropagation(); //this line will actually never be reached; it was set just to remember its existence for future projects
			}else return $event;
		}

		/**
		 * this function is used to add additional values to the TWIG engine
		 * @version 2020.8 @since PM (08.02.2020)
		 * @see https://learn.getgrav.org/16/plugins/event-hooks#ontwigsitevariables
		 */
		function onTwigSiteVariables(Event $event){
			//$page = $event && isset($event['page']) ? $event['page'] : $this->grav['page'];
			$values = [
				'dev' => self::$dev,
				'slug' => self::$slug,
				'auth' => self::$auth,
			];

			$twig = $this->grav['twig'];
			foreach($values as $key => $value) $twig->twig_vars[self::$slug ."_$key"] = $value;
		}


		/**
		 * this function is used to start the registration process of the email adresses entered in the registration form
		 * #die()
		 * @version 2020.7 @since PM (06-07.02.2020) standalone
		 * @version 2020.8 @since PM (08.02.2020) object oriented
		 */
		private function register(string $code){
			$response = self::error;
			$error = "";

			if( $_POST['name'] === "" && $this->isEmail($email = $_POST[self::email]) ){ //name is the honeypot
				$emails = $this->read(self::emails);
				if($emails[$email]) $response = 'exists';
				else{
					$aliases = $this->read(self::aliases);
					do $alias = uniqid($this->now ."_", true); while($aliases[$alias]); //making sure the alias is really unique

					$aliases[$alias] = base64_encode(implode(self::sep, [$this->now, $email]) ); //request date & email address
					if($this->write(self::aliases, $aliases)){ //TRUE if the data were successfully stored
						$url = "$this->url?". self::$auth ."=". urlencode($code) ."&data=". urlencode($alias);
						if(self::$dev) file_put_contents(self::path(self::$dir, 'urls.log.md'), "[$alias]($url)<br>\n", FILE_APPEND | LOCK_EX);

						if($texts = $this->readTexts()){
							list($sent, $error) = $this->notify( //send the confirmation mail to the visitor
								$email,
								'visitor', 
								$texts[$this->lang] ?: $texts[self::lang], //`self::lang` security measure
								['__URL_PAGE__', '__URL_CONFIRMATION__', '__TITLE__'], 
								[$this->url, $url, $this->title], 
							);
							if($sent) $response = self::success;

						}else $error = "texts-missing";
					}else $error = "alias-storage-failed";
				}
			}else $error = "email-match-failed [email:$email]";

			if($error) $this->errors("REGISTER", "$email $error");
			die($response);
		}

		/**
		 * this function is used to opt-in once the visitor will have confirmed the registration request by clicking on the link sent to him
		 * #redirect()
		 * @version 2020.7 @since PM (06-07.02.2020) standalone
		 * @version 2020.8 @since PM (08.02.2020) object oriented
		 */
		private function optIn(){
			$error = "";

			if( $alias = $this->clean($_GET['data']) ){
				$aliases = $this->read(self::aliases);
				$emails = $this->read(self::emails);

				//check the availability of the alias
				$found;
				if($data = $aliases[$alias]) $found = true;
				else{
					foreach($emails as $email) if($email[self::alias] == $alias){ //TRUE if the alias has already been used in the past
						$this->redirect('exists'); //the address exists already
						break; //this line will never be reached, but aw well :-)
					}
					$found = false; //the line will only be reached if the alias has never been used before
				}
				//DO NOT MERGE
				if($found){ //TRUE if the alias has been found in the list of aliases
					list($date, $email) = explode(self::sep, base64_decode($data));
					
					$d = "\\d{2}";
					if(preg_match("@$d$d-$d-$d $d:$d:$d@", $date) && $this->isEmail($email)){
						//DO NOT MERGE
						if($emails[$email]){
							unset($aliases[$alias]); //remove the alias
							$this->write(self::aliases, $aliases);
	
							$this->redirect('exists'); //the address exists already
						}else{ //add the address to list
							$emails[$email] = ['request' => $date, 'opt_in' => $this->now, self::alias => $alias]; //new entry
							if($this->write(self::emails, $emails)){ //TRUE if the new email was successfully stored
								$this->notifyAdmins('registration', $email, "OPT-IN");
	
								unset($aliases[$alias]); //remove the alias
								$this->write(self::aliases, $aliases);
								$this->redirect(self::success);
							}else $error = "email-storage-failed [email:$email]";
						} //endelse
					}else $error = "data-invalid [date:$date], [email:$email]";
				}else $error = "alias-invalid [alias:$alias]";
			}else $error = "alias-empty";
			
			$this->errors("OPT-IN", $error);
			$this->redirect(self::error);
		}

		/**
		 * this function is used to delete existing email address
		 * #redirect()
		 * @version 2020.8 @since PM (08.02.2020)
		 * @example http://upmorph.grav.meppe/en?upmorph_auth_code=a@a.com&delete=1
		 * @param string $email the email address to delete
		 */
		private function delete(string $email){
			$flag = 'deletion';
			$emails = $this->read(self::emails); //load the list of emails
			if($emails[$email]){ //TRUE if email has been found
				unset($emails[$email]); //remove the email address from the list
				//$recipients = $this->config->get('theme.registration_recipients', []);

				$prefix = "DELETE";
				if($this->write(self::emails, $emails)){ //update & close the list
					$this->notifyAdmins($flag, $email, $prefix);
					$this->redirect("$flag-". self::success);
				}else{
					$this->errors($prefix, "email-storage-failed [email:$email]");

					$this->notifyAdmins("${flag}_". self::error, $email, $prefix);
					$this->redirect("$flag-". self::error);
				}
			}else $this->redirect("$flag-not-found");
		}

		/**
		 * this function is used during the development to debug the registration process by logging errors
		 * @version 2020.9 @since 09.02.2020 $prefix has been added
		 * @param string $prefix s.e.
		 * @param string $error the error message to log
		 */
		private function errors(string $prefix, string $error){
			if(self::$dev) file_put_contents(
				self::path(self::$dir, self::errors .".log"), 
				"$this->now $prefix\n". trim($error) ."\n\n", 
				FILE_APPEND | LOCK_EX
			);
		}

		/**
		 * this function is used to generate the authentication code
		 * @param string $url the current page URL
		 * @return string the authentication code
		 */
		private function authCode(string $url):string {
			return preg_replace(
				$regex = "|\\W+|",
				'',
				base64_encode(preg_replace(
					$regex,
					'@',
					base64_encode(preg_replace(
						$regex, 
						'#', 
						$url
					))
				))
			);
		}

		/**
		 * this function is used to send a mail notification
		 * @version 2020.7 @since PM (08.02.2020) standalone
		 * @param string $email the email address
		 * @param string $mode admin_[registration|deletion|deletion_error]|visitor
		 * @param array $text the text to send
		 * @param array $phs the placeholders
		 * @param array $reps the replacers
		 * @return {TRUE, ''} if everything went as expected, otherwise {FALSE, '...'}
		 */
		private function notify(string $email, string $mode, array $text, array $phs, array $reps):array {
			try{
				$sent = mail( //notify the system user about the new email address
					$email,
					"$this->title: ". $text["subject_$mode"],
					str_replace(
						array_merge($phs, ['__TITLE__']), 
						array_merge($reps, [$this->title]), 
						implode("<br>", $text["message_$mode"]) 
					),
					[
						'MIME-Version' => '1.0', 
						'Content-type' => 'text/html; charset=UTF-8', 
						'From' => "info@". $_SERVER["SERVER_NAME"],
						'X-Mailer' => 'PHP/'. phpversion()
					]
				);
				return [
					$sent, 
					$sent ? '' : "mail-not-sent [$mode:$email]"
				];
			}catch(\Exception $e){
				return [
					self::$dev,
					"mail-exception [$mode:$email], [exception:". str_replace("\n", "-", $e->getMessage()) ."]"
				];
			}
		}

		/**
		 * this function is used to notify all the admins
		 * @version 2020.7 @since PM (08.02.2020) standalone
		 * @version 2020.9 @since 09.02.2020 $prefix was added
		 * @param string $mode registration|deletion|deletion_error
		 * @param string $email the visitor email address
		 * @param string $prefix the error prefix
		 */
		private function notifyAdmins(string $mode, string $email, string $prefix){
			if( ( $recipients = $this->config->get('theme.registration_recipients', []) ) && ($texts = $this->readTexts()) ){ //try to notify & forget
				$error = '';
				
				foreach($recipients as $recipient){
					list($admin, $lang, $username) = explode(self::sep, $recipient['recipient']); //email address, username & language used by the admin
					if($this->isEmail($admin)){ //TRUE if the recipient email is valid
						list(, $er) = $this->notify( //notify the system admin about the new email address
							$admin,
							self::admin ."_$mode", 
							$texts[$lang ?: $this->lang] ?: $texts[self::lang], //`self::lang` security measure
							['__USERNAME__', '__EMAIL_ADDRESS__'], 
							[$username, $email]
						);
						if($er) $error .= "$er\n";
					}
				}

				if($error) $this->errors($prefix, $error);
			}
		}

		/**
		 * this function is used to redirect the client to a different page
		 * #exit
		 * @param string $target the registration
		 */
		private function redirect(string $target){
			header("Location: $this->url?registration=$target");
			exit;
		}

		/**
		 * this function is used to check the validity of the given email address
		 * @version 2020.7 @since PM (07.02.2020) 
		 * @param string $email s.e.
		 * @return TRUE if the value entered is that of an actual email address otherwise FALSE
		 */
		private function isEmail(string $email):bool { return preg_match(self::regexEmail, $email); }

		private function str2Ar(string $data):array { return json_decode($data, true) ?: []; }

		/**
		 * this function is used to read stored arrays from a given file
		 * @param string $name the filename without the .json format
		 * @return array
		 */
		private function read(string $name):array {
			return $this->str2Ar( @file_get_contents(self::path(self::$dir, $name . self::json)) );
		}

		private function readTexts():array {
			return array_merge_recursive(
				$this->str2Ar( @file_get_contents(self::path(self::$thm, 'languages', self::admin . self::json)) ), //standard
				$this->read('languages') //custom
			);
		}

		/**
		 * this function is used to store arrays in a given file
		 * @param string $name the filename without the .json format
		 * @param array $data
		 * @return int|FALSE
		 */
		private function write(string $name, array $data)/*:int|FALSE*/{
			return file_put_contents(
				self::path(self::$dir, $name . self::json), 
				preg_replace("/ {4}/", "\t", json_encode($data, JSON_PRETTY_PRINT)), 
				LOCK_EX
			);
		}

		/**
		 * @version 2020.7 @since PM (07.02.2020) standalone
		 */
		private function clean(string $data):string { return urldecode(trim($data)); }

		##### EVENT functions - end #####

	}
