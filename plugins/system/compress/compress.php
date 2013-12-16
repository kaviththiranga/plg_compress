<?php

defined('_JEXEC') or die;

/**
 * Joomla! Assets Compress Plugin
 *ssd
 * @package     Joomla.Plugin
 * @subpackage  System.compress
 */
class plgSystemCompress extends JPlugin
{
	private $_document;

	private $_options;

	public $scriptFiles;

	public $scripts;

	public $stylesheets;

	public $styles;

	public $compressedJsFiles;

	public $combinedJsFiles;

	public $compressedCssFiles;

	public $combinedCssFiles;

	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$this->_options = array(
			'jscompression'	    => $this->params->get('jscompression', false),
			'csscompression'	=> $this->params->get('csscompression', false),
			'combinejs'	        => $this->params->get('combinejs', false),
			'combinecss'	    => $this->params->get('combinecss', false),
			'combinecache'	    => $this->params->get('combinecache', false),
			'compresscache'	    => $this->params->get('compresscache', false),
			'cachetime'         => $this->params->get('cachetime', 1) * 24 * 60 * 60,
			'compresssavepath'  => $this->params->get('compresssavepath'),
			'combinesavepath'   => $this->params->get('combinesavepath'),
			'compressprefix'    => $this->params->get('compressprefix'),
			'combineprefix'     => $this->params->get('combineprefix')
		);

		$this->_document = JFactory::getDocument();

