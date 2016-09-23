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
    private static $db = array(
        'ValidFrom'                      => 'Date',
        'ValidUntil'                     => 'Date',
        'Type'                           => "enum('absolute,percent','absolute')",
        'Value'                          => 'Float',
        'MinimumOrderValue'              => 'Money',
        'RestrictToNewsletterRecipients' => 'Boolean',
    );
    
    /**
     * Has one relations.
     *
     * @var array
     */
    private static $has_one = array(
        'Group' => 'Group',
    );
    
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_many = array(
        'SilvercartCustomerRebateLanguages' => 'SilvercartCustomerRebateLanguage',
    );

    /**
     * Many many relations.
     *
     * @var array
     */
    private static $many_many = array(
        'SilvercartProductGroups' => 'SilvercartProductGroupPage'
    );
    
    /**
     * Casted attributes.
     *
     * @var array
     */
    private static $casting = array(
        'Title'                 => 'Text',
        'MinimumOrderValueNice' => 'Text',
    );
    
    /**
     * Default sort.
     *
     * @var string
     */
    private static $default_sort = 'ValidFrom DESC';

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
     * @var ArrayList
     */
    protected $rebatePositions = null;
    
    /**
     * The related product groups.
     *
     * @var ArrayList
     */
    protected $relatedProductGroups = null;

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
     * @since 10.03.2014
     */
    public function fieldLabels($includerelations = true) {
        $fieldLabels = array_merge(
                parent::fieldLabels($includerelations),
                array(
                    'ValidFrom'                         => _t('SilvercartCustomerRebate.ValidFrom'),
                    'ValidUntil'                        => _t('SilvercartCustomerRebate.ValidUntil'),
                    'Type'                              => _t('SilvercartCustomerRebate.Type'),
                    'TypeAbsolute'                      => _t('SilvercartCustomerRebate.TypeAbsolute'),
                    'TypePercent'                       => _t('SilvercartCustomerRebate.TypePercent'),
                    'Value'                             => _t('SilvercartCustomerRebate.Value'),
                    'Title'                             => _t('SilvercartCustomerRebate.Title'),
                    'MinimumOrderValue'                 => _t('SilvercartCustomerRebate.MinimumOrderValue'),
                    'RestrictToNewsletterRecipients'    => _t('SilvercartCustomerRebate.RestrictToNewsletterRecipients'),
                    'Group'                             => _t('Group.SINGULARNAME'),
                    'SilvercartCustomerRebateLanguages' => _t('SilvercartCustomerRebateLanguage.PLURALNAME'),
                    'SilvercartProductGroups'           => _t('SilvercartCustomerRebate.SilvercartProductGroups'),
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
     * @since 10.03.2014
     */
    public function summaryFields() {
        $summaryFields = array(
            'Title'                 => $this->fieldLabel('Title'),
            'ValidFrom'             => $this->fieldLabel('ValidFrom'),
            'ValidUntil'            => $this->fieldLabel('ValidUntil'),
            'MinimumOrderValueNice' => $this->fieldLabel('MinimumOrderValue'),
            'Type'                  => $this->fieldLabel('Type'),
            'Value'                 => $this->fieldLabel('Value'),
        );
        
        $this->extend('updateSummaryFields', $summaryFields);
        
        return $summaryFields;
    }
    
    /**
     * Scaffold a simple edit form for all properties on this dataobject,
     * based on default {@link FormField} mapping in {@link DBField::scaffoldFormField()}.
     * Field labels/titles will be auto generated from {@link DataObject::fieldLabels()}.
     *
     * @uses FormScaffolder
     *
     * @param array $_params Associative array passing through properties to {@link FormScaffolder}.
     * 
     * @return FieldList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 23.09.2016
     */
    public function scaffoldFormFields($_params = null) {
        parent::scaffoldFormFields(array_merge(
                (array)$_params,
                array(
                    'fieldClasses' => array(
                        'MinimumOrderValue' => 'SilvercartMoneyField',
                    ),
                )
        ));
    }
    
    /**
     * The cms fields.
     * 
     * @param array $params Params for scaffolding
     * 
     * @return FieldList
     */
    public function getCMSFields() {
        $fields = parent::getCMSFields();
        
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
        
        $productGroupHolder = SilvercartTools::PageByIdentifierCode('SilvercartProductGroupHolder');
        $productGroupsField = new TreeMultiselectField(
                'SilvercartProductGroups',
                $this->fieldLabel('SilvercartProductGroups'),
                'SiteTree'
        );
        $productGroupsField->setTreeBaseID($productGroupHolder->ID);

        $fields->addFieldToTab('Root.Main', $productGroupsField);
        
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
     * Returns the MinimumOrderValue in a nice format.
     *
     * @return string
     */
    public function getMinimumOrderValueNice() {
        return $this->MinimumOrderValue->Nice();
    }
    
    /**
     * Returns the related product groups or its translations.
     * 
     * @return SS_List
     */
    public function getRelatedProductGroups() {
        if ($this->SilvercartProductGroups()->Count() == 0) {
            // Workaround to match translations of the phisical related product groups.
            $query1 = 'SELECT "SCRSPG"."SilvercartProductGroupPageID" FROM "SilvercartCustomerRebate_SilvercartProductGroups" AS "SCRSPG" WHERE "SCRSPG"."SilvercartCustomerRebateID" = \'' . $this->ID . '\'';
            $query2 = 'SELECT "STTG2"."TranslationGroupID" FROM "SiteTree_translationgroups" AS "STTG2" WHERE "STTG2"."OriginalID" IN (' . $query1 . ')';
            $query3 = 'SELECT "STTG"."OriginalID" FROM "SiteTree_translationgroups" AS "STTG" WHERE "STTG"."OriginalID" NOT IN (' . $query1 . ') AND "STTG"."TranslationGroupID" IN (' . $query2 . ')';
            $this->relatedProductGroups = SilvercartProductGroupPage::get()->where('"SiteTree"."ID" IN (' . $query3 . ')');
            if (!($this->relatedProductGroups instanceof DataList)) {
                $this->relatedProductGroups = new ArrayList();
            }
        } else {
            $this->relatedProductGroups = $this->SilvercartProductGroups();
        }
        return $this->relatedProductGroups;
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
                    if ($this->getRelatedProductGroups()->Count() == 0) {
                        // get rebate value from total amount
                        $total = $cart->getAmountTotalWithoutFees();
                        if ($this->Type == 'absolute') {
                            $value = $this->Value;
                        } else {
                            $value = ($total->getAmount() / 100) * $this->Value;
                        }
                        if ($total->getAmount() < $value) {
                            $value = $total->getAmount();
                        }
                    } else {
                        // get rebate value from single positions.
                        $value = $this->getRebateValueForShoppingCartPositions();
                    }
                    $this->doNotCallThisAsShoppingCartPlugin = false;
                }
            }
        }
        return $value;
    }
    
    /**
     * Returns the rebate value for shopping cart positions.
     * 
     * @return float
     */
    protected function getRebateValueForShoppingCartPositions() {
        $value       = 0;
        $totalAmount = 0;

        if ($this->Type == 'absolute') {
            $value = $this->Value;
        }
        
        foreach ($this->getRebatePositions() as $position) {
            $totalAmount += $position->getPrice()->getAmount();
            if ($this->Type == 'percent') {
                $value += ($position->getPrice()->getAmount() / 100) * $this->Value;
            }
        }
        
        if ($totalAmount < $value) {
            $value = $totalAmount;
        }
        
        return $value;
    }
    
    /**
     * Returns the positions to rebate.
     * 
     * @return ArrayList
     */
    public function getRebatePositions() {
        $rebatePositions    = new ArrayList();
        $cart               = Member::currentUser()->SilvercartShoppingCart();
        $validProductGroups = $this->getRelatedProductGroups()->map();
        $positionNum        = 1;
        foreach ($cart->SilvercartShoppingCartPositions() as $position) {
            if ($position instanceof SilvercartCustomerRebateShoppingCartPosition) {
                $positionNum++;
                continue;
            }
            $product = $position->SilvercartProduct();
            $position->PositionNum = $positionNum;
            if (array_key_exists($product->SilvercartProductGroupID, $validProductGroups)) {
                $rebatePositions->push($position);
            } elseif ($product->SilvercartProductGroupMirrorPages()->Count() > 0) {
                $mirrorProductGroupIDs = array_keys($product->SilvercartProductGroupMirrorPages()->map());
                foreach ($mirrorProductGroupIDs as $mirrorProductGroupID) {
                    if (array_key_exists($mirrorProductGroupID, $validProductGroups)) {
                        $rebatePositions->push($position);
                        break;
                    }
                }
            }
            $positionNum++;
        }
        
        return $rebatePositions;
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
     * @since 29.10.2013
     */
    public function loadObjectForShoppingCart(SilvercartShoppingCart $silvercartShoppingCart) {
        $object = null;
        if (!$this->doNotCallThisAsShoppingCartPlugin &&
            Member::currentUser() instanceof Member) {
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
     * @return ArrayList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function ShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $rebatePositions = new ArrayList();
        
        if (!$this->doNotCallThisAsShoppingCartPlugin &&
            $taxable &&
            $this->getShoppingCart()->SilvercartShoppingCartPositions()->Count() > 0 &&
            Member::currentUser()->hasCustomerRebate()) {
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
     * @return SS_List
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function TaxableShoppingCartPositions(SilvercartShoppingCart $silvercartShoppingCart, Member $member) {
        $positions = $this->ShoppingCartPositions($silvercartShoppingCart, $member, true);

        return $positions;
    }
}