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
            'compression'	=> $this->params->get('compression', false),
        );

    }

    function onBeforeCompileHead()
    {

    }

}