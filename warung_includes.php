<?php 

// ===================
//  G E N E R A L 
// ===================
interface IComparable {
	function equals($obj);
}

// ===================
//  S H I P P I N G 
// ===================
class ShippingDestination implements IComparable {
	public $country;
	public $state;
	public $city;
	public $price;
	
	function __construct($country, $state, $city, $price) {
		$this->country = $country;
		$this->state = $state;
		$this->city = $city;
		$this->price = $price;
	}
	
	function equals($obj) {
		return $this->equalsCountry($obj) && $this->equalsState($obj) && $this->equalsCity($obj);
	}
	
	function equalsCountry($obj) {
		if (is_object($obj)) {
			return $this->country == $obj->country;
		} else {
			return $obj == $this->country;
		}
	}
	
	function equalsState($obj) {
		if (is_object($obj)) {
			return $this->state == $obj->state;
		} else {
			return $obj == $this->state;
		}
		
	}
	
	function equalsCity($obj) {
		if (is_object($obj)) {
			return $this->city == $obj->city;
		} else {
			return $obj == $this->city;
		}
	}
	
	function key() {
		return $this->country.'-'.$this->state.'-'.$this->city;
	}
}

// shipping service
interface IShippingService {
	/**
	 * Get the price/destination info from given destination and items
	 * @param IShippingDestination $destination
	 * @param Array of KeranjangItem $items
	 */
	function getPrice($destination, $items);
	
	/**
	 * Get all destinations available in this shipping service
	 */
	function getDestinations();
	
	function getDestination($destination);
	
	/**
	 * Get all countries available in this shipping service
	 */
	function getCountries();
	
	/**
	 * Get all states available in this shipping services
	 */
	function getStates($country);
	
	/**
	 * Get all cities available in this shipping services;
	 */
	function getCities($country);
	
	/**
	 * Get all cities in given country and state
	 * @param unknown_type $country
	 * @param unknown_type $states
	 */
	function getCitiesByState($country, $state);
}

interface IShippingServiceByWeight extends IShippingService {
	
	function getPriceByWeight($destination, $weight);
	
	/**
	 * Round per item weight based on $perItemWeightRoundingPolicy
	 * 
	 * @param numeric $weight
	 */
	function roundWeight($weight);
	
	/**
	 * Round total weight based on $totalWeightRoundingPolicy
	 * 
	 * @param numeric $weight
	 */
	function roundTotalWeight($weight);
}


class ShippingDestinationByWeight extends ShippingDestination {
	
	public $minWeight;
	public $discountedWeight;
	
	function __construct($country, $state, $city, $price, $minWeight, $discountedWeight=0) {
		parent::__construct($country, $state, $city, $price);
		$this->minWeight = $minWeight;
		$this->discountedWeight = $discountedWeight;
	}
	
}

abstract class ShippingService implements IShippingService {
	/**
	 * Shipping Service Name
	 * Enter description here ...
	 * @var string
	 */
	protected $name;
	/**
	 * Destinations reachable by this shipping service
	 * Enter description here ...
	 * @var array of IShippingDestination
	 */
	protected $destinations;
	
	protected $countries = array();
	protected $statesByCountry = array();
	protected $citiesByCountry = array();
	protected $citiesByState = array();
	
	function __construct($destinations, $name) {
		$this->destinations = $destinations;
		$this->name = $name;
	}
	
	function getName() {
		return $this->name;
	}
	
	function getDestinations() {
		return $this->destinations;
	}
	
	function getDestination($destination) {
		if (isset($this->destinations[$destination->key()])) {
			return $this->destinations[$destination->key()];
		} else {
			// loop through
			foreach ($this->destinations as $dest) {
				if ($dest->equals($destination)) {
					return $dest;
				}
			}
		}
		
		return null;
	}
	
	function getCountries(){
		$ret = array();
		
		if (empty($this->countries)) {
			foreach ($this->destinations as $dest) {
				$this->countries[(string)$dest->country] = $dest->country;
			}
			$ret = $this->countries;
		} else {
			$ret = $this->countries;
		}
					
		return $ret;
	}
	
