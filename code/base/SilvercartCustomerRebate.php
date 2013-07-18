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
 * @subpackage Base
 */

/**
 * SilvercartCustomerRebate.
 * 
 * @package SilvercartCustomerRebate
 * @subpackage Base
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2013 pixeltricks GmbH
 * @since 17.07.2013
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartCustomerRebate extends DataObject {
    
    /**
     * DB attributes.
     *
     * @var array
     */
    public static $db = array(
        'ValidFrom'     => 'Date',
        'ValidUntil'    => 'Date',
        'Type'          => "enum('absolute,percent','absolute')",
        'Value'         => 'Float',
    );
    
    /**
     * Has one relations.
     *
     * @var array
     */
    public static $has_one = array(
        'Group' => 'Group',
    );
    
    /**
     * Has many relations.
     *
     * @var array
     */
    public static $has_many = array(
        'SilvercartCustomerRebateLanguages' => 'SilvercartCustomerRebateLanguage',
    );
    
    /**
     * Casted attributes.
     *
     * @var array
     */
    public static $casting = array(
        'Title' => 'Text',
    );
    
    /**
     * Default sort.
     *
     * @var string
     */
    public static $default_sort = 'ValidFrom DESC';

    /**
     * indicator to prevent the module from loading.
     *
     * @var bool
     */
    public $doNotCallThisAsShoppingCartPlugin = false;

    /**
     * The shopping cart.
     *
     * @var SilvercartShoppingCart
     */
    protected $shoppingCart = null;
    
    /**
     * The rebate positions.
     *
     * @var DataObjectSet
     */
    protected $rebatePositions = null;

    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string The objects singular name 
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function singular_name() {
        return SilvercartTools::singular_name_for($this);
    }


    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string the objects plural name
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function plural_name() {
        return SilvercartTools::plural_name_for($this);
    }

    /**
     * Field labels.
     * 
     * @param bool $includerelations Include relations?
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'ValidFrom'     => _t('SilvercartCustomerRebate.ValidFrom'),
                    'ValidUntil'    => _t('SilvercartCustomerRebate.ValidUntil'),
                    'Type'          => _t('SilvercartCustomerRebate.Type'),
                    'TypeAbsolute'  => _t('SilvercartCustomerRebate.TypeAbsolute'),
                    'TypePercent'   => _t('SilvercartCustomerRebate.TypePercent'),
                    'Value'         => _t('SilvercartCustomerRebate.Value'),
                    'Title'         => _t('SilvercartCustomerRebate.Title'),
                )
        );
        
        $this->extend('updateFieldLabels', $fieldLabels);
        
        return $fieldLabels;
    }
    
    /**
     * Summary fields.
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function summaryFields() {
        $summaryFields = array(
            'Title'         => $this->fieldLabel('Title'),
            'ValidFrom'     => $this->fieldLabel('ValidFrom'),
            'ValidUntil'    => $this->fieldLabel('ValidUntil'),
            'Type'          => $this->fieldLabel('Type'),
            'Value'         => $this->fieldLabel('Value'),
        );
        
        $this->extend('updateSummaryFields', $summaryFields);
        
        return $summaryFields;
    }
    
    /**
     * The cms fields.
     * 
     * @param array $params Params for scaffolding
     * 
     * @return FieldSet
     */
    public function getCMSFields($params = null) {
        $fields = parent::getCMSFields($params);
        
        $languageFields = SilvercartLanguageHelper::prepareCMSFields($this->getLanguage(true));
        foreach ($languageFields as $languageField) {
            $fields->insertBefore($languageField, 'ValidFrom');
        }
        
        $validFromField     = $fields->dataFieldByName('ValidFrom');
        $validUntilField    = $fields->dataFieldByName('ValidUntil');
        $typeField          = $fields->dataFieldByName('Type');
        
        $validFromField->setConfig('showcalendar', true);
        $validUntilField->setConfig('showcalendar', true);
        
        
        $typeFieldValues = array(
            'absolute'  => $this->fieldLabel('TypeAbsolute'),
            'percent'   => $this->fieldLabel('TypePercent'),
        );
        $typeField->setSource($typeFieldValues);
        
        return $fields;
    }
    
    /**
     * getter for the pseudo attribute title
     *
     * @return string the title in the corresponding frontend language 
     */
    public function getTitle() {
        return $this->getLanguageFieldValue('Title');
    }
    
    /**
     * Returns the rebate value for the current shopping cart.
     * 
     * @return float
     */
    public function getRebateValueForShoppingCart() {
        $value = 0;
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            if (Member::currentUser() instanceof Member) {
                $this->doNotCallThisAsShoppingCartPlugin = true;
                $cart = Member::currentUser()->SilvercartShoppingCart();
                if ($cart instanceof SilvercartShoppingCart) {
                    $total = $cart->getAmountTotalWithoutFees();
                    if ($this->Type == 'absolute') {
                        $value = $this->Value;
                    } else {
                        $value = ($total->getAmount() / 100) * $this->Value;
                    }
                    if ($total->getAmount() < $value) {
                        $value = $total->getAmount();
                    }
                    $this->doNotCallThisAsShoppingCartPlugin = false;
                }
            }
        }
        return $value;
    }
    
    /**
     * Returns the current shopping cart.
     * 
     * @return SilvercartShoppingCart
     */
    public function getShoppingCart() {
        $this->doNotCallThisAsShoppingCartPlugin = true;
        if (is_null($this->shoppingCart)) {
            $this->shoppingCart = Member::currentUser()->SilvercartShoppingCart();
        }
        return $this->shoppingCart;
    }

    /**
     * Returns an instance of a silvercart customer rebate object for the given
     * shopping cart.
     *
     * @param SilvercartShoppingcart $silvercartShoppingCart The shopping cart object
     *
     * @return SilvercartCustomerRebate
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function loadObjectForShoppingCart(SilvercartShoppingCart $silvercartShoppingCart) {
        $object = null;
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            $object = Member::currentUser()->getCustomerRebate();
        }
        return $object;
    }

    /**
     * Hook for the init method of the shopping cart.
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function ShoppingCartInit() {
        $result = null;
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            $controller = Controller::curr();
            // Don't initialise when called from within the cms
            if (!$controller->isFrontendPage) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the rebate is allowed to be placed in the cart.
     *
     * @param ShoppingCart $silvercartShoppingCart       the shopping cart to check against
     * @param Member       $member                       the shopping cart to check against
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function performShoppingCartConditionsCheck(SilvercartShoppingCart $silvercartShoppingCart, $member, $excludeShoppingCartPositions = false) {
        $result = false;
        
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            if ($this->getShoppingCart()->SilvercartShoppingCartPositions()->Count() > 0) {
                $this->doNotCallThisAsShoppingCartPlugin = false;
                if (Member::currentUser() instanceof Member) {
                    $result = Member::currentUser()->hasCustomerRebate();
                }
            }
            $this->doNotCallThisAsShoppingCartPlugin = false;
        }
        
        return $result;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @param ShoppingCart $silvercartShoppingCart       The shoppingcart object
     * @param Member       $member                       The customer object
     * @param Bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted; can contain the ID or the className of the position
     * @param Bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return DataObjectSet
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function ShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $rebatePositions = new DataObjectSet();
        
        if (!$this->doNotCallThisAsShoppingCartPlugin && $taxable && $this->getShoppingCart()->SilvercartShoppingCartPositions()->Count() > 0) {
            if (is_null($this->rebatePositions)) {
                $taxRates           = $this->getShoppingCart()->getTaxRatesWithoutFeesAndCharges();
                $mostValuableRate   = $this->getShoppingCart()->getMostValuableTaxRate($taxRates);
                $this->doNotCallThisAsShoppingCartPlugin = false;

                $position               = new SilvercartCustomerRebateShoppingCartPosition();
                $position->Tax          = $mostValuableRate;
                $this->rebatePositions  = $position->splitForTaxRates($taxRates);
            }
            $this->doNotCallThisAsShoppingCartPlugin = false;
            $rebatePositions = $this->rebatePositions;
        }
        
        return $rebatePositions;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns taxable entries for the cart listing.
     *
     * @param SilvercartShoppingCart $silvercartShoppingCart The Silvercart shoppingcart object
     * @param Member                 $member                 The member object
     *
     * @return DataObjectSet
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function TaxableShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $positions = $this->ShoppingCartPositions($silvercartShoppingCart, $member, true);

        return $positions;
    }
}