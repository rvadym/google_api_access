<?php
/**
 * Created by Vadym Radvansky
 * Date: 2/27/14 3:31 PM
 */
namespace rvadym\google_api_access;
class Model_Access extends \Model_Table {
    public $table = 'rvadym_google_api_access_access';
    function init() {
        parent::init();

        $this->addField('name');

    }


    /* {
        "access_token":"ya29.1.AADtN_Wt7gNgBSgGFjpoA5b8JcDgD2Z5VXR1jRS_6Ap7CWF0BFBwOM_bhxde-QM",
        "token_type":"Bearer",
        "expires_in":3600,
        "refresh_token":"1\/oX10GiLqClr-kKKtq54YpN3XgQ3a_fGUmxXl7LpQ_Xg",
        "created":1393506916
    } */
}
