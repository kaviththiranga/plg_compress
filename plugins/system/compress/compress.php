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
    function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);

        //Set the language in the class
        $config = JFactory::getConfig();
        $options = array(
            'defaultgroup'	=> 'page',
            'browsercache'	=> $this->params->get('browsercache', false),
            'caching'		=> false,
        );

        $this->_cache = JCache::getInstance('page', $options);
    }

    function onBeforeRender()
    {

    }

}