		$this->scriptFiles    = &$this->_document->_scripts;
		$this->scripts        = &$this->_document->_script;
		$this->stylesheets    = &$this->_document->_styleSheets;
		$this->styles         = &$this->_document->_style;
		$this->compressedJsFiles = array();
		$this->combinedJsFiles   = array();
		$this->compressedCssFiles = array();
		$this->combinedCssFiles   = array();
	}

	function onBeforeCompileHead()
	{
		// Avoid for the backend
		if (JFactory::getApplication()->isAdmin())
		{
			return;
		}

		// If only the compression is on, do the following, otherwise let the combiner take care of compression also
		if ($this->_options['jscompression'] && !$this->_options['combinejs'])
		{
			$this->_compressJsFiles();
			$this->_compressDeclarations('js');
		}
		elseif ($this->_options['jscompression'] && $this->_options['combinejs'])
		{
			$this->_prepareAndCombineJs();
			$this->_compressDeclarations('js');
		}
		else if (!$this->_options['jscompression'] && $this->_options['combinejs'])
		{
			$this->_prepareAndCombineJs();
		}

		if ($this->_options['csscompression'] && !$this->_options['combinecss'])
		{
			$this->_compressCssFiles();
			$this->_compressDeclarations('css');
		}
		elseif ($this->_options['csscompression'] && $this->_options['combinecss'])
		{
			$this->_prepareAndCombineCss();
			$this->_compressDeclarations('css');
		}
		elseif (!$this->_options['csscompression'] && $this->_options['combinecss'])
		{
			$this->_prepareAndCombineCss();
		}
	}

	private function _compressJsFiles()
	{
		$compressionOptions = $this->_getCompressorOptions('js');

		foreach ($this->scriptFiles as $file => $attributes )
		{

			$destinationFile = '';

			// If save path is not defined, try to save in the location of source file
			if ($this->_getSavePath('compress', 'js') === null && file_exists(dirname(JPATH_SITE) . $file))
			{
				$destinationFile = str_ireplace('.js', '.' . $this->_getPrefix('compress') . '.js', $file);
			}
			elseif ($this->_getSavePath('compress', 'js') === null && !file_exists(dirname(JPATH_SITE) . $file))
			// This means that the file path is not a relative url, in this case, a save path is a must
			{
				throw new RuntimeException(JText::sprintf('SYSTEM_COMPRESS_PLUGIN_CONFIG_ERROR_NO_SAVE_PATH_DEFINED'));
			}
			else
			{
				$destinationFile = $this->_getSavePath('compress', 'js') . str_ireplace('.js', '.' . $this->_getPrefix('compress') . '.js',
									JFile::getName($file)
									);
			}

			if ($this->_options['compresscache'] && file_exists(JPATH_SITE . DS . $destinationFile)
				&& (time() - $this->_options['cachetime'] < filemtime(JPATH_SITE . DS . $destinationFile)))
			{
				$this->compressedJsFiles[$destinationFile] = $attributes;
			}
			elseif (file_exists(dirname(JPATH_SITE) . $file)
					&& JMediaCompressor::compressFile(dirname(JPATH_SITE) . $file, $compressionOptions, $destinationFile))
			{

				$this->compressedJsFiles[$destinationFile] = $attributes;
			}
			elseif (!file_exists(dirname(JPATH_SITE) . $file)
					&& JMediaCompressor::compressFile($file, $compressionOptions, $destinationFile))
			{
				$this->compressedJsFiles[$destinationFile] = $attributes;
			}
			else
			{
				$this->compressedJsFiles[$file] = $attributes;
			}
		}
		$this->scriptFiles = $this->compressedJsFiles;
	}

	private function _compressCssFiles()
	{
		$compressionOptions = $this->_getCompressorOptions('css');

		foreach ($this->stylesheets as $file => $attributes )
		{
			$destinationFile = '';

			// If save path is not defined, try to save in the location of source file
			if ($this->_getSavePath('compress', 'css') === null && file_exists(dirname(JPATH_SITE) . $file))
			{
				$destinationFile = str_ireplace('.css', '.' . $this->_getPrefix('compress') . '.css', $file);
			}
			elseif ($this->_getSavePath('compress', 'css') === null && !file_exists(dirname(JPATH_SITE) . $file))
			// This means that the file path is not a relative url, in this case, a save path is a must
			{
				throw new RuntimeException(JText::sprintf('SYSTEM_COMPRESS_PLUGIN_CONFIG_ERROR_NO_SAVE_PATH_DEFINED'));
			}
			else
			{
				$destinationFile = $this->_getSavePath('compress', 'css') . str_ireplace('.css', '.' . $this->_getPrefix('compress') . '.css',
									JFile::getName($file)
									);
			}


			if ($this->_options['compresscache'] && file_exists(JPATH_SITE . DS . $destinationFile)
				&& (time() - $this->_options['cachetime'] < filemtime(JPATH_SITE . DS . $destinationFile)))
			{
				$this->compressedCssFiles[$destinationFile] = $attributes;
			}
			elseif (file_exists(dirname(JPATH_SITE) . $file)
					&& JMediaCompressor::compressFile(dirname(JPATH_SITE) . $file, $compressionOptions, $destinationFile))
			{

				$this->compressedCssFiles[$destinationFile] = $attributes;
			}
			elseif (!file_exists(dirname(JPATH_SITE) . $file)
					&& JMediaCompressor::compressFile($file, $compressionOptions, $destinationFile))
			{
				$this->compressedCssFiles[$destinationFile] = $attributes;
			}
			else
			{
				$this->compressedCssFiles[$file] = $attributes;
			}
		}
		$this->stylesheets = $this->compressedCssFiles;
	}

	private function _compressDeclarations($case)
	{
		switch ($case)
		{

			case 'js':    // Compress Script declarations
							if (isset ($this->scripts['text/javascript']))
							{
								$this->scripts['text/javascript'] = JMediaCompressor::compressString($this->scripts['text/javascript'], $this->_getCompressorOptions('js'));
							}
							break;

			case  'css':    // Compress Style declarations
							if (isset ($this->styles['text/css']))
							{
								$this->styles['text/css'] = JMediaCompressor::compressString($this->styles['text/css'], $this->_getCompressorOptions('css'));
							}
							break;
		}
	}

	private function _prepareAndCombineJs()
	{
		$currentFileSet = array();
		$currentAttribs = array();
		$preservedFiles = array();
		$fileCount      = 0;

		foreach ($this->scriptFiles as $file => $attributes)
		{
			if ($fileCount === 0)
			{
				$currentAttribs = $attributes;
				$currentFileSet[] = $file;
				$fileCount++;

				// Do not skip if only one file is to be combined.
				if (count($this->scriptFiles) != 1)
				{
					continue;
				}

			}
			// Only combine files that have similar attributes, divide files into separate sets depending on attributes
			if (md5(serialize($currentAttribs)) !== md5(serialize($attributes)))
			{
				$combinedFile = $this->_combineJsFiles($currentFileSet);
				$this->combinedJsFiles[$combinedFile] = $currentAttribs;

				$currentAttribs = $attributes;
				$currentFileSet = array();
			}
			$fileCount++;
			$currentFileSet[] = $file;

			if (count($this->scriptFiles) <= $fileCount)
			{
				$combinedFile = $this->_combineJsFiles($currentFileSet);
				$this->combinedJsFiles[$combinedFile] = $currentAttribs;
			}

		}
		$this->scriptFiles = $this->combinedJsFiles;
	}

	private function _prepareAndCombineCss()
	{
		$currentFileSet = array();
		$currentAttribs = array();
		$preservedFiles = array();
		$fileCount      = 0;

		foreach ($this->stylesheets as $file => $attributes)
		{
			if ($fileCount === 0)
			{
				$currentAttribs = $attributes;
				$currentFileSet[] = $file;
				$fileCount++;

				// Do not skip if only one file is to be combined.
				if (count($this->stylesheets) != 1)
				{
					continue;
				}
			}
			// Only combine files that have similar attributes, divide files into separate sets depending on attributes
			if (md5(serialize($currentAttribs)) !== md5(serialize($attributes)))
			{
				$combinedFile = $this->_combineCssFiles($currentFileSet);
				$this->combinedCssFiles[$combinedFile] = $currentAttribs;

				$currentAttribs = $attributes;
				$currentFileSet = array();
			}
			$fileCount++;
			$currentFileSet[] = $file;

			if (count($this->stylesheets) <= $fileCount)
			{
				$combinedFile = $this->_combineCssFiles($currentFileSet);
				$this->combinedCssFiles[$combinedFile] = $currentAttribs;
			}
		}
		$this->stylesheets = $this->combinedCssFiles;
	}

	private function _combineJsFiles($files)
	{
		$filesFullPath = array();

		// Set full file path in order to combiner to work properly
		foreach ($files as $file)
		{
			if(file_exists(dirname(JPATH_SITE).$file)){
				$filesFullPath[] = dirname(JPATH_SITE).$file;
			}
			else
			{
				$filesFullPath[] = $file;
			}
		}

		$destinationFile = '';

		if ($this->_getSavePath('combine', 'js') === null){

			$destinationFile = str_ireplace(JFile::getName($files[0]), md5(serialize($files)) . '.' . $this->_getPrefix('combine') . '.js', $files[0]);
		}
		else
		{
			$destinationFile = $this->_getSavePath('combine', 'js') . md5(serialize($files)) . '.' . $this->_getPrefix('combine') . '.js';
		}

		if ($this->_options['jscompression'])
		{
			$destinationFile = str_ireplace('.js', '.' . $this->_getPrefix('compress') . '.js', $destinationFile);
		}
		if ($this->_options['combinecache'] && file_exists(JPATH_SITE . DS . $destinationFile)
			&& (time() - $this->_options['cachetime'] < filemtime(JPATH_SITE . DS . $destinationFile)))
		{
			return $destinationFile;
		}
		elseif (count($filesFullPath) != 0)
		{
			JMediaCombiner::combineFiles($filesFullPath, $this->_getCombinerOptions('js'), $destinationFile);
		}
		return $destinationFile;
	}

	private function _combineCssFiles($files)
	{
		$filesFullPath = array();

		// Set full file path in order to combiner to work properly
		foreach ($files as $file)
		{
			if (file_exists(dirname(JPATH_SITE) . $file))
			{
				$filesFullPath[] = dirname(JPATH_SITE) . $file;
			}
			else
			{
				$filesFullPath[] = $file;
			}
		}

		$destinationFile = '';

		if ($this->_getSavePath('combine', 'css') === null)
		{
			$destinationFile = str_ireplace(JFile::getName($files[0]), md5(serialize($files)) . '.' . $this->_getPrefix('combine') . '.css', $files[0]);
		}
		else
		{
			$destinationFile = $this->_getSavePath('combine', 'css') . md5(serialize($files)) . '.' . $this->_getPrefix('combine') . '.css';
		}

		if ($this->_options['csscompression'])
		{
			$destinationFile = str_ireplace('.css', '.' . $this->_getPrefix('compress') . '.css', $destinationFile);
		}
		if ($this->_options['combinecache'] && file_exists(JPATH_SITE . DS . $destinationFile)
			&& (time() - $this->_options['cachetime'] < filemtime(JPATH_SITE . DS . $destinationFile)))
		{
			return $destinationFile;
		}
		elseif (count($filesFullPath) != 0)
		{
			JMediaCombiner::combineFiles($filesFullPath, $this->_getCombinerOptions('css'), $destinationFile);
		}
		return $destinationFile;
	}

	private function _getSavePath($case, $type)
	{
		$destination = null;
		$compressPath = $this->_options['compresssavepath'];
		$combinePath = $this->_options['combinesavepath'];

		switch ($case)
		{
			case 'compress' :   if (file_exists($compressPath) || mkdir($compressPath))
								{
									if (file_exists($compressPath . $type . '/') || mkdir($compressPath . $type . '/'))
									{
										$destination = $compressPath . $type . '/';
									}
									else
									{
										$destination = $compressPath;
									}
								}
								break;

			case 'combine'  :    if (file_exists($combinePath) || mkdir($combinePath))
								{
									if (file_exists($combinePath . $type . '/') || mkdir($combinePath . $type . '/'))
									{
										$destination = $combinePath . $type . '/';
									}
									else
									{
										$destination = $combinePath;
									}
								}
								break;

			default         :   $destination = null;
		}

		return $destination;

	}

	private function _getPrefix($case)
	{
		switch ($case)
		{
			case 'compress' :   if (!empty($this->_options['compressprefix']))
								{
									return $this->_options['compressprefix'];
								}
								else
								{
									return 'min';
								}
			case 'combine' :   if (!empty($this->_options['combineprefix']))
								{
									return $this->_options['combineprefix'];
								}
								else
								{
									return 'combined';
								}

		}
		return '';
	}
	private function _getCompressorOptions($type)
	{
		$tmp = explode(';', $this->params->get('compressoptions'));

		$options['type'] = $type;

		foreach ($tmp as $option)
		{
			$tmpOption = explode('=', $option);
			if (count($tmpOption) != 2)
			{
				continue;
			}
			$tmpOption[0] = trim($tmpOption[0]);
			$tmpOption[1] = trim($tmpOption[1]);

			if ($tmpOption[1] === 'true' || $tmpOption[1] === 'false')
			{
				($tmpOption[1] === 'true') ?$tmpOption[1] = true: $tmpOption[1] = false;
			}
			$options[$tmpOption[0]] = $tmpOption[1];
			$tmpOption = array();
		}
		return $options;
	}

	private function _getCombinerOptions($type)
	{
		$tmp = explode(';', $this->params->get('combineoptions'));

		$options['type'] = $type;

		// If compression is also on for this type, pass options for the compressor
		if ($this->params->get($type . 'compression'))
		{
			$options['COMPRESS'] = true;
			$options['COMPRESS_OPTIONS'] = $this->_getCompressorOptions($type);

		}

		foreach ($tmp as $option)
		{
			$tmpOption = explode('=', $option);
			if (count($tmpOption) != 2)
			{
				continue;
			}
			$tmpOption[0] = trim($tmpOption[0]);
			$tmpOption[1] = trim($tmpOption[1]);

			if ($tmpOption[1] === 'true' || $tmpOption[1] === 'false')
			{
				($tmpOption[1] === 'true') ?$tmpOption[1] = true: $tmpOption[1] = false;
			}
			$options[$tmpOption[0]] = $tmpOption[1];
			$tmpOption = array();
		}
		return $options;
	}

}
