<?php
// no direct access
    defined('_JEXEC') or die;

/**
 * Joomla! Assets Compress Plugin
 *
 * @package		Joomla.Plugin
 * @subpackage	System.compress
 */
class plgSystemCompress extends JPlugin
{
	public $_document;
	public $_options;
	public $scriptFiles;
	public $scripts;
	public $stylesheets;
	public $styles;
	public $compressedJsFiles ;
	public $combinedJsFiles;
    public $compressedCssFiles ;
    public $combinedCssFiles;

	function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        $this->_options = array(
            'jscompression'	    => $this->params->get('jscompression', false),
            'csscompression'	=> $this->params->get('csscompression', false),
            'combinejs'	        => $this->params->get('combinejs', false),
            'combinecss'	    => $this->params->get('combinecss', false),
            'combinecache'	    => $this->params->get('combinecache', false),
            'compresscache'	    => $this->params->get('compresscache', false)
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
        //Avoid for the backend
        if(JFactory::getApplication()->isAdmin())
        {
            return;
        }

        $compressionOptions = $this->_getCompressorOptions('js');

        // if only the compression is on, do the following, otherwise let the combiner take care of compression also
        if($this->_options['jscompression'] && !$this->_options['combinejs'])
        {
            $this->_compressJsFiles();
        }

        if ($this->_options['combinejs'])
        {
            $this->_prepareAndCombineJs();
        }
        if($this->_options['csscompression'] && !$this->_options['combinecss'])
        {
            $this->_compressCssFiles();
        }

        if ($this->_options['combinecss'])
        {
            $this->_prepareAndCombineCss();
        }
    }

    private function _compressJsFiles()
    {
        $compressionOptions = $this->_getCompressorOptions('js');

        foreach($this->scriptFiles as $file => $attributes )
        {
            if(JMediaCompressor::compressFile(dirname(JPATH_SITE).$file, $compressionOptions))
            {
                $destinationFile = str_ireplace('.js','.min.js', $file);
                $this->compressedJsFiles[$destinationFile] = $attributes;
            }
            else
            {
                $this->compressedJsFiles[$file]= $attributes;
            }
        }
        $this->scriptFiles = $this->compressedJsFiles;
    }

    private function _compressCssFiles()
    {
        $compressionOptions = $this->_getCompressorOptions('css');

        foreach($this->stylesheets as $file => $attributes )
        {
            if(JMediaCompressor::compressFile(dirname(JPATH_SITE).$file, $compressionOptions))
            {
                $destinationFile = str_ireplace('.css','.min.css', $file);
                $this->compressedCssFiles[$destinationFile] = $attributes;
            }
            else
            {
                $this->compressedCssFiles[$file]= $attributes;
            }
        }
        $this->stylesheets = $this->compressedCssFiles;
    }

    private function _prepareAndCombineJs()
    {
        $currentFileSet = array();
        $currentAttribs = array();
        $fileCount      = 0;

        foreach($this->scriptFiles as $file => $attributes)
        {
            if($fileCount === 0)
            {
                $currentAttribs = $attributes;
                $currentFileSet[] = $file;
                $fileCount++;
                continue;
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

            if(count($this->scriptFiles)===$fileCount)
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
        $fileCount      = 0;

        foreach($this->stylesheets as $file => $attributes)
        {
            if($fileCount === 0)
            {
                $currentAttribs = $attributes;
                $currentFileSet[] = $file;
                $fileCount++;
                continue;
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

            if(count($this->stylesheets)===$fileCount)
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
           $filesFullPath[] = dirname(JPATH_SITE).$file;

        }
        $destinationFile = str_ireplace('.js','.combined.js', $files[0]);
        JMediaCombiner::combineFiles($filesFullPath,$this->_getCombinerOptions('js'),dirname(JPATH_SITE).$destinationFile);

        var_dump($this->_getCombinerOptions('js'));
        return $destinationFile;
    }

    private function _combineCssFiles($files)
    {
        $filesFullPath = array();
        // Set full file path in order to combiner to work properly
        foreach ($files as $file)
        {
            $filesFullPath[] = dirname(JPATH_SITE).$file;

        }
        $destinationFile = str_ireplace('.css','.combined.css', $files[0]);
        JMediaCombiner::combineFiles($filesFullPath,$this->_getCombinerOptions('css'),dirname(JPATH_SITE).$destinationFile);

        var_dump($this->_getCombinerOptions('css'));
        return $destinationFile;
    }
    private function _getCompressorOptions($type)
    {
        $tmp = explode(';', $this->params->get('compressoptions'));

        $options['type'] = $type;

        foreach($tmp as $option)
        {
            $tmpOption = explode('=',$option);
            if(count($tmpOption) != 2){
                continue;
            }
            $tmpOption[0]=trim($tmpOption[0]);
            $tmpOption[1]=trim($tmpOption[1]);

            if($tmpOption[1]==='true' || $tmpOption[1]=== 'false')
            {
                ($tmpOption[1] === 'true') ?$tmpOption[1] = true: $tmpOption[1]= false;
            }
            $options[$tmpOption[0]] = $tmpOption[1];
            $tmpOption=array();
        }
        return $options;
    }

    private function _getCombinerOptions($type)
    {
        $tmp = explode(';', $this->params->get('combineoptions'));

        $options['type'] = $type;

        // If compression is also on for this type, pass options for the compressor
        if ($this->params->get($type.'compression'))
        {
            $options['COMPRESS'] = true;
            $options['COMPRESS_OPTIONS'] = $this->_getCompressorOptions($type);

        }

        foreach($tmp as $option)
        {
            $tmpOption = explode('=',$option);
            if(count($tmpOption) != 2){
                continue;
            }
            $tmpOption[0]=trim($tmpOption[0]);
            $tmpOption[1]=trim($tmpOption[1]);

            if($tmpOption[1]==='true' || $tmpOption[1]=== 'false')
            {
                ($tmpOption[1] === 'true') ?$tmpOption[1] = true: $tmpOption[1]= false;
            }
            $options[$tmpOption[0]] = $tmpOption[1];
            $tmpOption=array();
        }
        return $options;
    }

}