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



        if($this->_options['jscompression'])
        {

           foreach($this->scriptFiles as $file => $attributes )
           {
               if(JMediaCompressor::compressFile(dirname(JPATH_SITE).$file, $this->_getCompressorOptions('js')))
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
                    $currentFileSet[] = dirname(JPATH_SITE).$file;
                    $fileCount++;
                    continue;
                }

                if (md5(serialize($currentAttribs)) !== md5(serialize($attributes)))
                {
                    $combinedFile = $this->_compressJsFiles($currentFileSet);
                    $this->combinedJsFiles[$combinedFile] = $currentAttribs;

                    $currentAttribs = $attributes;
                    $currentFileSet = array();
                }
                $fileCount++;
                $currentFileSet[] = dirname(JPATH_SITE).$file;

                if(count($this->scriptFiles)===$fileCount)
                {
                    $combinedFile = $this->_compressJsFiles($currentFileSet);
                    $this->combinedJsFiles[$combinedFile] = $currentAttribs;
                 }
            }
            $this->scriptFiles = $this->combinedJsFiles;
        }
    }

    private function _compressJsFiles($files)
    {
        $destinationFile = str_ireplace('.js','.combined.js', $files[0]);
        JMediaCombiner::combineFiles($files,$this->_getCombinerOptions('js'),dirname(JPATH_SITE).$destinationFile);
        var_dump($files);
        return $files[0];
    }

    private function _getCompressorOptions($type)
    {
        return array('type' => $type, 'REMOVE_COMMENTS' => true, 'overwrite' => true);
    }

    private function _getCombinerOptions($type)
    {
        return array('type' => $type, 'FILE_COMMENTS' => true, 'overwrite' => true);
    }

}