	function getStates($country) {
		$ret = array();
		
		if (empty($this->statesByCountry) || empty($this->statesByCountry[$country]) ) {
			$this->statesByCountry[$country] = array();
			foreach ($this->destinations as $dest) {
				if ($dest->country == $country) {
					$this->statesByCountry[$country][$dest->state] = $dest->state;
				}
			}
			$ret = $this->statesByCountry[$country];
		} else {
			$ret = $this->statesByCountry[$country];
		}
		
		return $ret;
	}
	
	function getCities($country){
		$ret = array();
		
		if (empty($this->citiesByCountry) || empty($this->citiesByCountry[$country]) ) {
			$this->citiesByCountry[$country] = array();
			foreach ($this->destinations as $dest) {
				if ($dest->country == $country) {
					$this->citiesByCountry[$country][$dest->city] = $dest->city;
				}
			}
			$ret = $this->citiesByCountry[$country];
		} else {
			$ret = $this->citiesByCountry[$country];
		}
		
		return $ret;
	}
	
	function getCitiesByState($country, $state) {
		$ret = array();
		
		if (empty($this->citiesByState) || empty($this->citiesByState[$country.'-'.$state]) ) {
			$this->citiesByState[$country.'-'.$state] = array();
			foreach ($this->destinations as $dest) {
				if ($dest->country == $country && $dest->state == $state) {
					$this->citiesByState[$country.'-'.$state][$dest->city] = $dest->city;
				}
			}
			$ret = $this->citiesByState[$country.'-'.$state];
		} else {
			$ret = $this->citiesByState[$country.'-'.$state];
		}
		
		return $ret;
	}
	
}

class ShippingServiceByWeight extends ShippingService implements IShippingServiceByWeight {
	/**
	 * total weight rounding policy
	 * 
	 * @var int, -1=floor, 0=no rounding, 1=ceil, 2=round >= 0.5 up
	 */
	var $totalWeightRoundingPolicy = 0; // -1 floor, 0 no rounding, 1 ceil
	/**
	 * per-Item weight rounding policy
	 * 
	 * @var int, -1=floor, 0=no rounding, 1=ceil, 2=round >= 0.5 up
	 */
	var $perItemWeightRoundingPolicy = 0; // -1 floor, 0 no rounding, 1 ceil
	
	function __construct($destinations, $name) {
		parent::__construct($destinations, $name);
	}
	
	function getPrice($destination, $items) {
		
		// count total weight first
		$total_weight = 0;
		foreach ($items as $item) {
			$weight = 0;
			$qtt = 0;
			if (isset ($item->weight)) {
				$weight = $item->weight;
			}
			if (isset($item->quantity)) {
				$qtt = $item->quantity;
			}
			// apply per item weight rounding
			$total_weight += $this->roundWeight($weight) * $qtt;
		}	
		// apply total weight rounding
		$total_weight = $this->roundTotalWeight($total_weight);
		
		// check if destination exists with dest total weight
		return $this->getPriceByWeight($destination, $total_weight);
	}
	
	function getPriceByWeight($destination, $weight) {
		
		$tmp;
		if (isset ($this->destinations[$destination->key()])) {
			$tmp = $this->destinations[$destination->key()];
		}
		
		if (isset($tmp) && $tmp->minWeight <= $weight) {
			return $tmp->price * $weight;
		} else {
			foreach ($this->destinations as $dest) {
				if ($dest->equals($destination) && $dest->minWeight <= $weight) {
					return $dest->price * $weight;
				}
			}
		}
		
		return 0;
	}
	
	function roundWeight($weight) {
		if ($this->perItemWeightRoundingPolicy == 0) {
			return $weight;
		} else if ($this->perItemWeightRoundingPolicy == 1) {
			return ceil($weight);
		} else if ($this->perItemWeightRoundingPolicy == 2) {
			return round($weight);
		} else if ($this->perItemWeightRoundingPolicy == -1) {
			return floor($weight);
		} 
		return $weight;
	}
	
