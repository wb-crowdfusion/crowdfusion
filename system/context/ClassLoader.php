<?php
/**
 * Handles registering directories of class files and autoloading them upon use
 *
 * @version   $Id: $
 *
 */

class ClassLoader
{
    private static $registered = false;
    private static $classNames = array();
    private static $vcsPatterns = array('.svn', '_svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg');

    /**
     * Scans a directory recursively for all files with
     * a particular extension and will add any classes
     * it finds with the full path.
     *
     * @param string $directory
     * @param string $extensionToFind
     * @param string $bypassDirectories
     *
     * @throws ApplicationContextException
     *
     * @return void
     *
     */
	public static function addDirectory($directory, $extensionToFind = '.php', $bypassDirectories = '')
	{
        self::register();

		$dir = new SplFileInfo($directory);
		if (!$dir->isDir()) {
		    throw new ApplicationContextException('Cannot add directory to ClassLoader, directory does not exist: ' . $directory);
        }

        if (!is_array($bypassDirectories)) {
            $bypassDirectories = explode(',', $bypassDirectories);
        }

		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
		foreach ($objects as $name => $object) {
		    if (self::isVcsFile($name)) {
		        continue;
            }

            $tmpPath = explode(DIRECTORY_SEPARATOR, ltrim(str_replace($directory, '', $name), DIRECTORY_SEPARATOR));
            $rootNode = array_shift($tmpPath);
            if (in_array($rootNode, $bypassDirectories)) {
                continue;
            }

			if (!$object->isFile()) {
			    continue;
            }

            $extension = substr($object->getFilename(), strlen($extensionToFind)*-1);
            if ($extension !== $extensionToFind) {
                continue;
            }

			self::addFile($object, $extensionToFind);
		}
	}

    /**
     * Adds a class directory assuming it has PSR-0 or PEAR naming standards.
     * https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md
     * http://pear.php.net/manual/en/standards.naming.php
     *
     * @param string $directory
     * @param string $extensionToFind
     * @param bool   $isPsr0 defaults to true
     * @param string $prefix prepends to classNames found, useful for loading pear dirs
     *
     * @throws ApplicationContextException
     *
     * @return void
     *
     */
	public static function addClassDirectory($directory, $extensionToFind = '.php', $isPsr0 = true, $prefix = '')
	{
        self::register();

		$dir = new SplFileInfo($directory);
		if (!$dir->isDir())
		    throw new ApplicationContextException('Cannot add PSR-0 or PEAR style directory to ClassLoader, directory does not exist: ' . $directory);

		$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir), RecursiveIteratorIterator::SELF_FIRST);
		foreach ($objects as $name => $object) {
		    if (self::isVcsFile($name)) {
		        continue;
            }

			if (!$object->isFile()) {
			    continue;
            }

            $extension = substr($object->getFilename(), strlen($extensionToFind)*-1);
            if ($extension !== $extensionToFind) {
                continue;
            }

            $nsSeparator = $isPsr0 ? '\\' : '_';
            $className = str_replace(DIRECTORY_SEPARATOR, $nsSeparator, ltrim(str_replace($directory, '', $name), DIRECTORY_SEPARATOR));
			self::addClass($prefix . str_replace($extension, '', $className), $object->getRealPath());
		}
	}

    /**
     * Evaluates a file and if it looks like a php
     * class it will add it.  This prevents odd php files
     * that aren't classes from being auto loaded like
     * "bootstrap.php" or "autoload.php", etc.
     *
     * @param string $filePath
     * @param string $extensionToFind
     *
     * @return void
     *
     */
	public static function addFile($filePath, $extensionToFind = '.php')
    {
		if (!$filePath instanceof SplFileInfo) {
			$filePath = new SplFileInfo($filePath);
        }

		$firstChar = substr($filePath->getBasename(), 0, 1);
		$extension = substr($filePath->getFilename(), strlen($extensionToFind)*-1);
		$className = $filePath->getBasename($extension);
		if ($extension == $extensionToFind && $firstChar === strtoupper($firstChar)) {
			self::addClass($className, $filePath->getRealPath());
		}
	}

    /**
     * Adds a class name and file path.
     *
     * @param string $className
     * @param string $filePath
     *
     * @throws ApplicationContextException
     *
     * @return void
     *
     */
	public static function addClass($className, $filePath)
	{
        if (self::classExists($className)) {
			throw new ApplicationContextException('Cannot add class file [' . $filePath . '], class with same name already defined by [' . self::$classNames[$className] . ']');
        }

        self::$classNames[$className] = $filePath;
	}

    /**
     * Returns true if the class has been registered
     *
     * @param string $className
     *
     * @return bool
     *
     */
	public static function classExists($className)
	{
        return array_key_exists($className, self::$classNames);
	}

    /**
     * Returns all of the registered class names
     *
     * @return array
     *
     */
	public static function getClassNames()
	{
		return self::$classNames;
	}

    /**
     * Adds an array of class names to paths
     *
     * @param array $classMap Class to filename map
     *
     */
    public static function setClassNames(array $classMap)
    {
        self::register();
        self::$classNames = $classMap;
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     *
     */
    public static function register($prepend = false)
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register(array(__CLASS__, 'loadClass'), true, $prepend);
        self::$registered = true;
    }

    /**
     * Unregisters this instance as an autoloader.
     *
     */
    public static function unregister()
    {
        if (!self::$registered) {
            return;
        }

        spl_autoload_unregister(array(__CLASS__, 'loadClass'));
        self::$registered = false;
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string    $class The name of the class
     *
     * @return bool|null True, if loaded
     *
     */
    public static function loadClass($class)
    {
        if ($file = self::findFile($class)) {
            include_once $file;
            return true;
        }

        return null;
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|null The path, if found
     *
     */
    public static function findFile($class)
    {
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        if (isset(self::$classNames[$class])) {
            return self::$classNames[$class];
        }

        return null;
    }

    /**
     * Returns true if file path contains a vcs name.
     *
     * @param string $fileName
     *
     * @return bool
     *
     */
    public static function isVcsFile($fileName)
    {
        foreach (self::$vcsPatterns as $pattern) {
            if (strpos($fileName, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
}