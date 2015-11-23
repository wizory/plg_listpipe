<?php
/**
 * Joomla! System plugin for ListPipe content generation
 *
 * @author Wizory (support@wizory.com)
 * @version 2.0.0
 * @copyright Copyright 2010
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.wizory.com
 */

// Joomla or DIE!
defined( '_JEXEC' ) or die( 'Restricted access' );

class JElementListPipeUser extends JElement
{
	function fetchElement($name, $value, &$node, $control_name)
	{
		$db = &JFactory::getDBO();
		$class = $node->attributes('class');
						
		$db->setQuery("SELECT u.id,u.username FROM #__users AS u ORDER BY u.username");
		$options = $db->loadObjectList();
		
		array_unshift($options, JHTML::_('select.option','0','- ' . JText::_('Select User') . ' -','id','username'));
		
		return JHTML::_('select.genericlist',$options,''.$control_name.'['.$name.'][]', 'class="'.$class.'"', 'id', 'username', $value, $control_name.$name );
	}
}