	function roundTotalWeight($totalWeight) {
		if ($this->totalWeightRoundingPolicy == 0) {
			return $totalWeight;
		} else if ($this->totalWeightRoundingPolicy == 1) {
			return ceil($totalWeight);
		} else if ($this->totalWeightRoundingPolicy == 2) {
			return round($totalWeight);
		} else if ($this->totalWeightRoundingPolicy == -1) {
			return floor($totalWeight);
		} 
		return $weight;
	}
	
}

// ===================
//  K E R A N J A N G
// ===================
class KeranjangItem {
	public $productId;
	public $name;
	public $price;
	public $weight;
	public $attachment;
	public $quantity;
	public $category;
	
	function __construct($productId, $name, $price, $weight, $attachment, $quantity) {
		$this->productId=$productId;
		$this->name=$name;
		$this->price = $price;
		$this->weight = $weight;
		$this->attachment = $attachment;
		$this->quantity = $quantity;
	}
	
}


class KeranjangSummary {
	/**
	 * Variable untuk menyimpan summary item
	 * @var Array Of KeranjangItem
	 */
	public $items;
	public $totalPrice;
	public $totalWeight;
	public $totalItems;
	
	// shipping
	public $shippingName;
	public $totalShippingPrice;
}

// cart service
interface IKeranjangService {
	/**
	 * Add item to cart	 
	 * @param KeranjangItem $item
	 * @param int $count
	 */
	function addItem($item, $count);
	
	/**
	 * Remove specified item from cart
	 * @param KeranjangItem $item
	 * @param int $count
	 */
	function removeItem($item, $count);
	
	/**
	 * Remove all item from the shopping cart
	 */
	function emptyCart();
	
	/**
	 * Return all items in the shopping cart
	 */
	function getItems();
	/**
	 * Get Total Pice of all items
	 */
	function getTotalPrice();
	
	/**
	 * Get total item available in the shopping cart 
	 */
	function getTotalItems();
	
	/**
	 * 
	 * Get total weight of available items
	 */
	function getTotalWeight();
	
	function getSummary();
	
}

// KeranjangService class
class KeranjangService implements IKeranjangService {
	/**
	 * List of items
	 * @var Array of KeranjangItem
	 */
	protected $items;
	protected $summary;
	protected $needRecount;
	
	/**
	 * Create KeranjangService from intial items. 
	 * If no items specified empty array will be assigned.
	 * @param Array of KeranjangItem $items
	 */
	function __construct($items) {
		if (!isset($items)) {
			$this->items = array();
		} else {
			$this->items = $items;
		}
	}
	
	function addItem($item, $count) {
		$oldItem;

		if (isset($this->items[strval($item->productId)])) {
			$oldItem = $this->items[strval($item->productId)];
		}
		if (isset($oldItem)) {
			// existing item
			if ($count > 0) {
				$oldItem->quantity += $count;
			}
		} else {
			// new item
			$item->quantity = $count;
			$this->items[strval($item->productId)] = $item;
		}
		// recount summary
		$this->generateSummary();
	}
	
	function removeItem($item, $count) {
		$oldItem = &$this->items[strval($item->productId)];
		if (isset($oldItem)) {
			$oldQtt = $oldItem->quantity;
			var_dump($oldQtt);
			if ($oldQtt > $count) {
			    $oldItem->quantity = $oldQtt - $count;	
			} else {
				// remove from cart
				unset($this->items[strval($item->productId)]);
			}
			// recount summary
			$this->generateSummary();
		}
	}

	private function generateSummary() {
		$ret = new KeranjangSummary();
		
		$totalPrice = 0;
		$totalWeight = 0;
		$totalItems = 0;
		if (isset ($this->items)) {
			$ret->items = $this->items;
			foreach ($this->items as $item) {
				$totalPrice += $item->price * $item->quantity;
				$totalWeight += $item->weight * $item->quantity;
				$totalItems += $item->quantity;
			}
		}
		
		$ret->totalPrice = $totalPrice;
		$ret->totalWeight = $totalWeight;
		$ret->totalItems = $totalItems;
		
		$this->summary = $ret;
	}
	
	function getSummary() {
		return $this->summary;
	}
	
	function emptyCart() {
		unset($this->items);
	}
	
	function getItems() {
		return $this->items;
	}

