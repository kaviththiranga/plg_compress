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
    }

    function onBeforeCompileHead()
    {

        if(JFactory::getApplication()->isAdmin())
        {
            return;
        }

        $scriptFiles    = &$this->_document->_scripts;
        $scripts        = &$this->_document->_script;
        $stylesheets    = &$this->_document->_styleSheets;
        $styles         = &$this->_document->_style;
        $compressedJsFiles = array();
        $combinedJsFiles   = array();

        if($this->_options['jscompression'])
        {

           foreach($scriptFiles as $file => $attributes )
           {
               if(JMediaCompressor::compressFile(dirname(JPATH_SITE).$file, $this->_getCompressorOptions('js')))
               {
                   $destinationFile = str_ireplace('.js','.min.js', $file);
                   $compressedJsFiles[$destinationFile] = $attributes;
               }
               else
               {
                   $compressedJsFiles[$file]= $attributes;
               }
           }
           $scriptFiles = $compressedJsFiles;
        }

        if ($this->_options['combinejs'])
        {
            $currentFileSet = array();
            $currentAttribs = array();
            $fileCount      = 0;

            foreach($scriptFiles as $file => $attributes)
            {
                if($fileCount === 0)
                {
                    $currentAttribs = $attributes;
                    $currentFileSet[] = $file;

                }
                if (md5(serialize($currentAttribs)) !== md5(serialize($attributes)))
                {
                    var_dump($currentFileSet);
                    $currentAttribs = $attributes;

                    $currentFileSet = array();

                }
                $fileCount++;
                $currentFileSet[] = $file;
                if(count($scriptFiles)===$fileCount)
                {
                    var_dump($currentFileSet);
                }


                //var_dump($currentFileSet);



                //var_dump($currentAttribs);
                //var_dump($fileCount);
            }

        }

       //var_dump($scriptFiles);
        //var_dump($compressedJsFiles);

    }

    private function _getCompressorOptions($type)
    {
        return array('type' => $type, 'REMOVE_COMMENTS' => true, 'overwrite' => true);
    }

    private function _getCombinerOptions($type)
    {

    }

}