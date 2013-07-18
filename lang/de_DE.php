<?php
/**
 * Copyright 2013 pixeltricks GmbH
 *
 * This file is part of SilvercartPrepaymentPayment.
 *
 * SilvercartPaypalPayment is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * SilvercartPrepaymentPayment is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with SilvercartPrepaymentPayment.  If not, see <http://www.gnu.org/licenses/>.
 *
 * German (Germany) language pack
 *
 * @package SilvercartCustomerRebate
 * @subpackage i18n
 * @ignore
 */
i18n::include_locale_file('silvercart_customer_rebate', 'en_US');

global $lang;

if (array_key_exists('de_DE', $lang) && is_array($lang['de_DE'])) {
    $lang['de_DE'] = array_merge($lang['en_US'], $lang['de_DE']);
} else {
    $lang['de_DE'] = $lang['en_US'];
}

$lang['de_DE']['SilvercartCustomerRebate']['PLURALNAME']                        = 'Rabatte';
$lang['de_DE']['SilvercartCustomerRebate']['SINGULARNAME']                      = 'Rabatt';

$lang['de_DE']['SilvercartCustomerRebate']['ValidFrom']                         = 'Gültig ab';
$lang['de_DE']['SilvercartCustomerRebate']['ValidUntil']                        = 'Gültig bis';
$lang['de_DE']['SilvercartCustomerRebate']['Type']                              = 'Typ';
$lang['de_DE']['SilvercartCustomerRebate']['TypeAbsolute']                      = 'Absolut';
$lang['de_DE']['SilvercartCustomerRebate']['TypePercent']                       = 'Prozentual';
$lang['de_DE']['SilvercartCustomerRebate']['Value']                             = 'Wert';
$lang['de_DE']['SilvercartCustomerRebate']['Title']                             = 'Bezeichnung';
$lang['de_DE']['SilvercartCustomerRebate']['TaxInfo']                           = 'Anteil auf Positionen mit %s%% MwSt.';