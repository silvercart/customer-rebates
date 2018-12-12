<?php

namespace SilverCart\CustomerRebates\Extensions;

use SilverCart\Admin\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverCart\CustomerRebates\Model\CustomerRebate;
use SilverCart\Forms\FormFields\TextField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Security\Group;

/**
 * Extension for the SilverStripe Group.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class GroupExtension extends DataExtension
{
    /**
     * has_many relations
     *
     * @return array
     */
    private static $has_many = [
        'CustomerRebates' => CustomerRebate::class,
    ];
    
    /**
     * Updates the cms fields.
     *
     * @param FieldList $fields The original FieldList
     *
     * @return FieldList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.12.2018
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldToTab('Root.Members', TextField::create('Code', Group::singleton()->fieldLabel('Code')));
        if ($this->owner->exists()) {
            $customerRebatesField = GridField::create(
                    'CustomerRebates',
                    $this->owner->fieldLabel('CustomerRebates'),
                    $this->owner->CustomerRebates(),
                    GridFieldConfig_RelationEditor::create()
            );
            $customerRebatesField->getConfig()->removeComponentsByType(GridFieldAddExistingAutocompleter::class);
            $fields->findOrMakeTab('Root.CustomerRebates', $this->owner->fieldLabel('CustomerRebates'));
            $fields->addFieldToTab('Root.CustomerRebates', $customerRebatesField);
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
     * @since 12.12.2018
     */
    public function updateFieldLabels(&$labels)
    {
        $labels = array_merge($labels, [
            'CustomerRebates' => CustomerRebate::singleton()->plural_name(),
        ]);
    }
    
    /**
     * Returns whether there is a current valid customer rebate.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.12.2018
     */
    public function hasValidCustomerRebate()
    {
        $hasValidCustomerRebate = false;
        if ($this->getValidCustomerRebate() instanceof CustomerRebate) {
            $hasValidCustomerRebate = true;
        }
        return $hasValidCustomerRebate;
    }
    
    /**
     * Returns the current valid customer rebate.
     * 
     * @return CustomerRebate
     */
    public function getValidCustomerRebate()
    {
        return CustomerRebate::get()
                ->filter('GroupID', $this->owner->ID)
                ->where("ValidFrom < NOW() AND ValidUntil > NOW()")
                ->first();
    }
}