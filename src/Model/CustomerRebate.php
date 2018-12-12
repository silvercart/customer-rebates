<?php

namespace SilverCart\CustomerRebates\Model;

use SilverCart\Dev\Tools;
use SilverCart\Model\Customer\Customer;
use SilverCart\Model\Pages\ProductGroupPage;
use SilverCart\Model\Order\ShoppingCart;
use SilverCart\ORM\DataObjectExtension;
use SilverCart\ORM\FieldType\DBMoney;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\Controller;
use SilverStripe\Forms\TreeMultiselectField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBFloat;
use SilverStripe\ORM\Map;
use SilverStripe\Security\Group;
use SilverStripe\Security\Member;

/**
 * Customer rebate model.
 * 
 * @package SilverCart
 * @subpackage CustomerRebates\Model
 * @author Sebastian Diel <sdiel@pixeltricks.de>
 * @copyright 2018 pixeltricks GmbH
 * @since 12.12.2018
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */
class CustomerRebate extends DataObject
{
    use \SilverCart\ORM\ExtensibleDataObject;
    /**
     * DB table name.
     *
     * @var string
     */
    private static $table_name = 'SilvercartCustomerRebate';
    /**
     * DB attributes.
     *
     * @var array
     */
    private static $db = [
        'ValidFrom'                      => DBDate::class,
        'ValidUntil'                     => DBDate::class,
        'Type'                           => 'Enum("absolute,percent","absolute")',
        'Value'                          => DBFloat::class,
        'MinimumOrderValue'              => DBMoney::class,
        'RestrictToNewsletterRecipients' => DBBoolean::class,
    ];
    /**
     * Has one relations.
     *
     * @var array
     */
    private static $has_one = [
        'Group' => Group::class,
    ];
    /**
     * Has many relations.
     *
     * @var array
     */
    private static $has_many = [
        'CustomerRebateTranslations' => CustomerRebateTranslation::class,
    ];
    /**
     * Many many relations.
     *
     * @var array
     */
    private static $many_many = [
        'ProductGroups' => ProductGroupPage::class,
    ];
    /**
     * Casted attributes.
     *
     * @var array
     */
    private static $casting = [
        'Title'                 => 'Text',
        'MinimumOrderValueNice' => 'Text',
    ];
    /**
     * Default sort.
     *
     * @var string
     */
    private static $default_sort = 'ValidFrom DESC';
    /**
     * indicator to prevent the module from loading.
     *
     * @var bool
     */
    public $doNotCallThisAsShoppingCartPlugin = false;
    /**
     * The shopping cart.
     *
     * @var ShoppingCart
     */
    protected $shoppingCart = null;
    /**
     * The rebate positions.
     *
     * @var ArrayList
     */
    protected $rebatePositions = null;
    /**
     * The related product groups.
     *
     * @var ArrayList
     */
    protected $relatedProductGroups = null;

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
     * Field labels.
     * 
     * @param bool $includerelations Include relations?
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.12.2018
     */
    public function fieldLabels($includerelations = true)
    {
        $this->beforeUpdateFieldLabels(function(&$labels) {
            $labels = array_merge(
                    $labels,
                    [
                        'ValidFrom'                      => _t(self::class . '.ValidFrom', 'Valid from'),
                        'ValidUntil'                     => _t(self::class . '.ValidUntil', 'Valid until'),
                        'Type'                           => _t(self::class . '.Type', 'Type'),
                        'TypeAbsolute'                   => _t(self::class . '.TypeAbsolute', 'Absolute'),
                        'TypePercent'                    => _t(self::class . '.TypePercent', 'Relative'),
                        'Value'                          => _t(self::class . '.Value', 'Value'),
                        'Title'                          => _t(self::class . '.Title', 'Title'),
                        'MinimumOrderValue'              => _t(self::class . '.MinimumOrderValue', 'Minimum order value'),
                        'RestrictToNewsletterRecipients' => _t(self::class . '.RestrictToNewsletterRecipients', 'Restricted to newsletter recipients'),
                        'Group'                          => Group::singleton()->singular_name(),
                        'CustomerRebateTranslations'     => CustomerRebateTranslation::singleton()->plural_name(),
                        'ProductGroups'                  => ProductGroupPage::singleton()->plural_name(),
                    ]
            );
        });
        return parent::fieldLabels($includerelations);
    }
    
    /**
     * Summary fields.
     * 
     * @return array
     * 
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 12.12.2018
     */
    public function summaryFields()
    {
        $summaryFields = [
            'Title'                 => $this->fieldLabel('Title'),
            'ValidFrom'             => $this->fieldLabel('ValidFrom'),
            'ValidUntil'            => $this->fieldLabel('ValidUntil'),
            'MinimumOrderValueNice' => $this->fieldLabel('MinimumOrderValue'),
            'Type'                  => $this->fieldLabel('Type'),
            'Value'                 => $this->fieldLabel('Value'),
        ];
        $this->extend('updateSummaryFields', $summaryFields);
        return $summaryFields;
    }
    