	function getTotalPrice() {
		$sum = $this->summary;
		return $sum->totalPrice;
	}

	function getTotalItems() {
		$sum = $this->summary;
		return $sum->totalItems;
	}

	function getTotalWeight() {
		$sum = $this->summary;
		return $sum->totalWeight;
	}
}


// ===================
//  K A S I R 
// ===================
interface IKasirService {
	/**
	 * Return instance of WarungSummary class. with calculated totalShippingPrice.
	 * @param ShippingDestination $destination
	 * @param KeranjangService $keranjangService
	 * @param ShippingService $shippingService
	 */
	function getSummaryWithShippping($destination, $keranjangService, $shippingService);
	
	// shipping
	/**
	 * Get all available shipping serivces
	 */
	function getShippingServices();
	/**
	 * Get array of shipping service that can ship to given destination
	 * @param ShippingDestination $destination
	 */
	function getShippingServicesByDestination($destination);
	
	/**
	 * Get cheapest shipping service for given destination and items
	 * @param ShippingDestination $destination
	 * @param array Of KeranjangItem $items
	 */
	function getCheapestShippingService($destination, $items);
	
	/**
	 * Get shippingService instance of given name
	 * @param String $sname
	 * @return ShippingService instance or null if not found.
	 */
	function getShippingServiceByName($sname);
	
	/**
	 * Return array of Countries (string) that can be reach by at least one shipping service
	 */
	function getCountries();
	
	/**
	 * Return array of states within given country that can be reach by at least one shipping service
	 * @param string $country
	 */
	function getStatesByCountry($country);
	
	/**
	 * Return array of cities within given country that can be reach by at least one shipping service
	 * @param string $country
	 */
	function getCitiesByCountry($country);
	
	/**
	 * Return array of cities within given country and state that can be reach by at least one shipping service
	 * @param string $country
	 * @param string $state
	 */
	function getCitiesByState($country, $state);
}

class Kasir implements IKasirService {
	/**
	 * variable to store all shipping services
	 * @var array of IShippingService
	 */
	protected $shippingServices;
	protected $countries;
	protected $states;
	protected $cities;
	
	
	function __construct($shippingServices) {
		$this->shippingServices = $shippingServices;
	}
	
	function getSummaryWithShippping($destination, $keranjangService, $shippingService) {
		$sum = $keranjangService->getSummary();
		
		if (isset($sum)) {
			$sum->totalShippingPrice = $shippingService->getPrice($destination, $sum->items);
		}
		
		return $sum;
	}
	
	function getShippingServices() {
		return $this->shippingServices;
	}
	
	function getShippingServicesByDestination($destination) {
		$ret = array();
		foreach ($this->shippingServices as $ss) {
			if ($ss->getDestination($destination)) {
				array_push($ret, $ss);
			}
		}
		
		return $ret;
	}
	
	function getCheapestShippingService($destination, $items) {
		
		$map = array();
		
		foreach ($this->shippingServices as $ss) {
			$price = $ss->getPrice($destination, $items);
			$map[$price] = $ss;
		}
		
		ksort($map);
		
		//return 1st array
		foreach ($map as $m) {
			return $m;
		}
	}
	
	function getShippingServiceByName($sname) {
		if (isset($sname)) {
			foreach ($this->shippingServices as $ss) {
				if (strtolower($ss->getName) == strtolower($sname)) {
					return $ss;
				}
			}
		}
		
		return null;
	}
	
	function getCountries() {
		$ret = array();
		foreach ($this->shippingServices as $ss) {
			$ret = array_merge($ret, $ss->getCountries());
		}
		
		return $ret;
	}
	function getStatesByCountry($country) {
		$ret = array();
		foreach ($this->shippingServices as $ss) {
			$ret = array_merge($ret, $ss->getStates($country));
		}
		
		return $ret;
	}
	function getCitiesByCountry($country) {
		$ret = array();
		foreach ($this->shippingServices as $ss) {
			$ret = array_merge($ret, $ss->getCities($country));
		}
		
		return $ret;
	}
	function getCitiesByState($country, $state) {
		$ret = array();
		foreach ($this->shippingServices as $ss) {
			$ret = array_merge($ret, $ss->getCitiesByState($country, $state));
		}
		
		return $ret;
	}
	
}

