<?php

namespace SilverCart\CustomerRebates\Extensions;

use SilverCart\CustomerRebates\Model\CustomerRebate;
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
 */
class ProductGroupPageExtension extends DataExtension
{
    /**
     * belongs_many_many relations
     *
     * @return array
     */
    private static $belongs_many_many = [
        'CustomerRebates' => CustomerRebate::class,
    ];
    
    /**
     * Updates the field labels.
     * 
     * @param array &$labels labels.
     * 
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.12.2018
     */
    public function updateFieldLabels(&$labels)
    {
        $labels = array_merge($labels, [
            'CustomerRebates' => CustomerRebate::singleton()->plural_name(),
        ]);
    }
}