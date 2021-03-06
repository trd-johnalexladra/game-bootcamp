<?php

class Controller_Students_Auth extends Controller {

	public function action_oauth($provider = null)
	{
		// bail out if we don't have an OAuth provider to call
		if ($provider === null)
		{
			Log::error(__('login-no-provider-specified'));
			\Response::redirect_back();
		}

		// load Opauth, it will load the provider strategy and redirect to the provider
		\Auth_Opauth::forge();
	}

	public function action_logout()
	{
		// remove the remember-me cookie, we logged-out on purpose
		\Auth::dont_remember_me();

		// logout
		\Auth::logout();

		// and go back to where you came from (or the application
		// homepage if no previous page can be determined)
		\Response::redirect_back();
	}

	public function action_callback()
	{

		// Opauth can throw all kinds of nasty bits, so be prepared
		try
		{
			// get the Opauth object
			$opauth = \Auth_Opauth::forge(false);

			// and process the callback
			$status = $opauth->login_or_register();

			// fetch the provider name from the opauth response so we can display a message
			$provider = $opauth->get('auth.provider', '?');

			// deal with the result of the callback process
			switch ($status)
			{
				// a local user was logged-in, the provider has been linked to this user
				case 'linked':
					// inform the user the link was succesfully made
					// and set the redirect url for this status
					$url = '/students';
					break;

				// the provider was known and linked, the linked account as logged-in
				case 'logged_in':
					// inform the user the login using the provider was succesful
					// and set the redirect url for this status

					$url = '/students';

					break;

				// we don't know this provider login, ask the user to create a local account first
				case 'register':
					// inform the user the login using the provider was succesful, but we need a local account to continue
					// and set the redirect url for this status
					$user_hash = \Session::get('auth-strategy.user', array());

					$name = $user_hash['name'];
					$email =time().sha1($name).'@game-bootcamp.com';
					$password = sha1("aaaa2ht".time());

					$id = Auth::create_user($email, $password, $email, $group = 1);
					Auth::force_login($id);
					$this->link_provider($id);

					$url = '/students/auth/oauth/'.strtolower($provider);
					//$url = '/students';
					break;

				// we didn't know this provider login, but enough info was returned to auto-register the user
				case 'registered':
					// inform the user the login using the provider was succesful, and we created a local account
					// and set the redirect url for this status
					$url = '/students';
					break;

				default:
					throw new \FuelException('Auth_Opauth::login_or_register() has come up with a result that we dont know how to handle.');
			}

			// redirect to the url set
			Response::redirect($url);
		}

			// deal with Opauth exceptions
		catch (\OpauthException $e)
		{
			Log::error($e->getMessage());
			\Response::redirect_back();
		}

			// catch a user cancelling the authentication attempt (some providers allow that)
		catch (\OpauthCancelException $e)
		{
			// you should probably do something a bit more clean here...
			exit('It looks like you canceled your authorisation.'.\Html::anchor('users/oath/'.$provider, 'Click here').' to try again.');
		}

	}