class WarungKasir extends Kasir {
	var $shippingServices;
	var $shippingProductMap;
	
	function __construct($shippingServices, $shippingProductMap) {
		$this->shippingServices = $shippingService;
		$this->shippingProductMap = $shippingProductMap;
	}
	
	function getSummaryWithShippping($destination, $keranjangService, $shippingService) {
		
		$sum = $keranjangService->getSummary();
		
		// split mapped shipping products in items
		$items = $keranjangService->getItems();
		
		$mappedServiceItems = array();
		$otherServiceItems = array();
		
		foreach ($items as $item) {
			if ( isset($shippingProductMap[$item->category]) ) {
				$sname = $this->getShippingServiceByName($shippingProductMap[$item->category]);
				// pisahkan item yang ada di map jika shipping servicenya ada 
				if (isset ($mappedServiceItems[$sname]) && !empty($sname) ) {
					// mapped item
					if (isset($mappedServiceItems[$sname])) {
						// update
						$arr = $mappedServiceItems[$sname];
						$arr[$item->productId] = $item;
					} else {
						// new
						$arr = array();
						$mappedServiceItems[$sname] = $arr;
						$arr[$item->productId] = $item;
					}
				} else {
					// other item				
					$otherServiceItems[$item->productId] = $item;
				}				
			} else {
				// other item	
				$otherServiceItems[$item->productId] = $item;
			}
		}
		
		$totalOngkir = 0;
		
		// process mapped item
		if (!empty($mappedServiceItems)) {
			foreach ($mappedServiceItems as $k => $v) {
				$ss = $this->getShippingServiceByName($k);
				if (isset($ss)) {
					$totalOngkir += $ss->getPrice($destination, $v);
				}
			}
		}
		
		// process others item
		if (!empty($otherServiceItems)) {
			$ss = $this->getCheapestShippingService($destination, $otherServiceItems);
			$sum->shippingName = $ss->getName(); // default shipping name is from others item
			if (isset($ss)) {
				$totalOngkir += $ss->getPrice($destination, $otherServiceItems);
			}
		}
		
		return $sum;
	}
	
}

/* 
 * ==========================================================
 *  T E S T  C A S E
 * ==========================================================
 */


// ================== 
//  Shopping Cart 
// ==================
echo '<h1>Test Keranjang</h1>';
echo '<h3>Test Add</h3>';
$items=array();
$cart = new KeranjangService($items);

// add item
$item = new KeranjangItem(1, 'Sprei A - 180x200', 1000, 1, null, 1);
$cart->addItem($item, 1);

$item = new KeranjangItem(1, 'Sprei A - 180x200', 1000, 1, null, 1);
$cart->addItem($item, 5);

$item = new KeranjangItem(2, 'Sprei B - 120x200', 1000, 1, null, 1);
$cart->addItem($item, 1);

$item = new KeranjangItem(2, 'Sprei B - 120x200', 1000, 1, null, 2);
$cart->addItem($item, 1);

$item = new KeranjangItem(3, 'Bantal C - 100x30', 1000, 1, null, 1);
$cart->addItem($item, 1);

$item = new KeranjangItem(3, 'Bantal C - 100x30', 1000, 1, null, 2);
$cart->addItem($item, 1);

var_dump($cart->getSummary());

// remove item
echo '<h3>Test Remove 1</h3>';

$item = new KeranjangItem(3, 'Bantal C - 100x30', 1000, 1, null, 2);
$cart->removeItem($item, 1);
$cart->removeItem($item, 1);

var_dump($cart->getSummary());

echo '<h3>Test Remove 2</h3>';

$item = new KeranjangItem(2, 'Sprei B - 120x200', 1000, 1, null, 2);
$cart->removeItem($item, 5);

var_dump($cart->getSummary());


echo '<h3>Test Remove 3</h3>';

$item = new KeranjangItem(1, 'Sprei B - 120x200', 1000, 1, null, 2);
$cart->removeItem($item, 2);

var_dump($cart->getSummary());


