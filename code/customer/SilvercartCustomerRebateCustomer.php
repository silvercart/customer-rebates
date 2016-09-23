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
class SilvercartCustomerRebateCustomer extends DataExtension {
    
    /**
     * Current customer rebate.
     *
     * @var SilvercartCustomerRebate
     */
    protected $customerRebate = null;
    
    /**
     * indicator to prevent the module from loading.
     *
     * @var bool
     */
    protected $doNotCallThisAsShoppingCartPlugin = false;
    
    /**
     * Returns whether there is a current rebate.
     * 
     * @return boolean
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function hasCustomerRebate() {
        $hasCustomerRebate = false;
        
        if ($this->getCustomerRebate() instanceof SilvercartCustomerRebate) {
            $hasCustomerRebate = true;
        }
        
        return $hasCustomerRebate;
    }
    
    /**
     * Returns the current highest valid customer rebate.
     * 
     * @return SilvercartCustomerRebate
     */
    public function getCustomerRebate() {
        if (is_null($this->customerRebate)) {
            $rebate = null;
            if (!$this->doNotCallThisAsShoppingCartPlugin) {
                $groups = $this->owner->Groups();
                foreach ($groups as $group) {
                    if ($group->hasValidCustomerRebate()) {
                        $validRebate = $group->getValidCustomerRebate();
                        if (is_null($rebate) ||
                            $validRebate->getRebateValueForShoppingCart() > $rebate->getRebateValueForShoppingCart()) {
                            $rebate = $validRebate;
                        }
                    }
                }
                $rebate = $this->checkRebateConditions($rebate);
            }
            $this->customerRebate = $rebate;
        }
        return $this->customerRebate;
    }
    
    /**
     * Checks the conditions for the given rebate.
     * If the conditions are not fulfilled, the rebate will be set to NULL.
     * 
     * @param SilvercartCustomerRebate $rebate Rebate to check conditions for.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 10.03.2014
     */
    protected function checkRebateConditions($rebate) {
        if ($rebate instanceof SilvercartCustomerRebate) {
            $this->doNotCallThisAsShoppingCartPlugin = true;
            $cart  = $this->owner->getCart();
            $total = $cart->getAmountTotalWithoutFees(array('SilvercartCustomerRebate'));
            if ($total->getAmount() < $rebate->MinimumOrderValue->getAmount()) {
                // Rebate has a minimum order value higher than the 
                // shopping cart total amount.
                $rebate = null;
            } elseif ($rebate->RestrictToNewsletterRecipients &&
                      !$this->owner->SubscribedToNewsletter) {
                // Rebate is restricted to newsletter recipients but 
                // the customer did not subscribe to newsletter.
                $rebate = null;
            } elseif ($rebate->getRelatedProductGroups()->Count() > 0 &&
                      $rebate->getRebatePositions()->Count() == 0) {
                $rebate = null;
            }
            $this->doNotCallThisAsShoppingCartPlugin = false;
        }
        return $rebate;
    }
    
}