<?php

namespace SilverCart\CustomerRebates\Extensions;

use SilverCart\CustomerRebates\Model\CustomerRebate;
use SilverCart\Model\Pages\ProductGroupPage;
use SilverStripe\ORM\DataExtension;

/**
 * Extension for the SilverCart ProductGroupPage.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Extensions
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * 
 * @property ProductGroupPage $owner Owner
 */
class ProductGroupPageExtension extends DataExtension
{
    /**
     * belongs_many_many relations
     *
     * @return array
     */
    private static array $belongs_many_many = [
        'CustomerRebates' => CustomerRebate::class . '.ProductGroups',
    ];
    
    /**
     * Updates the field labels.
     * 
     * @param array &$labels labels.
     * 
     * @return void
     */
    public function updateFieldLabels(&$labels) : void
    {
        $labels = array_merge($labels, [
            'CustomerRebates' => CustomerRebate::singleton()->i18n_plural_name(),
        ]);
    }
}