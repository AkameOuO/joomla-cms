<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_users
 *
 * @copyright   Copyright (C) 2005 - 2019 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Users\Administrator\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Access\Access;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Helper\UserGroupsHelper;

/**
 * User Group Parent field..
 *
 * @since  1.6
 */
class GroupparentField extends ListField
{
	/**
	 * The form field type.
	 *
	 * @var        string
	 * @since   1.6
	 */
	protected $type = 'GroupParent';

	/**
	 * Method to clean the Usergroup Options from all children starting by a given father
	 *
	 * @param   array    $userGroupsOptions  The usergroup options to clean
	 * @param   integer  $fatherId           The father ID to start with
	 *
	 * @return  array  The cleaned field options
	 *
	 * @since   3.9.4
	 */
	private function cleanOptionsChildrenByFather($userGroupsOptions, $fatherId)
	{
		foreach ($userGroupsOptions as $userGroupsOptionsId => $userGroupsOptionsData)
		{
			if ((int) $userGroupsOptionsData->parent_id === (int) $fatherId)
			{
				unset($userGroupsOptions[$userGroupsOptionsId]);

				$userGroupsOptions = $this->cleanOptionsChildrenByFather($userGroupsOptions, $userGroupsOptionsId);
			}
		}

		return $userGroupsOptions;
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array  The field option objects
	 *
	 * @since   1.6
	 */
	protected function getOptions()
	{
		$options = UserGroupsHelper::getInstance()->getAll();
		$currentGroupId = $this->form->getValue('id');

		// Prevent to set yourself as parent
		if ($currentGroupId)
		{
			unset($options[$currentGroupId]);
		}

		// We should not remove any groups when we are creating a new group
		if ($currentGroupId !== null && $currentGroupId !== 0)
		{
			// Prevent parenting direct children and children of children of this item.
			$options = $this->cleanOptionsChildrenByFather($options, $currentGroupId);
		}

		$options      = array_values($options);
		$isSuperAdmin = Factory::getUser()->authorise('core.admin');

		// Pad the option text with spaces using depth level as a multiplier.
		for ($i = 0, $n = count($options); $i < $n; $i++)
		{
			// Show groups only if user is super admin or group is not super admin
			if ($isSuperAdmin || !Access::checkGroup($options[$i]->id, 'core.admin'))
			{
				$options[$i]->value = $options[$i]->id;
				$options[$i]->text = str_repeat('- ', $options[$i]->level) . $options[$i]->title;
			}
			else
			{
				unset($options[$i]);
			}
		}

		// Merge any additional options in the XML definition.
		return array_merge(parent::getOptions(), $options);
	}
}
