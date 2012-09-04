<?php

class Tickspot {
	
	const BASE_URL = 'https://tickspot.com/';
	const USER_AGENT = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_3; en-us) AppleWebKit/533.16 (KHTML, like Gecko) Version/5.0 Safari/533.16';
	
	// config data
	protected $_company;
	protected $_email;
	protected $_password;
	
	// last request respones data
	protected $_response;
	protected $_info;
	
	/**
	 * The base url to the company site.
	 * 
	 * @var string
	 */
	protected $_baseUrl;
	
	/**
	 * The login url for performing screen scraping when necessary.
	 * 
	 * @var	string
	 */
	protected $_loginUrl;
	
	/**
	 * The API base url for performing screen scraping when necessary.
	 * 
	 * @var	string
	 */
	protected $_apiUrl;
	
	/**
	 * Default constructor.
	 */
	public function __construct($config = array())
	{
		if (empty($config) || !is_array($config)) {
			throw new Exception('You must provide a TickSpot config array.');
		}
		
		// store parameters
		if (!isset($config['company']) || !isset($config['email']) || !isset($config['password'])) {
			throw new Exception('You must specify a company, email address, and password.');
		}
		
		// set some vars
		$this->_company = $config['company'];
		$this->_email = $config['email'];
		$this->_password = $config['password'];

		// generate the base url
		$this->_baseUrl = 'https://' . $this->_company . '.tickspot.com/';

		// generate the api url
		$this->_apiUrl = $this->_baseUrl . 'api';
		
		// generate the login url
		$this->_loginUrl = $this->_baseUrl . 'login';
	}
	
	/**
	 * Will return a list of all clients and can only be accessed by admins on
	 * the subscription.
	 *
	 * @access	public
	 * @param	bool	$open	Whether the clients are open or closed [NULL = both]
	 * @return	array
	 * @return	mixed
	 */
	public function getClients($open = NULL)
	{
		$params = array('open' => $open);
		$params += $this->_getAuthParams();
		
		// fire off a post request
		return $this->postRequest('clients', $params, null);
	}
	
	/**
	 * The projects method will return projects filtered by the parameters
	 * provided. Admin can see all projects on the subscription, while
	 * non-admins can only access the projects they are assigned.
	 *
	 * @access	public
	 * @param	mixed	$project_id
	 * @param	mixed	$open
	 * @param	mixed	$project_billable
	 * @return	mixed
	 */
	public function getProjects($project_id = NULL, $open = NULL, $project_billable = NULL)
	{
		$params = array(
			'project_id' => $project_id,
			'open' => $open,
			'project_billable' => $project_billable
		);
		$params += $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('projects', $params, null);
	}
	
	/**
	 * The projects method will return projects filtered by the parameters
	 * provided. Admin can see all projects on the subscription, while
	 * non-admins can only access the projects they are assigned.
	 *
	 * @access	public
	 * @param	int		$project_id
	 * @param	mixed	$task_id
	 * @param	mixed	$open
	 * @param	mixed	$task_billable
	 * @return	mixed
	 */
	public function getTasks($project_id, $task_id = NULL, $open = NULL, $task_billable = NULL)
	{
		$params = array(
			'project_id' => $project_id,
			'task_id' => $task_id,
			'open' => $open,
			'task_billable' => $task_billable
		);
		$params += $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('tasks', $params, null);
	}
	
	/**
	 * Will return a list of all clients, projects, and tasks that are assigned
	 * to the user.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function getUserDetails()
	{
		// only needs auth params
		$params = $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('clients_projects_tasks', $params, null);
	}
	
	/**
	 * Will return a list of all entries that meet the provided criteria. Either
	 * a start and end date have to be provided or an updated_at time. The
	 * entries will be in the start and end date range or they will be after
	 * the updated_at time depending on what criteria is provided. Each of the
	 * optional parameters will further filter the response.
	 *
	 * @access	public
	 * @param	string	$updated_at
	 * @param	string	$start_date
	 * @param	string	$end_date
	 * @param	int		$project_id
	 * @param	int		$task_id
	 * @param	int		$user_id
	 * @param	string	$user_email
	 * @param	int		$client_id
	 * @param	bool	$entry_billable
	 * @param	bool	$billed
	 */
	public function getEntries(
		$updated_at = NULL,
		$start_date = NULL,
		$end_date = NULL,
		$project_id = NULL,
		$task_id = NULL,
		$user_id = NULL,
		$user_email = NULL,
		$client_id = NULL,
		$entry_billable = NULL,
		$billed = NULL)
	{
		// minimal requirements
		if ($updated_at === NULL && ($start_date === NULL || $end_date === NULL)) {
			throw new Exception('You must provide either updated_at or a combination of start_date and end_date.');
		}
		
		$params = array(
			'project_id' => $project_id,
			'task_id' => $task_id,
			'user_id' => $user_id,
			'user_email' => $user_email,
			'client_id' => $client_id,
			'entry_billable' => $entry_billable,
			'billed' => $billed
		);
		$params += $this->_getAuthParams();
		
		// determine which required params to add
		if ($updated_at !== NULL) {
			$params['updated_at'] = $updated_at;
		} else {
			$params['start_date'] = $start_date;
			$params['end_date'] = $end_date;
		}

		// fire off a post request
		return $this->postRequest('entries', $params, null);
	}
	
	/**
	 * Will return a list of the most recently used tasks. This is useful for
	 * generating quick links for a user to select a task they have been using
	 * recently.
	 *
	 * @access	public
	 * @return	mixed
	 */
	public function getRecentTasks()
	{
		// only needs auth params
		$params = $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('recent_tasks', $params, null);
	}
	
	/**
	 * The users method will return a list of users.
	 *
	 * @access	public
	 * @param	mixed	$project_id
	 * @return	mixed
	 */
	public function getUsers($project_id = NULL)
	{
		$params = array('project_id' => $project_id);
		$params = $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('users', $params, null);
	}
	
	/**
	 * The create_entry method will accept a time entry for a specified task_id
	 * and return the created entry along with the task and project stats.
	 *
	 * @access	public
	 * @param	int		$task_id
	 * @param	float	$hours
	 * @param	string	$date
	 * @param	string	$notes
	 * @return	mixed
	 */
	public function createEntry($task_id, $hours, $date, $notes = NULL)
	{
		$params = array(
			'task_id' => $task_id,
			'hours' => $hours,
			'date' => $date,
			'notes' => $notes
		);
		$params += $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('create_entry', $params, null);
	}
	
	/**
	 * The update_entry method will allow you to modify attributes of an existing
	 * entry. The only required parameter is the id of the entry. Additional
	 * parameters must be provided for any attribute that you wish to update.
	 * For example, if you are only changing the billed attribute, your post
	 * should only include the required parameters and the billed parameter.
	 *
	 * @access	public
	 * @param	int		$id
	 * @param	float	$hours
	 * @param	string	$date
	 * @param	bool	$billed
	 * @param	int		$task_id
	 * @param	int		$user_id
	 * @param	string	$notes
	 */
	public function updateEntry($id, $hours = NULL, $date = NULL, $billed = NULL, $task_id = NULL, $user_id = NULL, $notes = NULL)
	{
		$params = array(
			'id' => $task_id,
			'hours' => $hours,
			'date' => $date,
			'billed' => $billed,
			'task_id' => $task_id,
			'user_id' => $user_id,
			'notes' => $notes
		);
		$params += $this->_getAuthParams();

		// fire off a post request
		return $this->postRequest('update_entry', $params, null);
	}
	
	/**
	 * Only to be used when screen scraping is required.
	 *
	 * This is used to set a cookie for the robot to use in the CookieJar to
	 * authentic with the TickSpot service. The cookie will be stored in the
	 * functions directory as 'cookie.txt'. This function has to be called before
	 * contacting the TickSpot service to ensure that the cookie is valid to use
	 * so that the robot can make any modifications to the account that are
	 * requested. This should not be called multiple times during a single
	 * function as it is not required and only needs to authentic once with the
	 * service.
	 *
	 * @access	public
	 * @return	void
	 */
	public function authenticate()
	{
		// generate request parameters
		$data = 'user_login=' . $this->_email . '&user_password=' . $this->_password . '&remember%5Bpassword%5D=1&remember%5Bpassword%5D=&login.x=74&login.y=25';

		try {
			
			// we need to store cookies
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->_loginUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt'); 
			curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
			
			// avoid looking suspicious
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
			
			// retrieve the response
			$this->_response = curl_exec($ch);
			$this->_info = curl_getinfo($ch);
			
			// close cURL
			curl_close($ch);
			
		} catch (Exception $e) {
			error_log($e->getMessage());
			return false;
		}

	}

	/**
	 * Fire off a POST request.
	 *
	 * @access	public
	 * @param	string	$url
	 * @param	mixed	$data
	 * @param	string	$referrer
	 * @return	mixed
	 */
	public function postRequest($method, $data, $referrer)
	{
		try {
			
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->_apiUrl . '/' . $method);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt'); 
			curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
			
			// avoid looking suspicious
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch, CURLOPT_REFERER, $referrer);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
			
			// retrieve the response
			$this->_response = curl_exec($ch);
			$this->_info = curl_getinfo($ch);
			
			// close cURL
			curl_close($ch);
			
			return $this->_response;
		
		} catch (Exception $e) {
			error_log($e->getMessage());
			return false;
		}
	}

	
	/**
	 * This will return the methods passed into the function that do not require
	 * further parameters than email and password.
	 * 
	 * It is a clear and easy way to return results from the api before passing
	 * through any results.
	 *
	 * @access	public
	 * @param	string	$method
	 * @link	http://tickspot.com/api/
	 */
	public function getRequest($method)
	{
		
		try {
		
			$data = $this->_getAuthParams();
			$data = http_build_query($data);
		
			// generate the GET url
			$url = $this->_apiUrl . '/' . $method . '?' . $data;
		
			// initialize cURL
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt'); 
			curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
			
			// avoid looking suspicious
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		
			// retrieve the response
			$this->_response = curl_exec($ch);
			$this->_info = curl_getinfo($ch);
		
			// close cURL
			curl_close($ch);
			
			return $this->_response;
		
		} catch (Exception $e) {
			return false;
		}
	}

	/**
	 * Return necessary parameters for authentication.
	 *
	 * @access	private
	 * @return	array
	 */
	private function _getAuthParams()
	{
		return array(
			'email' => $this->_email,
			'password' => $this->_password
		);
	}

	/**
	 * Screen scraper handling to delete all projects.
	 * Uses an undocumented API endpoint.
	 *
	 * @access	public
	 * @return	void
	 */
	public function deleteAllProjects()
	{
		// retrieve all current projects
		$xml = $this->getProjects();
		
		// convert returned XML
		$projects = new SimpleXMLElement($xml);
		
		// perform an actual HTTP login
		$this->authenticate();
		
		// iterate over projects and delete
		foreach ($projects->project as $project) {
			$deleteUrl = $this->_baseUrl . 'projects/delete_project/' . $project->id;
			$response = $this->postRequest($deleteUrl);
			echo 'Deleted project: ' . $project->name . "<br />\n";
		}
		
		echo 'Project deletion completed.';
	}

	/**
	 * Screen scraper handling to close all projects.
	 * Uses an undocumented API endpoint.
	 *
	 * @access	public
	 * @return	void
	 */
	public function closeAllProjects()
	{
		// retrieve all current projects
		$xml = $this->getProjects();
		
		// convert returned XML
		$projects = new SimpleXMLElement($xml);
		
		// perform an actual HTTP login
		$this->authenticate();
		
		// iterate over projects and delete
		foreach ($projects->project as $project) {
			$deleteUrl = $this->_baseUrl . 'projects/close_project/' . $project->id;
			$response = $this->postRequest($deleteUrl);
			echo 'Closed project: ' . $project->name . "<br />\n";
		}
		
		echo 'Project close completed.';
	}
	
	/**
	 * Screen scraper handling to open all projects.
	 * Uses an undocumented API endpoint.
	 *
	 * @access	public
	 * @return	void
	 */
	public function openAllProjects()
	{
		// retrieve all current projects
		$xml = $this->getProjects();
		
		// convert returned XML
		$projects = new SimpleXMLElement($xml);
		
		// perform an actual HTTP login
		$this->authenticate();
		
		// iterate over projects and delete
		foreach ($projects->project as $project) {
			$deleteUrl = $this->_baseUrl . 'projects/open_project/' . $project->id;
			$response = $this->postRequest($deleteUrl);
			echo 'Opened project: ' . $project->name . "<br />\n";
		}
		
		echo 'Project open completed.';
	}

}
