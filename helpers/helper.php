<?php

defined('_JEXEC') or die;

/**
 * @author 	mManishTrivedi 
 */
Class JoomImagePrivacyHelper 
{
	const ONLY_ME 		= 40;
	const FRIENDS 		= 30;
	const SITE_MEMBER 	= 20;
	const GUEST 		= 0; //Public
	
	static public function getData($imageId)
	{
		 // Load the profile data from the database.
      $db = JFactory::getDbo();
      $query = $db->getQuery(true)
            ->select('*')
            ->from(self::getTableName())
            ->where('image_id = '.(int) $imageId);
      $db->setQuery($query);
      return $db->loadObject();
	}
	
	static public function getTableName()
	{
		return '#__joomgallery_privacy';
		;
	}
	
	/**
	 * 
	 * @param int $imageId
	 * @param int $userId
	 */
	static public function isAccess($imageId, $userId = null)
	{
		$imageTouple = self::getData($imageId);
		
		// Image available for public OR 
		// (user is registered on site and privacy set to site member) OR
		// Owner of the image  
		if( 
			( $imageTouple->privacy == self::GUEST) ||
			( $userId && self::SITE_MEMBER == $imageTouple->privacy) || 
			( $userId && $userId == $imageTouple->user_id)
			 
		  ) {
			return true;	
		}
		
		// If image privact set to only me
		if($imageTouple->privacy == self::ONLY_ME ) {
			return false;
		}
		
		// if set to visible only friend
		if($imageTouple->privacy == self::FRIENDS) {
			include_once( JPATH_BASE . DS . 'components' . DS . 'com_community' . DS . 'libraries' . DS . 'core.php');
			CFactory::load( 'helpers' , 'friends' );
			$isFriend = CFriendsHelper::isConnected($userId, $imageTouple->user_id);
			return $isFriend; 
		} 
		
		// Exceptional cases
		return false;
	}
}