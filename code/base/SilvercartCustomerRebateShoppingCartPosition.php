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
 * SilvercartCustomerRebateShoppingCartPosition.
 * 
 * @package SilvercartCustomerRebate
 * @subpackage Base
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2013 pixeltricks GmbH
 * @since 17.07.2013
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class SilvercartCustomerRebateShoppingCartPosition extends DataObject {

    /**
     * Casted attributes.
     *
     * @var array
     */
    public static $casting = array(
        'Title'                 => 'HtmlText',
        'Name'                  => 'HtmlText',
        'Quantity'              => 'Int',
        'Price'                 => 'Money',
        'PriceTotal'            => 'Float',
        'PriceTotalFormatted'   => 'Text',
        'TaxRate'               => 'Float',
    );
    
    /**
     * price total.
     *
     * @var float
     */
    protected $priceTotal = null;

    /**
     * Rebate positions splitted by available tax rates.
     *
     * @var DataObjectSet
     */
    protected $splittedPositions = null;
    
    /**
     * Determines whether this is a splitted position.
     *
     * @var bool
     */
    public $isSplittedPosition = false;

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
     * Returns that this is a charge or discount position.
     * 
     * @return boolean
     */
    public function getIsChargeOrDiscount() {
        return true;
    }
    
    /**
     * Returns that this is a productValue discount position.
     * 
     * @return boolean
     */
    public function getChargeOrDiscountModificationImpact() {
        return 'productValue';
    }

    /**
     * Returns the title.
     * 
     * @return string
     */
    public function getTitle() {
        $title  = '';
        $rebate = $this->getCustomerRebate();
        if (!is_null($rebate)) {
            $title = '<strong>' . $this->getCustomerRebate()->singular_name() . ':</strong> ' . $rebate->Title;
            if ($this->isSplittedPosition) {
                $title .= ' (<i>' . sprintf(_t('SilvercartCustomerRebate.TaxInfo'), $this->getTaxRate()) . '</i>)';
            }
            if ($this->getCustomerRebate()->SilvercartProductGroups()->Count() > 0) {
                $positions = $this->getCustomerRebate()->getRebatePositions();
                $nums = array();
                foreach ($positions as $position) {
                    $nums[] = $position->PositionNum;
                }
                $title .= '<br/><i> - ' . sprintf(_t('SilvercartCustomerRebate.ValidForPositions'), implode(', ', $nums)) . '</i>';
            }
        }
        return $title;
    }
    
    /**
     * Returns the short description wihtout HTML.
     * 
     * @return string
     */
    public function getShortDescription() {
        return strip_tags($this->Title);
    }
    
    /**
     * Returns the Name.
     * 
     * @return string
     */
    public function getName() {
        return $this->getTitle();
    }
    
    /**
     * Returns the quantity.
     * 
     * @return int
     */
    public function getQuantity() {
        return 1;
    }
    
    /**
     * Returns the net price.
     * 
     * @return Money
     */
    public function getPriceNet() {
        $price = new Money();
        $price->setAmount($this->getPriceNetTotal());
        $price->setCurrency(SilvercartConfig::DefaultCurrency());
        return $price;
    }
    
    /**
     * Returns the price.
     * 
     * @return Money
     */
    public function getPrice() {
        $price = new Money();
        $price->setAmount($this->getPriceTotal());
        $price->setCurrency(SilvercartConfig::DefaultCurrency());
        return $price;
    }
    
    /**
     * Returns the currency.
     * 
     * @return string
     */
    public function getCurrency() {
        return SilvercartConfig::DefaultCurrency();
    }

    /**
     * Returns the total price.
     * 
     * @return float
     */
    public function getPriceTotal() {
        if (is_null($this->priceTotal)) {
            $priceTotal = 0;
            $rebate     = $this->getCustomerRebate();
            if (!is_null($rebate)) {
                $priceTotal = $rebate->getRebateValueForShoppingCart() * -1;
            }
            $this->setPriceTotal(round($priceTotal, 2));
        }
        return $this->priceTotal;
    }

    /**
     * Returns the total price.
     * 
     * @return float
     */
    public function getPriceNetTotal() {
        if (is_null($this->priceNetTotal)) {
            $priceTotal = $this->getPriceTotal();
            $priceNetTotal = $priceTotal - $this->getTaxAmount();
            $this->priceNetTotal = round($priceNetTotal,2);
        }
        return $this->priceNetTotal;
    }
    
    /**
     * Sets the total price.
     * 
     * @param float $priceTotal Price to set.
     * 
     * @return void
     */
    public function setPriceTotal($priceTotal) {
        $this->priceTotal   = (float) $priceTotal;
    }

    /**
     * Returns the total price formatted.
     * 
     * @return string
     */
    public function getPriceTotalFormatted() {
        return $this->getPrice()->Nice();
    }

    /**
     * Returns the type safe quantity.
     * 
     * @return string
     */
    public function getTypeSafeQuantity() {
        return '';
    }
    
    /**
     * returns the tax amount included in $this
     * 
     * @return float
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 18.07.2013
     */
    public function getTaxAmount() {
        $taxRate = 0;
        if ($this->Tax instanceof SilvercartTax) {
            if (SilvercartConfig::PriceType() == 'gross') {
                $taxRate = $this->getPrice()->getAmount() -
                           ($this->getPrice()->getAmount() /
                            (100 + $this->Tax->Rate) * 100); 
            } else {
                $taxRate = $this->getPrice()->getAmount() *
                           ($this->Tax->Rate / 100);
            }
        }
        return $taxRate;
    }
    
    /**
     * Returns the tax rate
     * 
     * @return int
     */
    public function getTaxRate() {
        return $this->Tax->Rate;
    }

    /**
     * Returns the customer rebate.
     * 
     * @return SilvercartCustomerRebate
     */
    public function getCustomerRebate() {
        $rebate = null;
        if (Member::currentUser() instanceof Member) {
             $currentLocale          = i18n::get_locale();
            // $rebateLocale =  SilvercartCustomerRebate::getLanguage();
           // var_dump($rebateLocale); exit();
            if ($currentLocale == 'de_DE'){
            $rebate = Member::currentUser()->getCustomerRebate();
           //  var_dump($rebate);exit();
            }
        }
        return $rebate;
    }

    /**
     * Splits the rebates total price dependent on the available tax rates if
     * needed.
     * 
     * @param DataObjectSet $taxRates Tax rates to split rebate for.
     * 
     * @return DataObjectSet
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.03.2014
     */
    public function splitForTaxRates(DataObjectSet $taxRates) {
        if (is_null($this->splittedPositions)) {

            $positions = new DataObjectSet();
            if ($taxRates->Count() == 1) {
                $positions->push($this);
            } elseif ($taxRates->find('Rate', $this->Tax->Rate)->AmountRaw + $this->getTaxAmount() >= 0) {
                $positions->push($this);
            } else {

                $rebate = $this->getCustomerRebate();
                if (!is_null($rebate)) {
                    $shoppingCartPositions = $rebate->getShoppingCart()->SilvercartShoppingCartPositions();
                    $rebate->doNotCallThisAsShoppingCartPlugin = false;

                    $amounts = array();

                    foreach ($shoppingCartPositions as $shoppingCartPosition) {
                        if ($shoppingCartPosition instanceof SilvercartCustomerRebateShoppingCartPosition) {
                            continue;
                        }
                        $taxRate = $shoppingCartPosition->SilvercartProduct()->getTaxRate();

                        if (!array_key_exists($taxRate, $amounts)) {
                            $amounts[$taxRate] = 0;
                        }
                        $amounts[$taxRate] += $shoppingCartPosition->getPrice()->getAmount();
                    }

                    arsort($amounts);

                    $rebatePrice = $this->getPriceTotal();
                    
                    foreach ($amounts as $rate => $amount) {
                        if ($rebatePrice == 0) {
                            break;
                        }
                        if ($amount + $rebatePrice < 0) {
                            $priceTotal     = $amount * -1;
                            $rebatePrice    = $amount + $rebatePrice;
                        } else {
                            $priceTotal     = $rebatePrice;
                            $rebatePrice    = 0;
                        }

                        $rebatePosition                     = new SilvercartCustomerRebateShoppingCartPosition();
                        $rebatePosition->isSplittedPosition = true;
                        $rebatePosition->Tax                = DataObject::get_one(
                                'SilvercartTax',
                                sprintf(
                                        "Rate = %f",
                                        $rate
                                )
                        );
                        $rebatePosition->setPriceTotal($priceTotal);
                        $positions->push($rebatePosition);
                    }
                }
            }
            $this->splittedPositions = $positions;
        }
        return $this->splittedPositions;
            
    }
    
}