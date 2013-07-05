<?php

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class plgJoomGalleryImagePrivacyInstallerScript
{
	/**
	 * Called before any type of action
	 *
	 * @param   string  $type  Which action is happening (install|uninstall|discover_install)
	 * @param   object  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function preflight($type, $parent)
	{
		if($type == 'install' || $type == 'update') {
			$db = JFactory::getDBO();
			$query = '
						CREATE TABLE IF NOT EXISTS `#__joomgallery_privacy` (
						  `id` int(11) NOT NULL AUTO_INCREMENT,
						  `image_id` int(11) NOT NULL,
						  `user_id` int(11) NOT NULL,
						  `privacy` int(11) NOT NULL,
						  PRIMARY KEY (`id`),
						  KEY `image_id` (`image_id`,`user_id`)
						) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 
					';
			$db->setQuery($query)->query();
		}
	}

	/**
	 * Called on installation
	 *
	 * @param   object  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	function XX_install($parent){}

    /**
	 * Called on uninstallation
	 *
	 * @param   object  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	function XX_uninstall($parent){}

	/**
	 * Called after install
	 *
	 * @param   string  $type  Which action is happening (install|uninstall|discover_install)
	 * @param   object  $parent  The object responsible for running this script
	 *
	 * @return  boolean  True on success
	 */
	public function XX_postflight($type, $parent){}
}