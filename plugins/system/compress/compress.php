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
    var $_document;
    var $_options;
    var $scriptFiles;
    var $scripts;
    var $stylesheets;
    var $styles;
    var $compressedJsFiles ;
    var $combinedJsFiles;

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
    }

    function onBeforeCompileHead()
    {

        if(JFactory::getApplication()->isAdmin())
        {
            return;
        }

        $compressionOptions = $this->_getCompressorOptions('js');

        // if only the compression is on, do the following, otherwise let the combiner take care of compression also
        if($this->_options['jscompression'] && !$this->_options['combinejs'])
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

        if ($this->_options['combinejs'])
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