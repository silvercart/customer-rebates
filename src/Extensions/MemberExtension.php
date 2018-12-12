<?php

namespace SilverCart\CustomerRebates\Extensions;

use SilverCart\CustomerRebates\Model\CustomerRebate;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for the SilverStripe Member.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
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
     * indicator to prevent the module from loading.
     *
     * @var bool
     */
    protected static $doNotCallThisAsShoppingCartPlugin = false;
    
    /**
     * Returns whether there is a current rebate.
     * 
     * @return boolean
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function hasCustomerRebate()
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
     * @return CustomerRebate
     */
    public function getCustomerRebate()
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
     * Checks the conditions for the given rebate.
     * If the conditions are not fulfilled, the rebate will be set to NULL.
     * 
     * @param CustomerRebate $rebate Rebate to check conditions for.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.03.2014
     */
    protected function checkRebateConditions($rebate) {
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