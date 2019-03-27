<?php
/**
 * generic functions (helpers)
 */

namespace Common;


class Lib {

	/**
	 * dumps any variable with var_dump through output buffering
	 * @param $var
	 * @return string
	 */
	public static function varDump($var)
	{

		ob_clean();
		ob_start();

		var_dump($var);

		$dump = "<pre>" . ob_get_contents() . "</pre>";

		ob_end_clean();

		return $dump;

	}

	/**
	 * converts snake case to camel case
	 * @param $val -- text to be converted
	 * @param bool $bFirstCharUpper -- makes the first char upper case
	 * @return mixed|string
	 */
	public static function snakeToCamelCase($val, $bFirstCharUpper=false)
	{

		$val = str_replace(' ', '', ucwords(str_replace('_', ' ', $val)));

		if($bFirstCharUpper)
			$val = lcfirst($val);

		return $val;

	}

	/**
	 * convert camel case to snake case
	 * @see https://stackoverflow.com/a/1993772
	 * @param $value
	 * @return string
	 */
	public static function camelToSnakeCase($value)
	{
		preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $value, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}
		return implode('_', $ret);
	}

	/**
	 * converts and array or object to json
	 * @static
	 * @param mixed $in
	 * @return string (JSON)
	 */
	public static function var2json($in)
	{

		$_escape = function ($str) {

			return addcslashes($str, "\v\t\n\r\f\"\\/");

		};

		$out = "";

		if (is_object($in)) {

			$class_vars = get_object_vars(($in));
			$arr = array();

			foreach ($class_vars as $key => $val) {

				$arr[$key] = "\"{$_escape($key)}\":".self::var2json($val);
			}

			$val = implode(',', $arr);
			$out .= "{{$val}}";

		}
		elseif (is_array($in)) {

			$obj = false;
			$arr = array();

			foreach ($in AS $key => $val) {

				if (!is_numeric($key)) {

					$obj = true;

				}

				$arr[$key] = self::var2json($val);

			}

			if ($obj) {

				foreach ($arr as $key => $val) {

					$arr[$key] = "\"{$_escape($key)}\":{$val}";

				}

				$val = implode(',', $arr);
				$out .= "{{$val}}";

			}
			else {

				$val = implode(',', $arr);
				$out .= "[{$val}]";

			}

		}
		elseif (is_bool($in)) {

			$out .= $in ? 'true' : 'false';

		}
		elseif (is_null($in)) {

			$out .= 'null';

		}
		elseif (is_string($in)) {

			$out .= "\"{$_escape($in)}\"";

		}
		else {

			$out .= $in;

		}

		// pretty print
		$out = json_decode($out);
		$out = json_encode($out, JSON_PRETTY_PRINT);

		return "{$out}";

	}

	/**
	 * validates a date in given format
	 * @param $date
	 * @param string $format
	 * @return bool
	 */
	static public function isValidDate($date, $format='Y-m-d H:i:s')
	{

		return (\DateTime::createFromFormat($format, $date) !== false);

	}

	/**
	 * gets visitor info formatted as an array or HTML
	 * @param bool $bHtml
	 * @return array|string (HTML)
	 */
	static public function getVisitorInfo($bHtml=true)
	{

		$aInfo = array();

		$aInfo['time'] = date('Y-m-d H:i:s');

		$aInfo['ip'] = $_SERVER['REMOTE_ADDR'];

		$referrer = @$_SERVER['HTTP_REFERER'];

		if(empty($referrer))
			$referrer = 'direct access';

		$aInfo['referer'] = $referrer;

		if(session_id())
			$aInfo['session_id'] = session_id();

		$aBrowser = self::getBrowser();

		$aInfo = array_merge($aInfo,$aBrowser);

		// format output as html
		if($bHtml) {

			$a = array();

			foreach($aInfo as $info => $value) {

				// link to ip information page
				if($info == 'ip')
					$value = "<a href='https://www.infobyip.com/ip-{$value}.html'>{$value}</a>";

				$a[] = "{$info}: {$value}";

			}

			return implode('<br/>',$a);

		}
		else {

			return $aInfo;

		}

	}

	/**
	 * writes a file to disc with $content
	 * @param $content
	 * @param null $file
	 * @param string $path
	 * @param string $writeMode
	 * @throws \Exception
	 */
	public static function writeFile($content,$file=null, $path='./', $writeMode='a+')
	{

		// makes sure directory exists
		if(! is_dir($path)) {

			if(! mkdir($path,0777,true))
				throw new \Exception(__METHOD__ . ": it was not possible to create path: {$path}");

		}

		// if not defined, uses default name
		if(empty($file)) {

			// by default, filename is ddmmyyhhiiss.log
			$file = date('YmdHis') . ".log";

		}

		$filePath = $path . $file;

		$fp = fopen($filePath, $writeMode);

		fwrite($fp, $content . "\r\n");

		fclose($fp);

	}


	/**
	 * sends a notification to the sysadmin about the visitor (should be called once in a session)
	 */
	public static function notifyVisit()
	{

		$html = "Hooray, you got a visit on " . self::getFullUrlRequested() . '<br/><br/>' . self::getVisitorInfo();

		self::notifyAdmin($html,'Visitor on ' . self::getFullUrlRequested());

	}

	/**
	 * sends a notification about an exception to the sysadmin
	 * @param Exception $e
	 */
	public static function notifyError($e)
	{

		if(! is_object($e)) {

			$exceptionType = 'Unidentified Exception';
			$exceptionMsg = is_string($e) ? $e : \Common\Lib::varDump($e);
			$debug = "what can I do? =(";
			$file = "?";
			$line = "?";

		}
		else {

			$exceptionType = get_class($e);
			$exceptionMsg = $e->getMessage();
			$file = $e->getFile();
			$line = $e->getLine();
			$debug = "TODO";

			// TODO: identify debug based on Exception class (for example: database has lastQuery)

		}

		$html = "You got a(n) {$exceptionType}: {$exceptionMsg}<br/><br/>File: {$file}<br/>Line: {$line}<br/>Debug: {$debug}";

		self::notifyAdmin($html,'Exception on ' . self::getFullUrlRequested());

	}

	/**
	 * sends visitor info to sysadmin email
	 * @require constant SYSADMIN_EMAIL has to be set and a valid email
	 * @param $html (email body)
	 * @param $subject
	 */
	public static function notifyAdmin($html, $subject)
	{

		// avoids exceptions
		try {

			if(! defined('SYSADMIN_EMAIL'))
				throw new \Exception(__METHOD__ . ": constant SYSADMIN_EMAIL is required");

			$emailTo = SYSADMIN_EMAIL;

			if(! filter_var($emailTo,FILTER_VALIDATE_EMAIL))
				throw new \Exception(__METHOD__ . ": emailTo invalid: {$emailTo}");

			mail($emailTo,$subject,$html,self::getDefaultMailHeader("Admin Notifier",$emailTo));


		}
		catch(\Exception $e) {

			// also avoids exception while trying to write the log file
			try {

				$html .= "<br/><br/>Warning: it was not possible to send the email because of {$e->getMessage()}";

				// logs exception on disk
				self::writeFile($html);

			}
			catch (\Exception $e) {

				// ok, give up...but not for debug environment
				if(defined('DEBUG_MODE') && DEBUG_MODE)
					echo "Error trying to notifiy admin ({$subject}): neither email nor log file worked: {$e->getMessage()}";

			}

		}

	}

	/**
	 * return default header for mail
	 * @param $fromName
	 * @param $fromAddress
	 * @return string
	 */
	public static function getDefaultMailHeader($fromName,$fromAddress)
	{

		$headers =  'MIME-Version: 1.0' . "\r\n";
		$headers .= "From: {$fromName} <{$fromAddress}>" . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		return $headers;

	}

	/* * * THIRD PARTIES CODES * * */


	/**
	 * sniffs the user agent of the browser and return a nice object with all the information
	 * @src https://gist.github.com/james2doyle/5774516
	 * @see http://www.php.net/manual/en/function.get-browser.php#101125
	 * @return array
	 */
	public static function getBrowser()
	{

		$u_agent = $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version= "";

		// First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		} elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		} elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}

		// Next get the name of the useragent yes seperately and for good reason
		if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		} elseif(preg_match('/Firefox/i',$u_agent)) {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		} elseif(preg_match('/Chrome/i',$u_agent)) {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		} elseif(preg_match('/Safari/i',$u_agent)) {
			$bname = 'Apple Safari';
			$ub = "Safari";
		} elseif(preg_match('/Opera/i',$u_agent)) {
			$bname = 'Opera';
			$ub = "Opera";
		} elseif(preg_match('/Netscape/i',$u_agent)) {
			$bname = 'Netscape';
			$ub = "Netscape";
		}

		// finally get the correct version number
		$known = array('Version', $ub, 'other');
		$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
		if (!preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}

		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
				$version= $matches['version'][0];
			} else {
				$version= $matches['version'][1];
			}
		} else {
			$version= $matches['version'][0];
		}

		// check if we have a number
		if ($version==null || $version=="") {$version="?";}

		return array(
			'browser'      => $bname,
			'version'   => $version,
			'platform'  => $platform,
			'userAgent' => $u_agent,
			// 'pattern'    => $pattern
		);

	}

	/**
	 * returns full URL user has accessed
	 * @src https://stackoverflow.com/a/6768831/1932767
	 * @return string
	 */
	public static function getFullUrlRequested()
	{

		return (isset($_SERVER['HTTPS']) ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";

	}

}