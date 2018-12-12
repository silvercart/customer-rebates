<?php

namespace SilverCart\CustomerRebates\Model;

use SilverCart\Admin\Model\Config;
use SilverCart\Dev\Tools;
use SilverCart\Model\Product\Tax;
use SilverCart\Model\Customer\Customer;
use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\Security\Member;
use SilverStripe\View\ViewableData;

/**
 * Customer rebate ShoppingCartPosition model.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class ShoppingCartPosition extends ViewableData
{
    /**
     * Casted attributes.
     *
     * @var array
     */
    private static $casting = [
        'Title'               => 'HtmlText',
        'Name'                => 'HtmlText',
        'Quantity'            => 'Int',
        'Price'               => 'Money',
        'PriceTotal'          => 'Float',
        'PriceTotalFormatted' => 'Text',
        'TaxRate'             => 'Float',
    ];
    /**
     * price total.
     *
     * @var float
     */
    protected $priceTotal = null;
    /**
     * Rebate positions splitted by available tax rates.
     *
     * @var ArrayList
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
     * @return string
     */
    public function singular_name() : string
    {
        return Tools::singular_name_for($this);
    }

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     */
    public function plural_name() : string
    {
        return Tools::plural_name_for($this);
    }
    
    /**
     * Returns that this is a charge or discount position.
     * 
     * @return boolean
     */
    public function getIsChargeOrDiscount() : bool
    {
        return true;
    }
    
    /**
     * Returns that this is a productValue discount position.
     * 
     * @return string
     */
    public function getChargeOrDiscountModificationImpact() : string
    {
        return 'productValue';
    }

    /**
     * Returns the title.
     * 
     * @return DBHTMLText
     */
    public function getTitle() : DBHTMLText
    {
        $title  = "";
        $rebate = $this->getCustomerRebate();
        if (!is_null($rebate)) {
            $title = "<strong>{$this->getCustomerRebate()->singular_name()}:</strong> {$rebate->Title}";
            if ($this->isSplittedPosition) {
                $taxInfo = _t(CustomerRebate::class . '.TaxInfo', 'amount for positions with {taxrate}% VAT', [
                    'taxrate' => $this->getTaxRate(),
                ]);
                $title = "{$title} (<i>{$taxInfo}</i>)";
            }
            if ($this->getCustomerRebate()->getRelatedProductGroups()->Count() > 0) {
                $positions = $this->getCustomerRebate()->getRebatePositions();
                $nums = array();
                foreach ($positions as $position) {
                    $nums[] = $position->PositionNum;
                }
                $validForPositions = _t(CustomerRebate::class . '.ValidForPositions', 'Rebate is valid for position(s): {positionlist}', [
                    'positionlist' => implode(', ', $nums),
                ]);
                $title = "{$title}<br/><i> - {$validForPositions}</i>";
            }
        }
        return Tools::string2html($title);
    }
    
    /**
     * Returns the short description wihtout HTML.
     * 
     * @return string
     */
    public function getShortDescription() : string
    {
        return strip_tags($this->Title);
    }
    
    /**
     * Returns the Name.
     * 
     * @return DBHTMLText
     */
    public function getName() : DBHTMLText
    {
        return $this->getTitle();
    }
    
    /**
     * Returns the quantity.
     * 
     * @return int
     */
    public function getQuantity() : int
    {
        return 1;
    }
    
    /**
     * Returns the net price.
     * 
     * @return DBMoney
     */
    public function getPriceNet() : DBMoney
    {
        $price = DBMoney::create();
        $price->setAmount($this->getPriceNetTotal());
        $price->setCurrency(Config::DefaultCurrency());
        return $price;
    }
    
    /**
     * Returns the price.
     * 
     * @return DBMoney
     */
    public function getPrice() : DBMoney
    {
        $price = DBMoney::create();
        $price->setAmount($this->getPriceTotal());
        $price->setCurrency(Config::DefaultCurrency());
        return $price;
    }
    
    /**
     * Returns the currency.
     * 
     * @return string
     */
    public function getCurrency() : string
    {
        return Config::DefaultCurrency();
    }

    /**
     * Returns the total price.
     * 
     * @return float
     */
    public function getPriceTotal() : float
    {
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
    public function getPriceNetTotal() : float
    {
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
     * @return $this
     */
    public function setPriceTotal($priceTotal) : ShoppingCartPosition
    {
        $this->priceTotal = (float) $priceTotal;
        return $this;
    }

    /**
     * Returns the total price formatted.
     * 
     * @return string
     */
    public function getPriceTotalFormatted() : string
    {
        return $this->getPrice()->Nice();
    }

    /**
     * Returns the type safe quantity.
     * 
     * @return string
     */
    public function getTypeSafeQuantity() : string
    {
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
    public function getTaxAmount() : float
    {
        $taxRate = 0;
        if ($this->Tax instanceof Tax) {
            if (Config::PriceType() == 'gross') {
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
    public function getTaxRate()
    {
        return $this->Tax->Rate;
    }

    /**
     * Returns the customer rebate.
     * 
     * @return CustomerRebate
     */
    public function getCustomerRebate()
    {
        $rebate = null;
        if (Customer::currentUser() instanceof Member) {
            $rebate = Customer::currentUser()->getCustomerRebate();
        }
        return $rebate;
    }

    /**
     * Splits the rebates total price dependent on the available tax rates if
     * needed.
     * 
     * @param \SilverStripe\ORM\SS_List $taxRates Tax rates to split rebate for.
     * 
     * @return ArrayList
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.03.2014
     */
    public function splitForTaxRates($taxRates)
    {
        if (is_null($this->splittedPositions)) {
            $positions = ArrayList::create();
            if ($taxRates->count() == 1) {
                $positions->push($this);
            } elseif ($taxRates->find('Rate', $this->Tax->Rate)->AmountRaw + $this->getTaxAmount() >= 0) {
                $positions->push($this);
            } else {
                $rebate = $this->getCustomerRebate();
                if (!is_null($rebate)) {
                    $shoppingCartPositions = $rebate->getShoppingCart()->ShoppingCartPositions();
                    $rebate->doNotCallThisAsShoppingCartPlugin = false;
                    $amounts = [];
                    foreach ($shoppingCartPositions as $shoppingCartPosition) {
                        if ($shoppingCartPosition instanceof ShoppingCartPosition) {
                            continue;
                        }
                        $taxRate = $shoppingCartPosition->Product()->getTaxRate();

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

                        $rebatePosition                     = ShoppingCartPosition::create();
                        $rebatePosition->isSplittedPosition = true;
                        $rebatePosition->Tax                = Tax::get()->filter('Rate', $rate)->first();
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