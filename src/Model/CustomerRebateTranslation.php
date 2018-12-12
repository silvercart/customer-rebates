<?php

namespace SilverCart\CustomerRebates\Model;

use SilverCart\Dev\Tools;
use SilverStripe\ORM\DataObject;

/**
 * Translations for customer rebate model.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class CustomerRebateTranslation extends DataObject
{
    use \SilverCart\ORM\ExtensibleDataObject;
    /**
     * DB table name.
     *
     * @var string
     */
    private static $table_name = 'SilvercartCustomerRebateTranslation';
    /**
     * Attributes.
     *
     * @var array
     */
    private static $db = [
        'Title' => 'Varchar(256)',
    ];
    /**
     * 1:1 or 1:n relationships.
     *
     * @var array
     */
    private static $has_one = [
        'CustomerRebate' => CustomerRebate::class,
    ];
    
    /**
     * Returns the translated singular name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     */
    public function singular_name() {
        return Tools::singular_name_for($this);
    }

    /**
     * Returns the translated plural name of the object. If no translation exists
     * the class name will be returned.
     * 
     * @return string
     */
    public function plural_name() {
        return Tools::plural_name_for($this); 
    }
    
    /**
     * Field labels for display in tables.
     *
     * @param boolean $includerelations A boolean value to indicate if the labels returned include relation fields
     *
     * @return array
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function fieldLabels($includerelations = true)
    {
        $this->beforeUpdateFieldLabels(function(&$labels) {
            $labels = array_merge(
                    $labels,
                    [
                        'Title' => CustomerRebate::singleton()->fieldLabel('Title'),
                    ]
            );
        });
        return parent::fieldLabels($includerelations);
    }
}

