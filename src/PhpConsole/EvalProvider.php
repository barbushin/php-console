<?php

namespace PhpConsole;

/**
 * Execute PHP code with some security & accessibility tweaks
 *
 * @package PhpConsole
 * @version 3.1
 * @link http://php-console.com
 * @author Sergey Barbushin http://linkedin.com/in/barbushin
 * @copyright © Sergey Barbushin, 2011-2013. All rights reserved.
 * @license http://www.opensource.org/licenses/BSD-3-Clause "The BSD 3-Clause License"
 */
class EvalProvider {

	protected $sharedVars = array();
	protected $openBaseDirs = array();
	protected $codeCallbackHandlers = array();
	protected $globalsBackup;

	/**
	 * Execute PHP code handling execution time, output & exception
	 * @param string $code
	 * @return EvalResult
	 */
	public function evaluate($code) {
		$code = $this->applyHandlersToCode($code);
		$code = $this->adaptCodeToEval($code);

		$this->backupGlobals();
		$this->applyOpenBaseDirSetting();

		$startTime = microtime(true);
		static::executeCode('', $this->sharedVars);
		$selfTime = microtime(true) - $startTime;

		ob_start();
		$result = new EvalResult();
		$startTime = microtime(true);
		try {
			$result->return = static::executeCode($code, $this->sharedVars);
		}
		catch(\Exception $exception) {
			$result->exception = $exception;
		}
		$result->time = abs(microtime(true) - $startTime - $selfTime);
		$result->output = ob_get_clean();

		$this->restoreGlobals();

		return $result;
	}

	/**
	 * Add callback that will be called with &$code var reference before code execution
	 * @param $callback
	 * @throws \Exception
	 */
	public function addCodeHandler($callback) {
		if(!is_callable($callback)) {
			throw new \Exception('Argument is not callable');
		}
		$this->codeCallbackHandlers[] = $callback;
	}

	/**
	 * Call added code handlers
	 * @param $code
	 * @return mixed
	 */
	protected function applyHandlersToCode($code) {
		foreach($this->codeCallbackHandlers as $callback) {
			call_user_func_array($callback, array(&$code));
		}
		return $code;
	}

	/**
	 * Store global vars data in backup var
	 */
	protected function backupGlobals() {
		$this->globalsBackup = array();
		foreach($GLOBALS as $key => $value) {
			if($key != 'GLOBALS') {
				$this->globalsBackup[$key] = $value;
			}
		}
	}

	/**
	 * Restore global vars data from backup var
	 */
	protected function restoreGlobals() {
		foreach($this->globalsBackup as $key => $value) {
			$GLOBALS[$key] = $value;
		}
		foreach(array_diff(array_keys($GLOBALS), array_keys($this->globalsBackup)) as $newKey) {
			if($newKey != 'GLOBALS') {
				unset($GLOBALS[$newKey]);
			}
		}
	}

	/**
	 * Execute code with shared vars
	 * @param $code
	 * @param array $sharedVars
	 * @return mixed
	 */
	protected static function executeCode($code, array $sharedVars) {
		unset($code);
		unset($sharedVars);

		foreach(func_get_arg(1) as $var => $value) {
			if(isset($GLOBALS[$var]) && $var[0] == '_') { // extract($this->sharedVars, EXTR_OVERWRITE) and $$var = $value do not overwrites global vars
				$GLOBALS[$var] = $value;
			}
			else {
				$$var = $value;
			}
		}

		return eval(func_get_arg(0));
	}

	/**
	 * Prepare code PHP tags be correctly passed to eval() function
	 * @param string $code
	 * @return string
	 */
	protected function trimPhpTags($code) {
		$replace = array(
			'~^(\s*)<\?=~s' => '\1echo ',
			'~^(\s*)<\?(php)?~is' => '\1',
			'~\?>\s*$~s' => '',
			'~<\?(php)?[\s;]*$~is' => '',
		);
		return preg_replace(array_keys($replace), $replace, $code);
	}

	/**
	 * Add semicolon to the end of code if it's required
	 * @param string $code
	 * @return string
	 */
	protected function forceEndingSemicolon($code) {
		$code = rtrim($code, "; \r\n");
		return $code[strlen($code) - 1] != '}' ? $code . ';' : $code;
	}

	/**
	 * Apply some default code handlers
	 * @param string $code
	 * @return string
	 */
	protected function adaptCodeToEval($code) {
		$code = $this->trimPhpTags($code);
		$code = $this->forceEndingSemicolon($code);
		return $code;
	}

	/**
	 * Protect response code access only to specified directories using http://www.php.net/manual/en/ini.core.php#ini.open-basedir
	 * IMPORTANT: classes autoload methods will work only for specified directories
	 * @param array $openBaseDirs
	 */
	public function setOpenBaseDirs(array $openBaseDirs) {
		$this->openBaseDirs = $openBaseDirs;
	}

	/**
	 * Autoload all PHP Console classes
	 */
	protected function forcePhpConsoleClassesAutoLoad() {
		foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(__DIR__), \RecursiveIteratorIterator::LEAVES_ONLY) as $path) {
			/** @var $path \SplFileInfo */
			if($path->isFile() && $path->getExtension() == 'php' && $path->getFilename() !== 'PsrLogger.php') {
				require_once($path->getPathname());
			}
		}
	}

	/**
	 * Set actual "open_basedir" PHP ini option
	 * @throws \Exception
	 */
	protected function applyOpenBaseDirSetting() {
		if($this->openBaseDirs) {
			$value = implode(PATH_SEPARATOR, $this->openBaseDirs);
			if(ini_get('open_basedir') != $value) {
				$this->forcePhpConsoleClassesAutoLoad();
				if(ini_set('open_basedir', $value) === false) {
					throw new \Exception('Unable to set "open_basedir" php.ini setting');
				}
			}
		}
	}

	/**
	 * Protect response code from reading/writing/including any files using http://www.php.net/manual/en/ini.core.php#ini.open-basedir
	 * IMPORTANT: It does not protects from system(), exec(), passthru(), popen() & etc OS commands execution functions
	 * IMPORTANT: Classes autoload methods will not work, so all required classes must be loaded before code evaluation
	 */
	public function disableFileAccessByOpenBaseDir() {
		$this->setOpenBaseDirs(array(__DIR__ . '/not_existed_dir' . mt_rand()));
	}

	/**
	 * Add var that will be implemented in PHP code executed from PHP Console debug panel (will be implemented in PHP Console > v3.0)
	 * @param $name
	 * @param $var
	 * @throws \Exception
	 * @internal param bool $asReference
	 */
	public function addSharedVar($name, $var) {
		$this->addSharedVarReference($name, $var);
	}

	/**
	 * Add var that will be implemented in PHP code executed from PHP Console debug panel (will be implemented in PHP Console > v3.0)
	 * @param $name
	 * @param $var
	 * @throws \Exception
	 * @internal param bool $asReference
	 */
	public function addSharedVarReference($name, &$var) {
		if(isset($this->sharedVars[$name])) {
			throw new \Exception('Var with name "' . $name . '" already added');
		}
		$this->sharedVars[$name] =& $var;
	}
}

class EvalResult {

	public $return;
	public $output;
	public $time;
	/** @var  \Exception|null */
	public $exception;
}
