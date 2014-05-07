<?php

require_once 'mwf_auth_config.php';

// debug
/*
if(isset($_GET['name']))
{
	try
	{
		$user = new MwfUser($_GET['name']);

		if($user->exists())
			echo $_GET['name']." exists<br/>";
		else 
			echo $_GET['name']." does not exist<br/>";
		
		if(isset($_GET['password']))
		{
		
			echo "AUTHENTIFICATION: ";

			if($user->authenticate($_GET['password']))
				echo "OK<br/>";
			else
				echo "failed!<br/>";
		}
		if(isset($_GET['info']))
		{
			echo "<br/><br/>INFO & GROUPS:<br/>";
			print_r($user->get_info());
			echo "<br/><br/>";
			print_r($user->get_groups());
		}
	}
	catch(Exception $e)
	{
		echo "ERROR:" . $e->getMessage() . "<br/>";
	}
}
*/
class MwfUser
{
	private $mysqli = NULL, $user_row = NULL, $user_name = NULL;

	public function __construct($name)
	{
	
		if(!isset($name) || !$name)
		{
			return;
		}
	
		$mysqli = new mysqli(MwfAuthConfig::$SERVER, MwfAuthConfig::$USER, MwfAuthConfig::$PASSWORD, MwfAuthConfig::$DB);
		if ($mysqli->connect_errno)
		{
			throw new Exception("Failed to connect to database: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
		}
		
		$mysqli->set_charset('utf8');
		$name = $mysqli->real_escape_string($name);
		
		$result = $mysqli->query("SELECT * FROM ".MwfAuthConfig::$TABLE_PREFIX."users WHERE userName = '$name'");
		if (!$result)
		{
			throw new Exception("Failed to query user $name: (". $mysqli->connect_errno . ") " . $mysqli->connect_error);
		}
		
		$this->user_row = $result->fetch_assoc();
		$this->user_name = $name;
		$this->mysqli = $mysqli;
	}

	public function __destruct()
	{
		if($this->mysqli)
		{
			$this->mysqli->close();
		}
	}
	
	/** Hash the password the same way mwForum does it */
	private static function hash_password($password, $salt)
	{
		$data = $password . $salt;
		$rounds = 100000;

		// FIXME: need that in PHP?
		// utf8::encode($data) if utf8::is_utf8($data);

		for($i = 0; $i < 100000; ++$i)
		{
			$data = md5($data,true);
		}

		$data = base64_encode($data);
		$data = strtr($data, array('+'=>'-','/'=>'_'));
		
		// remove the trailing == (if any)
		if(substr( $data, -2) == '==')
		{
			$data = substr($data,0,-2);
		}
		
		return $data;
	}
	
	/** Whether the user exists at all */
	public function exists()
	{
		$exists = !!$this->user_row;
		return $exists;
	}
	
	/** Authenticate the user against the mwforum database. Returns false if user does not exist or password is wrong */
    public function authenticate($password)
	{
		// user does not exist:
		if(!$this->exists())
		{
			return false;
		}
	
		if(!isset($password) || !$password)
		{
			return false;
		}
		
		$password_hash = self::hash_password($password, $this->user_row['salt']);
		if ($password_hash != $this->user_row['password'])
		{
			return false;
		}
		
		return true;
	}

	public function get_user_name()
	{
		return $this->user_name;
	}
	
	/** Get info from a user. Returns NULL if the user does not exist, otherwise an associative array */
	function get_info()
	{
		if(!$this->exists())
		{
			return NULL;
		}
	
		$result = array(
			'id'               => $this->user_row['id'],
			'userName'         => $this->user_row['userName'],
			'admin'            => ($this->user_row['admin'] == 1),
			
			// language
			'language'         => $this->user_row['language'],
			'timezone'         => $this->user_row['timezone'],
			
			// contact
			'email'            => $this->user_row['email'],
			'instantMessenger' => $this->user_row['icq'],
			
			// profile
			'realName'         => $this->user_row['realName'],
			'title'            => $this->user_row['title'],
			'homepage'         => $this->user_row['homepage'],

			'occupation'       => $this->user_row['occupation'],
			'hobbies'          => $this->user_row['hobbies'],
			'location'         => $this->user_row['location'],

			'avatar'           => $this->user_row['avatar'],
			'signature'        => $this->user_row['signature'],
			'birthyear'        => $this->user_row['birthyear'],
			'birthday'         => $this->user_row['birthday'],
			
			// mwforum extra data
			'extra1'           => $this->user_row['extra1'],
			'extra2'           => $this->user_row['extra2'],
			'extra3'           => $this->user_row['extra3']
			);
			
		return $result;
	}
	
	/** Get groups of a user. Returns NULL if the user does not exist, otherwise an array of groups */
	function get_groups()
	{
		if(!$this->exists())
		{
			return NULL;
		}
		
		$user_id = $this->user_row['id'];
		
		$result = $this->mysqli->query(
		"SELECT groups.title AS title
		FROM ".MwfAuthConfig::$TABLE_PREFIX."groups AS groups
			INNER JOIN ".MwfAuthConfig::$TABLE_PREFIX."groupMembers AS groupMembers
				ON groupMembers.userId = $user_id
				AND groupMembers.groupId = groups.id
			LEFT JOIN ".MwfAuthConfig::$TABLE_PREFIX."groupAdmins AS groupAdmins
				ON groupAdmins.userId = $user_id
				AND groupAdmins.groupId = groups.id
		ORDER BY groups.title"
		);
		
		if (!$result)
		{
			throw new Exception("Failed to query groups for user $this->user_name: (". $this->mysqli->connect_errno . ") " . $this->mysqli->connect_error);
		}
		
		$groups = array();
		while ($row = $result->fetch_assoc()) {
			$groups[] = $row['title'];
		}
		
		return $groups;
	}
}
?>
