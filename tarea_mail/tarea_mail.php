<?php
/**
* 2007-2021 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2021 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

use Doctrine\ORM\Mapping\Id;
use PrestaShop\PrestaShop\Core\Domain\Customer\Query\GetCustomerOrders;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Tarea_mail extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'tarea_mail';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Ruben';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('tarea_mail');
        $this->description = $this->l('modulo para la creación de la tarea del mail ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
       
        Configuration::updateValue('TAREA_MAIL_LIVE_MODE', false);


        return parent::install()  &&
            $this->registerHook('header') && $this->registerHook('actionValidateOrder') &&
            $this->registerHook('backOfficeHeader') && $this->registerHook('DisplayOrderConfirmation');
    }

    public function uninstall()
    {
        Configuration::deleteByName('TAREA_MAIL_LIVE_MODE');


        return parent::uninstall() ;
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('guardar')) == true) { //submitemailcouponModule
            $this->postProcess();
        } else if(((bool)Tools::isSubmit('guardar2')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);


        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'guardar'; 
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues('id_customer', 'reduction_percent', 'minimum_amount', 'code'), 
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuracion del cupon descuento INDIVIDUAL'),
                    'icon' => 'icon-cogs',
                    'id'=> 'formemail',
                    'method'=> $_POST,
                ),

                'input' => array(
                    
                    array(
                        'type' => 'html',
                        'label' => $this->l('Importe en € a gastar'),
                        'name' => 'moneydiscount',
                        'desc' => $this->l('El importe que el cliente debe gastar para que se genere el cupón descuento'),
                        'html_content' => '<input type="number" name="moneydiscount">',
                        'suffix'=>'€',
                        'required'=> true,
                    ),
                    array(
                        'type' => 'html',
                        'label' => $this->l('€ de Descuento'),
                        'desc' => $this->l('Descuento en € que se le hará al usuario cuando llegue a la cantidad establecida'),
                        'name' => 'discount',
                        'html_content'=> '<input type="number" name="discount">',
                        'suffix'=>'€',
                        'required'=> true,
                        
                    ),

                ),
                'submit' => array(
                    'title' => $this->l('Guardar'),
                    'name'=> 'guardar',
                ),
            ),
        );
    }


    protected function getConfigFormValues()
    {
        return array(
            'moneydiscount' => Configuration::get('reduction_percent'),
            'discount' => Configuration::get('minimum_amount'),
            'codediscount'=>Configuration::get('code'),
            'user_id'=> Configuration::get('id_customer'),
            'MINIMOREQUERIDO' => Configuration::get('MINIMOREQUERIDO'),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        if (Tools::isSubmit('guardar')) {
            $id_emailcoupon=(int)Tools::getValue('');
            $moneydiscount=(int)(Tools::getValue('reduction_percent'));
            $discount=(int)(Tools::getValue('minimum_amount'));
            $codediscount=(string)(Tools::getValue('code'));
            $user_id=(int)(Tools::getValue('id_customer'));

            Db::getInstance()->insert('cart_rule', array(
                'reduction_percent'=>$moneydiscount,
                'minimum_amount'=>$discount,
                'code'=>$codediscount,
                'id_customer'=>$user_id,
            ));
        } 
    }


    /**c
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function  hookactionValidateOrder($params) {
        $cantidad_gastada=Db::getInstance()->getValue('SELECT ROUND(sum(po.total_paid),2) as totalpaid FROM `ps_orders` po where id_customer="'.pSQL($this->context->customer->id).'"');
        $nombre= $this->context->customer->firstname;
        $apellido= $this->context->customer->lastname;
        $cupones=Db::getInstance()->getValue('SELECT code FROM `ps_cart_rule` where id_customer="'.pSQL($this->context->customer->id).'"');
       // $this->CartRule::getCustomerCartRules($this->context->language->id, $this->context->customer->id, true, false);
        
         
         Mail::Send(
             (int)(Configuration::get('PS_LANG_DEFAULT')), // defaut language id
             'contact', // email template file to be use
             ' Module Installation', // email subject
             array(
                 '{email}' => Configuration::get('PS_SHOP_EMAIL'), 
                 '{message}' => $nombre. ' '.$apellido. ' lleva gastado '.$cantidad_gastada . ' €'. 'su codigo de descuento es el ' .$cupones
             ), 
             Configuration::get('PS_SHOP_EMAIL'), 
             NULL, //receiver name
             NULL, //from email address
             NULL,  //from name
             NULL, //file attachment
             NULL, //mode smtp
             _PS_MODULE_DIR_ . '/mails/' //custom template path
         );
    }



    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
        $this->context->controller->addCSS($this->_path.'/views/css/style.css');
        $this->context->controller->addJS($this->_path."/views/js/scratch.js");
    }


   public function crearCupon($parems) {
    $id_usuario = $this->context->customer->id;
    $user_email = $this->context->customer->email;
    $cantidad_configurada=Configuration::get('CANTIDAD_MODULO_CUPON');
    $enviarmail=true;
   
    $cupon = new CartRule();

 
   

    $idioma = Language::getLanguages();
    $nombre_cupon = array();
    foreach ($idioma as $language) {
        $nombre_cupon[$language['id_lang']] = 'Descuento de usuario' ;
    }
    $cupon->id_customer = (int) $this->context->customer->id;
    $cupon->name = $nombre_cupon;
    $cupon->description = $this->l('Cupón por gastar dinero en la web');
    $cupon->quantity = 1;
    $cupon->code=$this->generarCodigo(8);
    $cupon->quantity_per_user = 1;
    $cupon->date_from = date('Y-m-d');
    $cupon->date_to = strftime('%Y-%m-%d', strtotime('+2 year'));
    $cupon->active = true;
    $cupon->cart_rule_restriction = true;
    $cupon->minimum_amount = 0;
    $cupon->reduction_tax = 1; 
    $cupon->partial_use = false;
    $cupon->product_restriction = false;
    $cupon->add();

  
    }

    public function generarCodigo($longitud)
{
    $codigo = "";
    $caracteres="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    //el maximo de caracteres a usar
    $max=strlen($caracteres)-1;
    //creamos un for para generar el codigo aleatorio utilizando parametros min y max
    for($i=0;$i < $longitud;$i++)
    {
        $codigo.=$caracteres[rand(0,$max)];
    }
    
    return $codigo;
}
public function hookDisplayOrderConfirmation($params)
        {
            $id_cupon=Db::getInstance()->getValue('SELECT code FROM `ps_cart_rule` where id_customer="'.pSQL($this->context->customer->id).'"');
            $this->context->smarty->assign('cupon',$id_cupon); 
            $this->context->smarty->assign(array(
                'urlcss'=>$this->_path.'/views/css/style.css',
                'urljs'=>$this->_path.'/views/js/scratch.js',
            ));
       
            return $this->display(__FILE__,'views/templates/hook/canvas.tpl');
       
        }

}