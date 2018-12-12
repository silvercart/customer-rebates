<?php

use SilverCart\CustomerRebates\Model\CustomerRebate;
use SilverCart\Model\Order\ShoppingCart;

ShoppingCart::registerModule(CustomerRebate::class);