	public function action_register()
	{

		// create the registration fieldset
		$form = \Fieldset::forge('registerform');

		// add a csrf token to prevent CSRF attacks
		$form->form()->add_csrf();

		// and populate the form with the model properties
		$form->add_model('Model\\Auth_User');

		// add the fullname field, it's a profile property, not a user property
		$form->add_after('fullname', __('login.form.fullname'), array(), array(), 'username')->add_rule('required');

		// add a password confirmation field
		$form->add_after('confirm', __('login.form.confirm'), array('type' => 'password'), array(), 'password')->add_rule('required');

		// make sure the password is required
		$form->field('password')->add_rule('required');

		// and new users are not allowed to select the group they're in (duh!)
		$form->disable('group_id');

		// since it's not on the form, make sure validation doesn't trip on its absence
		$form->field('group_id')->delete_rule('required')->delete_rule('is_numeric');

		// fetch the oauth provider from the session (if present)
		$provider = \Session::get('auth-strategy.authentication.provider', false);

		// if we have provider information, create the login fieldset too
		if ($provider)
		{
			// disable the username, it was passed to us by the Oauth strategy
			$form->field('username')->set_attribute('readonly', true);

			// create an additional login form so we can link providers to existing accounts
			$login = \Fieldset::forge('loginform');
			$login->form()->add_csrf();
			$login->add_model('Model\\Auth_User');

			// we only need username and password
			$login->disable('group_id')->disable('email');

			// since they're not on the form, make sure validation doesn't trip on their absence
			$login->field('group_id')->delete_rule('required')->delete_rule('is_numeric');
			$login->field('email')->delete_rule('required')->delete_rule('valid_email');
		}

		// was the registration form posted?
		if (\Input::method() == 'POST')
		{
			// was the login form posted?
			if ($provider and \Input::post('login'))
			{
				// check the credentials.
				if (\Auth::instance()->login(\Input::param('username'), \Input::param('password')))
				{
					// get the current logged-in user's id
					list(, $userid) = \Auth::instance()->get_user_id();

					// so we can link it to the provider manually
					$this->link_provider($userid);

					// logged in, go back where we came from,
					// or the the user dashboard if we don't know
					\Response::redirect_back('dashboard');
				}
				else
				{
					// login failed, show an error message
					Log::error(__('login.failure'));
				}
			}

			// was the registration form posted?
			elseif (\Input::post('register'))
			{
				// validate the input
				$form->validation()->run();

				// if validated, create the user
				if ( ! $form->validation()->error())
				{
					try
					{
						// call Auth to create this user
						$created = \Auth::create_user(
							$form->validated('username'),
							$form->validated('password'),
							$form->validated('email'),
							\Config::get('application.user.default_group', 1),
							array(
								'fullname' => $form->validated('fullname'),
							)
						);

						// if a user was created succesfully
						if ($created)
						{
							// inform the user

							// link new user
							$this->link_provider($created);

							// and go back to the previous page, or show the
							// application dashboard if we don't have any
							\Response::redirect_back('/');
						}
						else
						{
							// oops, creating a new user failed?
							Log::error(__('login.account-creation-failed'));
						}
					}

						// catch exceptions from the create_user() call
					catch (\SimpleUserUpdateException $e)
					{
						// duplicate email address
						if ($e->getCode() == 2)
						{
							Log::error(__('login.email-already-exists'));
						}

						// duplicate username
						elseif ($e->getCode() == 3)
						{
							Log::error(__('login.username-already-exists'));
						}

						// this can't happen, but you'll never know...
						else
						{
							Log::error($e->getMessage());
						}
					}
				}
			}

			// validation failed, repopulate the form from the posted data
			$form->repopulate();
		}
		else
		{
			// get the auth-strategy data from the session (created by the callback)
			$user_hash = \Session::get('auth-strategy.user', array());

			// populate the registration form with the data from the provider callback
			$form->populate(array(
				'username' => \Arr::get($user_hash, 'nickname'),
				'fullname' => \Arr::get($user_hash, 'name'),
				'email' => \Arr::get($user_hash, 'email'),
			));
		}
		$form->add('register', '', array('type'=>'hidden', 'value' => '1'));
		$form->add('submit', '', array('type'=>'submit', 'value' => 'submit'));

		// pass the fieldset to the form, and display the new user registration view
		return \View::forge('login/registration')->set('form', $form->build(), false)->set('login', isset($login) ? $login : null, false);
	}

	protected function link_provider($userid)
	{
		// do we have an auth strategy to match?
		if ($authentication = \Session::get('auth-strategy.authentication', array()))
		{
			// don't forget to pass false, we need an object instance, not a strategy call
			$opauth = \Auth_Opauth::forge(false);

			// call Opauth to link the provider login with the local user
			$insert_id = $opauth->link_provider(array(
				'parent_id' => $userid,
				'provider' => $authentication['provider'],
				'uid' => $authentication['uid'],
				'access_token' => $authentication['access_token'],
				'secret' => $authentication['secret'],
				'refresh_token' => $authentication['refresh_token'],
				'expires' => $authentication['expires'],
				'created_at' => time(),
			));
		}
	}

}
