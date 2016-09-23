<?php
/**
 * Copyright 2013 pixeltricks GmbH
 *
 * This file is part of SilverCart.
 *
 * SilverCart is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or * (at your option) any later version.
 *
 * SilverCart is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilverCart.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package SilvercartCustomerRebate
 * @subpackage Security
 */

/**
 * Extension for Group.
 * 
 * @package SilvercartCustomerRebate
 * @subpackage Security
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2013 pixeltricks GmbH
 * @since 17.07.2013
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartCustomerRebateGroup extends DataExtension {

    /**
     * has_many relations
     *
     * @return array
     */
    private static $has_many = array(
        'SilvercartCustomerRebates' => 'SilvercartCustomerRebate',
    );
    
    /**
     * Updates the cms fields.
     *
     * @param FieldList $fields The original FieldList
     *
     * @return FieldList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 07.07.2016
     */
    public function updateCMSFields(FieldList $fields) {
        $fields->addFieldToTab('Root.Members', new TextField('Code', _t('Group.CODE')));
        if ($this->owner->ID) {
            $silvercartCustomerRebatesField = new GridField(
                    'SilvercartCustomerRebates',
                    $this->owner->fieldLabel('SilvercartCustomerRebates'),
                    $this->owner->SilvercartCustomerRebates(),
                    SilvercartGridFieldConfig_RelationEditor::create()
            );
            $silvercartCustomerRebatesField->getConfig()->removeComponentsByType('GridFieldAddExistingAutocompleter');
            $fields->findOrMakeTab('Root.SilvercartCustomerRebates', $this->owner->fieldLabel('SilvercartCustomerRebates'));
            $fields->addFieldToTab('Root.SilvercartCustomerRebates', $silvercartCustomerRebatesField);
        }
    }
    
    /**
     * Updates the field labels.
     * 
     * @param array &$labels labels.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function updateFieldLabels(&$labels) {
        $labels['SilvercartCustomerRebates'] = _t('SilvercartCustomerRebate.PLURALNAME');
    }
    
    /**
     * Returns whether there is a current valid customer rebate.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function hasValidCustomerRebate() {
        $hasValidCustomerRebate = false;
        
        if ($this->getValidCustomerRebate() instanceof SilvercartCustomerRebate) {
            $hasValidCustomerRebate = true;
        }
        
        return $hasValidCustomerRebate;
    }
    
    /**
     * Returns the current valid customer rebate.
     * 
     * @return SilvercartCustomerRebate
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function getValidCustomerRebate() {
        $rebate = DataObject::get_one(
                'SilvercartCustomerRebate',
                sprintf(
                        '"GroupID" = %s AND "ValidFrom" < NOW() AND "ValidUntil" > NOW()',
                        $this->owner->ID
                )
        );
        return $rebate;
    }
    
}