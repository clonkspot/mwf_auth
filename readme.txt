DESCRIPTION
===========
The MwfAuth API offers authentication against a MwForum user database. It offers two features:
1. Authentication
2. Getting user info and groups

Nothing more. So, this API only replaces the password check. The application using this API must still keep track of its users and their permissions separately and should be able to create a new local account for an existing user from the forum on login automatically.

Finer-grained permissions (like being a moderator in the league) should be controlled by checking the user's membership of certain groups (e.g. 'League Moderators') so that this can easily be controlled through the forum.
Informations like e.g. email address and or forum group membership (= user's permissions) should be updated on each successful login.

DEPLOYMENT
==========
1. Fill in the details for access to your MwForum database into mwf_auth_config.php
2. Upload. On OpenClonk.org it is in a directory outside of htdocs so that it is not accessible via http.

EXAMPLE
=======

	include($_SERVER['DOCUMENT_ROOT'].'../auth/mwf_auth.php');

	try
	{
	  // authenticate
	  $user = new MwfUser($username);
	  if($user->authenticate($password))
		echo 'Authenticated as ' . $username;
	  else
		die 'Wrong username or password';
	  
	  // login success: update information
	  $user_email = $user->get_info()['email'];
	  $my_own_user_object->update_email($user_email);
	  /// etc... and other info

	  // update permissions
	  $is_league_mod = in_array('League Moderators',$user->get_groups());
	  $my_own_user_object->update_mod($is_league_mod);
	}
	catch(Exception $e)
	{
	  die 'Database access error: ' . $e->getMessage();
	}

METHOD REFERENCE
===========

MwfUser::MwfUser($name)
  Initializes the MwfUser object by querying the data from the MwForum. Throws an exception if it can't access the DB.

MwfUser::authenticate($password)
  Checks if the password is correct for the user. Returns false if the password is wrong or the user does not exist.

MwfUser::exists()
  Returns false if the user does not exist.

MwfUser::get_info()
  Return information about the user in an associative array or NULL if the user does not exist. It does not contain all the fields from the MwForum DB. Fields:

  General
  -------
  'id'               id in the forum. Useful for linking to the user info page in the forum
  'userName'
  'admin'            whether the user is an admin (in the forum), true or false
  
  Contact fields
  --------------
  'email'
  'instantMessenger'
  
  Language and Timezone
  ---------------------
  'timezone'         +1, -2 or something
  'language'         'English', 'German', etc.
  
  Profile fields
  --------------
  'realName'
  'title'            Title in the forum
  'homepage'
  'occupation'
  'hobbies'
  'location'
  'avatar'           Filename of avatar (only, not including the path to it)
  'signature'
  'birthyear'
  'birthday'
  
  Extra fields
  ------------
  'extra1'
  'extra2'
  'extra3'

MwfUser::get_groups()
  Return all the groups as strings the user is member of or NULL if the user does not exist. The array is empty for users that are in no groups. Throws an exception if it can't access the DB.