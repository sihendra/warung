<?php


class UserInfo {

    public $name;
    public $email;
    public $mobilePhone;
    public $phone;
    public $address;
    public $city;
    public $state;
    public $country;
    public $additionalInfo;

    public function __construct($name, $email, $mobilePhone, $phone, $address, $city, $country, $additionalInfo) {
        $this->name = $name;
        $this->email = $email;
        $this->mobilePhone = $mobilePhone;
        $this->phone = $phone;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->additionalInfo = $additionalInfo;
    }

}

?>
