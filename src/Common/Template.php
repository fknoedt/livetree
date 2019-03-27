<?php
/**
 * $descricaoAqui
 * @author filipe
 * @date 11/11/2017
 * @rotina $rotinaAqui
 */

namespace Common;

/**
 * Simple Template Engine
 * @TODO clean not-used placeholders
 * @package Common
 */
class Template
{

	/**
	 * path to the templates directory
	 * @var
	 */
	var $templatesPath;

	/**
	 * variables to be replace
	 * @var array
	 */
	var $aVar = array();

	/**
	 * placeholders to be replaced
	 * @var array
	 */
	var $aPlaceholder = array();

	/**
	 * Template constructor.
	 * @param $templatesPath
	 */
	public function __construct($templatesPath)
	{

		$this->templatesPath = $templatesPath;

	}

	/**
	 * adds variable => value to the template
	 * placeholder will always be (uppercase) {$VARNAME}
	 * @param $var -- variable name / placeholder
	 * @param $val -- variable value / value to replace placeholder
	 */
	function addVar($var,$val)
	{

		$this->aVar[$var] = $val;

		$var = '{$' . strtoupper($var) . '}';
		$this->aPlaceholder[$var] = $val;

	}

	/**
	 * adds $aVar to the list of placeholder => variables of the template
	 * @param $vars
	 */
	function addVars($vars)
	{

		foreach($vars as $key => $value) {
			$this->addVar($key, $value);
		}

	}

	/**
	 * displays the template file with variables
	 * @param $tplFile
	 * @throws \Exception
	 */
	function display($tplFile)
	{

		$html = $this->fetchTpl($tplFile);

		echo $html;

		// clean vars
		$this->aVar = [];

	}

	/**
	 * includes the .tpl.php file and gets it's output as html
	 * @param $tplFile
	 * @return string
	 * @throws \Exception
	 * @important cleans output buffering (ob_clean)
	 */
	function fetchTpl($tplFile)
	{

		// template file must be under tempates path
		if(! file_exists($this->templatesPath . $tplFile))
			throw new \Exception(__METHOD__ . ": invalid file: {$this->templatesPath}{$tplFile}");

		// template has arguments (variables)
		if(! empty($this->aVar)) {

			// instantiate each variable within function's scope
			foreach($this->aVar as $varName => $varValue) {

				$$varName = $varValue;

			}


		}

		// cleans output buffering, includes the .tpl.php file and returns it's output
		@ob_clean();

		ob_start();

		// requires the file allowing that the template uses PHP tags
		require ($this->templatesPath . $tplFile);

		$html = ob_get_clean();

		// converts placeholders into variables defined on $this->aPlaceholder
		if(! empty($this->aPlaceholder)) {

			$html = strtr($html,$this->aPlaceholder);

		}

		// TODO: clear non-used placeholders

		// clean vars
		$this->aVar = [];

		return $html;

	}


}