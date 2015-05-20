<?php
/*
	RESTful Routing
	-----------------------------
	GET: read
	PUT: (over)write
	DELETE: delete
	POST: invoke custom processing
*/

class rest
{
	public function get($path, $func) 
	{
		return $this->route($path, $func, 'GET');
	}
	public function put($path, $func) 
	{
		return $this->route($path, $func, 'PUT');
	}
	public function post($path, $func) 
	{
		return $this->route($path, $func, 'POST');
	}
	public function head($path, $func) 
	{
		return $this->route($path, $func, 'HEAD');
	}
	public function delete($path, $func) 
	{
		return $this->route($path, $func, 'DELETE');
	}
	
	private function route($path, $func, $methods = null) 
	{
		if ($func === false) return false;
		static $method;
		if ($method === null) 
		{
			if (isset($_POST['_method'])) 
			{
				$method = $_POST['_method'];
			} 
			elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) 
			{
				$method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
			} 
			else 
			{
				$method = $_SERVER['REQUEST_METHOD'];
			}
		}
		if ($methods !== null) 
		{
			if (is_array($methods)) 
			{
				if (!in_array($method, $methods)) return false;
			} 
			else 
			{
				if (strpos(strval($methods), $method) === false) return false;
			}
		}
		static $url;
		if ($url === null) 
		{
			$url = parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH);
			$url = strtolower(trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), strlen(substr($url, 0, strrpos($url, '/')))), '/'));
		}
		$path = strtolower(trim($path, '/'));
		$scnf = str_replace('%p', '%[^/]', $path);
		$prnf = str_replace('%p', '%s', $path);
		$args = sscanf($url, $scnf);
		if (substr_count(str_replace('%%', '', $prnf), '%') !== count($args)) return false;
		$path = vsprintf($prnf, $args);

		if ($path !== $url) return false;
		$args = array_map(function($value) 
		{
			return is_string($value) ? urldecode($value) : $value;
		}, $args);
		return is_callable($func) ? call_user_func_array($func, $args) :
			$this->call(is_object($func) ? array($func, strtolower($method)) : $func, $args);
	}
	
	private function call($func, array $args = array()) 
	{
		if (is_callable($func)) return call_user_func_array($func, $args);
		if (is_string($func)) 
		{
			if (file_exists($func)) return require $func;
			if (strpos($func, '->') > 0) 
			{
				list($clazz, $method) = explode('->', $func, 2);
				if (class_exists($clazz)) 
				{
					$func = array(new $clazz, $method);
					if (is_callable($func)) return call_user_func_array($func, $args);
				}
			}
		}
		return $func;
	}
}

$route = new rest();
$route->get('/phpinfo', 'phpinfo');
