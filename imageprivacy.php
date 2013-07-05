<?php

defined('_JEXEC') or die;

/**
 * Plugin to using custom privacy for JoomGallery images. (With jomsocial)
 *
 * @package JoomGallery
 * @since   1.0
 * @author 	mManishTrivedi 
 */
class plgJoomGalleryImagePrivacy extends JPlugin
{
  /**
   * Constructor
   *
   * @param   object  $subject  The object to observe
   * @param   array   $config   An array that holds the plugin configuration
   * @return  void
   * @since   1.0
   */
  public function __construct(& $subject, $config)
  {
    parent::__construct($subject, $config);
    $this->checkXmlFile();
    $this->loadHelpers(); 
  }
  
  
  /**
   * Checks existence of the XML file and outputs a notice if it was not found
   * Additional this plugin is detached from all events, so it won't be called anymore
   * @return  boolean True if file was found, false otherwise
   */
  protected function checkXmlFile()
  {
    if(!is_file(dirname(__FILE__).'/privacyfield/privacy.xml'))
    {
      JFactory::getApplication()->enqueueMessage(JText::_('Privacy field not found'), 'warning');

      $this->_subject->detach($this);
    }
  }
  
  
  /**
   * Checks existence of the XML file and outputs a notice if it was not found
   * Additional this plugin is detached from all events, so it won't be called anymore
   * @return  boolean True if file was found, false otherwise
   */
  public function loadHelpers()
  {
  	$file = dirname(__FILE__).'/helpers/helper.php';
    if(!is_file($file))
    {
      JFactory::getApplication()->enqueueMessage(JText::_('Privacy field not found'), 'warning');
      $this->_subject->detach($this);
    }
    
    include_once $file;
  }
  
  

   /**
   * onJoomAfterDisplayDetailImage event
   * Method is called by the view (default detail view)
   *
   * @param   object  $image  Image data of the image displayed
   */
  public function onJoomAfterDisplayDetailImage($image)
  {
  	$msg = "You don't have sufficient permission for access this image.";
  	$url = $_SERVER['HTTP_REFERER'];  //  Return to back url
  	if(!JoomImagePrivacyHelper::isAccess($image->id, JFactory::getUser()->id)) {
  		JFactory::getApplication()->redirect($url, $msg);
  	}
  }
  
  public function XonJoomAfterDisplayCatThumb($catId)
  {
  	
  }
  
	public function onJoomAfterDisplayThumb($imageId)
	{
		// TODO:: Add Java-Script to hide Image if not accessible
	  	
	}
  
  

  /**
   * onContentPrepareForm event
   * Method is called after the form was instantiated
   *
   * @param   object  $form The form to be altered
   * @param   array   $data The associated data for the form
   * @return  boolean True on success, false otherwise
   * @since   1.0
   */
  public function onContentPrepareForm($form, $data)
  {
    if(!($form instanceof JForm))
    {
      $this->_subject->setError('JERROR_NOT_A_FORM');

      return false;
    }

    // Check we are manipulating a valid form
    $name = $form->getName();
    if(!in_array($name, array(_JOOM_OPTION.'.image', _JOOM_OPTION.'.edit')))
    {
      return true;
    }

    // Add the registration fields to the form
    JForm::addFormPath(dirname(__FILE__).'/privacyfield');
    $form->loadFile('privacy', false);

    return true;
  }

  /**
   * onContentPrepareData event
   * Method is called when data is retrieved for preparing a form
   *
   * @param   string  $context  The context for the data
   * @param   object  $data     The image data object
   * @return  void
   * @since   1.0
   */
  public function onContentPrepareData($context, $data)
  {
    // Check if we are manipulating a valid form
    if(!in_array($context, array(_JOOM_OPTION.'.image', _JOOM_OPTION.'.edit')))
    {
      return;
    }

    if(is_object($data) && !isset($data->privacy) && isset($data->id) && $data->id)
    {
      // Load the profile data from the database.
      $result = JoomImagePrivacyHelper::getData($data->id);

      // Merge the profile data
      $data->image_privacy = array();

      JForm::addFormPath(dirname(__FILE__).'/privacyfield');
      $form = JForm::getInstance('plgjoomgalleryimageprivacy.form', 'privacy');
      if($result)
      {
        $k = $result->privacy;
        if($form->getField('js_privacy', 'image_privacy'))
        {
          $data->image_privacy['js_privacy'] = $k;
        }
      }
    }
  }

  /**
   * onContentAfterSave event
   * Method is called after an image was stored successfully
   *
   * @param   string  $context  The context of the store action
   * @param   object  $table    The table object which was used for storing the image
   * @param   boolean $isNew    Determines wether it is a new image which was stored
   * @return  void
   * @since   1.0
   */
  public function onContentAfterSave($context, &$table, $isNew)
  {
    if(!isset($table->id) || !$table->id || $context != _JOOM_OPTION.'.image')
    {
      return;
    }

    try
    {
      $db = JFactory::getDbo();

      $data = JRequest::getVar('image_privacy', array(), 'post', 'array');
      JForm::addFormPath(dirname(__FILE__).'/privacyfield');
      $form = JForm::getInstance('plgjoomgalleryimageprivacy.form', 'privacy');
      foreach($data as $k => $v)
      {
        if($form->getField($k, 'image_privacy'))
        {
          $tuple = (int) $table->id.','.$db->q($table->owner).','.$db->q($v);
          $onDuplicate ='`user_id` ='.$db->q($table->owner).',`privacy` ='.$db->q($v);
        }
      }
      $query = "
				INSERT INTO `#__joomgallery_privacy` (`image_id`, `user_id`, `privacy`)
				VALUES ($tuple)
				ON DUPLICATE KEY UPDATE  $onDuplicate
			   ";

      if( isset($tuple) && !$db->setQuery($query)->query() ) {
          	throw new Exception($db->getErrorMsg());
        }
    }
    
    catch(Exception $e)
    {
      $this->_subject->setError($e->getMessage());
      return;
    }
  }

  /**
   * Removes all image privacy data for the given image ID
   *
   * Method is called after an image is deleted from the database
   *
   * @param   string  $context  The context of the delete action
   * @param   object  $table    The table object which was used for deleting the image
   * @return  void
   * @since   1.0
   */
  public function onContentAfterDelete($context, $table)
  {
    if(!isset($table->id) || !$table->id || $context != _JOOM_OPTION.'.image')
    {
      return;
    }

    try
    {
      $db = JFactory::getDbo();
      $query = $db->getQuery(true)
            ->delete(JoomImagePrivacyHelper::getTableName())
            ->where('image_id = '.(int) $table->id);
      $db->setQuery($query);
      if(!$db->query())
      {
        throw new Exception($db->getErrorMsg());
      }
    }
    catch(Exception $e)
    {
      $this->_subject->setError($e->getMessage());

      return;
    }
  }

  /**
   * After photo upload, this event will eventually trigger onContentAfterSave,
   * which will save additional fields
   * @row its instance of table
   */
   
  public function onJoomAfterUpload($row)
  {
  	$this->onContentAfterSave(_JOOM_OPTION.'.image', $row, false);
  }
}