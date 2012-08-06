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

        var_dump($scriptFiles);
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