<?php
/**
 * @package		DigiCom
 * @author 		ThemeXpert http://www.themexpert.com
 * @copyright	Copyright (c) 2010-2015 ThemeXpert. All rights reserved.
 * @license 	GNU General Public License version 3 or later; see LICENSE.txt
 * @since 		1.0.0
 */

defined('_JEXEC') or die;

// load digicom helperfile

JLoader::discover('DigiComSiteHelper', JPATH_SITE . '/components/com_digicom/helpers/');
class plgSystemDigiCom extends JPlugin{

	/**
	 * Load the language file on instantiation. Note this is only available in Joomla 3.1 and higher.
	 * If you want to support 3.0 series you must override the constructor
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	/*
	* get the digicom configuration
	*
	* @var object
	* @since digicom 1.0.0-beta.5
	*/
	protected $configs = null;

	/**
	 * Plugin method with the same name as the event will be called automatically.
	 */

	public function onAfterSidebarMenu($params = array()) {

		JPluginHelper::importPlugin('digicom');
		JPluginHelper::importPlugin('digicom_pay');
		$dispatcher = JDispatcher::getInstance();

		$db = JFactory::getDBO();
		$sql = "SELECT `extension_id`,`element`,`folder`,`enabled`,`params` from `#__extensions` WHERE `type` = 'plugin' AND `folder` in ('digicom', 'digicom_pay')";
		$db->setQuery($sql);
		$plugins = $db->loadObjectList();
		if(!count($plugins)) return false;

		$results = array();
		$subject = 'JEventDispatcher';
		$subject = '';
		foreach ($plugins as $key => $value) {
			JLoader::registerPrefix('plg'.$value->folder, JPATH_SITE . '/plugins/'.$value->folder);
			$config = json_decode($value->params, true); 

			$className = 'plg'.$value->folder.$value->element;
			if(method_exists($className,'onSidebarMenuItem')){
				JFactory::getLanguage()->load('plg_'.$value->folder.'_'.$value->element, JPATH_ADMINISTRATOR);
				$class = new $className($dispatcher, $config);
				$results []= '<i class="digi-micro-btn icon-'.($value->enabled ? 'publish' : 'unpublish').'"></i> '. $class->onSidebarMenuItem(array());					
			}
		}

		if(!$results) return;

		echo '<h3>' . JText::_('PLG_SYSTEM_DIGICOM_PLUGINS_LIST') . '</h3>';
		echo '<ul>';
			foreach ($results as $key => $value) {
					echo '<li>'. $value .'</li>';
			}
		echo '</ul>';

		return;

	}



	public function getConfigs(){
		if( !$this->configs ) {
			$config = JComponentHelper::getComponent('com_digicom');
			$this->configs = $config->params;
		}
		return $this->configs;
	}

}