    /**
     * The cms fields.
     * 
     * @param array $params Params for scaffolding
     * 
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = DataObjectExtension::getCMSFields($this);
        
        $validFromField  = $fields->dataFieldByName('ValidFrom');
        $validUntilField = $fields->dataFieldByName('ValidUntil');
        $typeField       = $fields->dataFieldByName('Type');
        
        $validFromField->config()->set('showcalendar', true);
        $validUntilField->config()->set('showcalendar', true);
        
        $typeFieldValues = [
            'absolute'  => $this->fieldLabel('TypeAbsolute'),
            'percent'   => $this->fieldLabel('TypePercent'),
        ];
        $typeField->setSource($typeFieldValues);
        
        $productGroupHolder = Tools::PageByIdentifierCode('SilvercartProductGroupHolder');
        $productGroupsField = TreeMultiselectField::create(
                'ProductGroups',
                $this->fieldLabel('ProductGroups'),
                SiteTree::class
        );
        $productGroupsField->setTreeBaseID($productGroupHolder->ID);

        $fields->addFieldToTab('Root.Main', $productGroupsField);
        
        return $fields;
    }
    
    /**
     * getter for the pseudo attribute title
     *
     * @return string the title in the corresponding frontend language 
     */
    public function getTitle()
    {
        return $this->getTranslationFieldValue('Title');
    }
    
    /**
     * Returns the MinimumOrderValue in a nice format.
     *
     * @return string
     */
    public function getMinimumOrderValueNice()
    {
        return $this->MinimumOrderValue->Nice();
    }
    
    /**
     * Returns the related product groups or its translations.
     * 
     * @return \SilverStripe\ORM\SS_List
     */
    public function getRelatedProductGroups()
    {
        if ($this->ProductGroups()->count() == 0) {
            // Workaround to match translations of the physical related product groups.
            $query1 = 'SELECT "SCRSPG"."SilvercartProductGroupPageID" FROM "SilvercartCustomerRebate_ProductGroups" AS "SCRSPG" WHERE "SCRSPG"."SilvercartCustomerRebateID" = \'' . $this->ID . '\'';
            $query2 = 'SELECT "STTG2"."TranslationGroupID" FROM "SiteTree_translationgroups" AS "STTG2" WHERE "STTG2"."OriginalID" IN (' . $query1 . ')';
            $query3 = 'SELECT "STTG"."OriginalID" FROM "SiteTree_translationgroups" AS "STTG" WHERE "STTG"."OriginalID" NOT IN (' . $query1 . ') AND "STTG"."TranslationGroupID" IN (' . $query2 . ')';
            $this->relatedProductGroups = ProductGroupPage::get()->where('"SiteTree"."ID" IN (' . $query3 . ')');
            if (!($this->relatedProductGroups instanceof DataList)) {
                $this->relatedProductGroups = ArrayList::create();
            }
        } else {
            $this->relatedProductGroups = $this->ProductGroups();
        }
        return $this->relatedProductGroups;
    }
    
