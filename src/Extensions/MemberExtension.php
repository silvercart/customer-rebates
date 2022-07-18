<?php

namespace SilverCart\CustomerRebates\Extensions;

use SilverCart\CustomerRebates\Model\CustomerRebate;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\ArrayList;

/**
 * Extension for the SilverStripe Member.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * 
 * @property \SilverStripe\Security\Member $owner Owner
 */
class MemberExtension extends DataExtension
{
    /**
     * Current customer rebate.
     *
     * @var CustomerRebate[]
     */
    protected $customerRebate = [];
    /**
     * List of valid customer rebates.
     *
     * @var ArrayList[]
     */
    protected $customerRebates = [];

    /**
     * indicator to prevent the module from loading.
     *
     * @var bool
     */
    protected static $doNotCallThisAsShoppingCartPlugin = false;
    
    /**
     * Returns whether there is a current rebate.
     * 
     * @return bool
     */
    public function hasCustomerRebate() : bool
    {
        $hasCustomerRebate = false;
        if ($this->getCustomerRebate() instanceof CustomerRebate) {
            $hasCustomerRebate = true;
        }
        return $hasCustomerRebate;
    }
    
    /**
     * Returns the current highest valid customer rebate.
     * 
     * @return CustomerRebate|null
     */
    public function getCustomerRebate() : ?CustomerRebate
    {
        if (!array_key_exists($this->owner->ID, $this->customerRebate)) {
            $rebate = null;
            if (!self::$doNotCallThisAsShoppingCartPlugin) {
                $groups = $this->owner->Groups();
                foreach ($groups as $group) {
                    if ($group->hasValidCustomerRebate()) {
                        $validRebate = $group->getValidCustomerRebate();
                        if (is_null($rebate)
                         || $validRebate->getRebateValueForShoppingCart() > $rebate->getRebateValueForShoppingCart()
                        ) {
                            $rebate = $validRebate;
                        }
                    }
                }
                $rebate = $this->checkRebateConditions($rebate);
            }
            $this->customerRebate[$this->owner->ID] = $rebate;
        }
        return $this->customerRebate[$this->owner->ID];
    }
    
    /**
     * Returns the list of valid customer rebates.
     * 
     * @return ArrayList
     */
    public function getCustomerRebates() : ArrayList
    {
        if (!array_key_exists($this->owner->ID, $this->customerRebates)) {
            $rebates = ArrayList::create();
            if (!self::$doNotCallThisAsShoppingCartPlugin) {
                $groups = $this->owner->Groups();
                foreach ($groups as $group) {
                    if ($group->hasValidCustomerRebate()) {
                        $rebate = $this->checkRebateConditions($group->getValidCustomerRebate());
                        if (!is_null($rebate)) {
                            $rebates->push($rebate);
                        }
                    }
                }
            }
            $this->customerRebates[$this->owner->ID] = $rebates;
        }
        return $this->customerRebates[$this->owner->ID];
    }

    /**
     * Checks the conditions for the given rebate.
     * If the conditions are not fulfilled, the rebate will be set to NULL.
     * 
     * @param CustomerRebate $rebate Rebate to check conditions for.
     * 
     * @return CustomerRebate|null
     */
    protected function checkRebateConditions($rebate) : ?CustomerRebate
    {
        if ($rebate instanceof CustomerRebate) {
            self::$doNotCallThisAsShoppingCartPlugin = true;
            $cart  = $this->owner->getCart();
            $total = $cart->getAmountTotalWithoutFees([CustomerRebate::class]);
            if ($total->getAmount() < $rebate->MinimumOrderValue->getAmount()) {
                // Rebate has a minimum order value higher than the 
                // shopping cart total amount.
                $rebate = null;
            } elseif ($rebate->RestrictToNewsletterRecipients
                   && !$this->owner->SubscribedToNewsletter
            ) {
                // Rebate is restricted to newsletter recipients but 
                // the customer did not subscribe to newsletter.
                $rebate = null;
            } elseif ($rebate->RestrictToFirstOrder
                   && !$this->owner->Orders()->exists()
            ) {
                // Rebate is restricted to the first order but the customer 
                // already placed an order.
                $rebate = null;
            } elseif ($rebate->getRelatedProductGroups()->Count() > 0
                   && $rebate->getRebatePositions()->Count() == 0
            ) {
                $rebate = null;
            }
            self::$doNotCallThisAsShoppingCartPlugin = false;
        }
        return $rebate;
    }
}