// ================== 
//  Shipping 
// ==================
echo '<h1>Test shipping service</h1>';

$des = array(
	new ShippingDestinationByWeight('indonesia', 'dki', 'jakarta', 1000, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa timur', 'surabaya', 2000, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa timur', 'kediri', 2000, 1),
	new ShippingDestinationByWeight('indonesia', 'bali', 'denpasar', 3000, 1),
	new ShippingDestinationByWeight('indonesia', 'bali', 'singaraja', 3000, 1),
	new ShippingDestinationByWeight('indonesia', 'papua', 'papua', 5000, 1),
	
	new ShippingDestinationByWeight('usa', 'alaska', 'anchorage', 11000, 1),
	new ShippingDestinationByWeight('usa', 'alaska', 'fairbanks', 11000, 1),
	new ShippingDestinationByWeight('usa', 'hawai', 'honolulu', 12000, 1)
);

$ss = new ShippingServiceByWeight($des, 'jne');

echo '<h3>countries</h3>';
$ss_countries = $ss->getCountries();
var_dump($ss_countries);

echo '<h3>states</h3>';
foreach ($ss_countries as $c) {
	echo '<h4>get states in '.$c.'</h4>';
	$ss_states = $ss->getStates($c);
	var_dump($ss_states);	
	foreach ($ss_states as $s) {
		echo '<h4>get cities in '.$s.'</h4>';
		var_dump($ss->getCitiesByState($c, $s));
	}
}

echo '<h3>cities</h3>';
foreach ($ss_countries as $c) {
	echo '<h4>get cities in '.$c.'</h4>';
	var_dump($ss->getCities($c));
}


$testDest = $des[6];
echo '<h3>get price by items to '.$testDest->city.'</h3>';
$r = $ss->getPrice($testDest, $cart->getItems());
var_dump($r);

// ====================
// K A S I R  T E S T
// ====================

echo '<h1>Kasir Test</h1>';

$des2 = array(
	new ShippingDestinationByWeight('indonesia', 'dki', 'jakarta', 1100, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa timur', 'surabaya', 2100, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa timur', 'kediri', 2100, 1),
	new ShippingDestinationByWeight('indonesia', 'bali', 'denpasar', 3100, 1),
	new ShippingDestinationByWeight('indonesia', 'bali', 'singaraja', 3100, 1),
	new ShippingDestinationByWeight('indonesia', 'papua', 'papua', 5100, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa barat', 'bandung', 3100, 1),
	new ShippingDestinationByWeight('indonesia', 'jawa barat', 'subang', 5100, 1),
	
	new ShippingDestinationByWeight('usa', 'alaska', 'anchorage', 11001, 1),
	new ShippingDestinationByWeight('usa', 'alaska', 'fairbanks', 11001, 1),
	new ShippingDestinationByWeight('usa', 'hawai', 'honolulu', 12001, 1),
	
	new ShippingDestinationByWeight('canada', 'manitoba', 'montreal', 12001, 1),
);

$ss_pandusiwi = new ShippingServiceByWeight($des2, 'pandusiwi');

$kasir = new Kasir(array ($ss, $ss_pandusiwi));

echo '<h3>Test get countries</h3>';

$k_c = $kasir->getCountries();
var_dump($k_c);

foreach ($k_c as $co) {
	echo '<h3>Test get cities in '.$co.'</h3>';
	$c_c = $kasir->getCitiesByCountry($co);
	var_dump($c_c);
}

$k_d = array($des2[7], $des2[0]);
foreach ($k_d as $k_d_1) {
	echo '<h3>Test get shipping services by destination '.$k_d_1->city.'</h3>';
	$ssbd = $kasir->getShippingServicesByDestination($k_d_1);
	foreach ($ssbd as $kss) {
		var_dump( $kss->getName() );
	}
}

echo '<h3>Test get cheapest services to '.$k_d_1->city.'</h3>';
$k_item = new KeranjangItem(1, 'Sprei A - 180x200', 1000, 1, null, 1);
$k_items = array(1=>$k_item);

$k_cs = $kasir->getCheapestShippingService($des[0], $k_items);
var_dump($k_cs->getName());

?>