    /**
     * Returns the rebate value for the current shopping cart.
     * 
     * @return float
     */
    public function getRebateValueForShoppingCart()
    {
        $value = 0;
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            if (Customer::currentUser() instanceof Member) {
                $this->doNotCallThisAsShoppingCartPlugin = true;
                $cart = Member::currentUser()->ShoppingCart();
                if ($cart instanceof ShoppingCart) {
                    if ($this->getRelatedProductGroups()->count() == 0) {
                        // get rebate value from total amount
                        $total = $cart->getAmountTotalWithoutFees();
                        if ($this->Type == 'absolute') {
                            $value = $this->Value;
                        } else {
                            $value = ($total->getAmount() / 100) * $this->Value;
                        }
                        if ($total->getAmount() < $value) {
                            $value = $total->getAmount();
                        }
                    } else {
                        // get rebate value from single positions.
                        $value = $this->getRebateValueForShoppingCartPositions();
                    }
                    $this->doNotCallThisAsShoppingCartPlugin = false;
                }
            }
        }
        return $value;
    }
    
    /**
     * Returns the rebate value for shopping cart positions.
     * 
     * @return float
     */
    protected function getRebateValueForShoppingCartPositions()
    {
        $value       = 0;
        $totalAmount = 0;

        if ($this->Type == 'absolute') {
            $value = $this->Value;
        }
        
        foreach ($this->getRebatePositions() as $position) {
            $totalAmount += $position->getPrice()->getAmount();
            if ($this->Type == 'percent') {
                $value += ($position->getPrice()->getAmount() / 100) * $this->Value;
            }
        }
        
        if ($totalAmount < $value) {
            $value = $totalAmount;
        }
        
        return $value;
    }
    
    /**
     * Returns the positions to rebate.
     * 
     * @return ArrayList
     */
    public function getRebatePositions()
    {
        $rebatePositions    = ArrayList::create();
        $cart               = Customer::currentUser()->ShoppingCart();
        $validProductGroups = $this->getRelatedProductGroups()->map();
        $positionNum        = 1;
        foreach ($cart->ShoppingCartPositions() as $position) {
            if ($position instanceof ShoppingCartPosition) {
                $positionNum++;
                continue;
            }
            $product = $position->Product();
            $position->PositionNum = $positionNum;
            if (array_key_exists($product->ProductGroupID, $validProductGroups->toArray())) {
                $rebatePositions->push($position);
            } elseif ($product->ProductGroupMirrorPages()->exists()) {
                $map = $product->ProductGroupMirrorPages()->map();
                if ($map instanceof Map) {
                    $map = $map->toArray();
                }
                $mirrorProductGroupIDs = array_keys($map);
                foreach ($mirrorProductGroupIDs as $mirrorProductGroupID) {
                    if (array_key_exists($mirrorProductGroupID, $validProductGroups)) {
                        $rebatePositions->push($position);
                        break;
                    }
                }
            }
            $positionNum++;
        }
        
        return $rebatePositions;
    }

    /**
     * Returns the current shopping cart.
     * 
     * @return ShoppingCart
     */
    public function getShoppingCart()
    {
        $this->doNotCallThisAsShoppingCartPlugin = true;
        if (is_null($this->shoppingCart)) {
            $this->shoppingCart = Customer::currentUser()->ShoppingCart();
        }
        return $this->shoppingCart;
    }

    /**
     * Returns an instance of a SilverCart CustomerRebate object for the given
     * shopping cart.
     *
     * @param Shoppingcart $shoppingCart The shopping cart object
     *
     * @return CustomerRebate
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 29.10.2013
     */
    public function loadObjectForShoppingCart(ShoppingCart $shoppingCart) {
        $object = null;
        if (!$this->doNotCallThisAsShoppingCartPlugin
         && Customer::currentUser() instanceof Member
        ) {
            $object = Member::currentUser()->getCustomerRebate();
        }
        return $object;
    }

    /**
     * Hook for the init method of the shopping cart.
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function ShoppingCartInit() {
        $result = null;
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            $controller = Controller::curr();
            // Don't initialise when called from within the cms
            if (!$controller->isFrontendPage) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Performs checks related to the shopping cart entries to ensure that
     * the rebate is allowed to be placed in the cart.
     *
     * @param ShoppingCart $ShoppingCart                 the shopping cart to check against
     * @param Member       $member                       the shopping cart to check against
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted
     *
     * @return void
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function performShoppingCartConditionsCheck(ShoppingCart $shoppingCart, $member, $excludeShoppingCartPositions = false) {
        $result = false;
        
        if (!$this->doNotCallThisAsShoppingCartPlugin) {
            if ($this->getShoppingCart()->ShoppingCartPositions()->Count() > 0) {
                $this->doNotCallThisAsShoppingCartPlugin = false;
                if (Customer::currentUser() instanceof Member) {
                    $result = Customer::currentUser()->hasCustomerRebate();
                }
            }
            $this->doNotCallThisAsShoppingCartPlugin = false;
        }
        
        return $result;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns an entry for the cart listing.
     *
     * @param ShoppingCart $shoppingCart                 The shoppingcart object
     * @param Member       $member                       The customer object
     * @param Bool         $taxable                      Indicates if taxable or nontaxable entries should be returned
     * @param array        $excludeShoppingCartPositions Positions that shall not be counted; can contain the ID or the className of the position
     * @param Bool         $createForms                  Indicates wether the form objects should be created or not
     *
     * @return ArrayList
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function ShoppingCartPositions(ShoppingCart $shoppingCart, Member $member, $taxable = true, $excludeShoppingCartPositions = false, $createForms = true) {
        $rebatePositions = ArrayList::create();
        
        if (!$this->doNotCallThisAsShoppingCartPlugin
         && $taxable
         && $this->getShoppingCart()->ShoppingCartPositions()->exists()
         && Customer::currentUser()->hasCustomerRebate()
        ) {
            if (is_null($this->rebatePositions)) {
                $taxRates           = $this->getShoppingCart()->getTaxRatesWithoutFeesAndCharges();
                $mostValuableRate   = $this->getShoppingCart()->getMostValuableTaxRate($taxRates);
                $this->doNotCallThisAsShoppingCartPlugin = false;

                $position               = ShoppingCartPosition::create();
                $position->Tax          = $mostValuableRate;
                $this->rebatePositions  = $position->splitForTaxRates($taxRates);
            }
            $this->doNotCallThisAsShoppingCartPlugin = false;
            $rebatePositions = $this->rebatePositions;
        }
        
        return $rebatePositions;
    }

    /**
     * This method is a hook that gets called by the shoppingcart.
     *
     * It returns taxable entries for the cart listing.
     *
     * @param ShoppingCart $shoppingCart The SilverCart ShoppingCart object
     * @param Member       $member       The member object
     *
     * @return SS_List
     *
     * @author Sebastian Diel <sdiel@pixeltricks.de>
     * @since 17.07.2013
     */
    public function TaxableShoppingCartPositions(ShoppingCart $shoppingCart, Member $member) {
        $positions = $this->ShoppingCartPositions($shoppingCart, $member, true);
        return $positions;
    }
}