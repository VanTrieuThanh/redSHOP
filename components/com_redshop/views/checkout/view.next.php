<?php
/**
 * @package     redSHOP
 * @subpackage  Views
 *
 * @copyright   Copyright (C) 2008 - 2012 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later, see LICENSE.
 */

defined('_JEXEC') or die ('restricted access');

require_once(JPATH_COMPONENT . DS . 'helpers' . DS . 'product.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'helpers' . DS . 'order.php');
require_once(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_redshop' . DS . 'helpers' . DS . 'shipping.php');

class checkoutViewcheckout extends JViewLegacy
{
    function display($tpl = null)
    {
        global $mainframe;
        $shippinghelper  = new shipping();
        $order_functions = new order_functions();

        $option  = JRequest::getVar('option');
        $Itemid  = JRequest::getVar('Itemid');
        $issplit = JRequest::getVar('issplit');
        $task    = JRequest::getVar('task');

        $session = JFactory::getSession();
        if ($issplit != '')
        {
            $session->set('issplit', $issplit);
        }

        $payment_method_id = JRequest::getVar('payment_method_id');
        $users_info_id     = JRequest::getInt('users_info_id');
        $auth              = $session->get('auth');
        if (empty($users_info_id))
        {
            $users_info_id = $auth['users_info_id'];
        }
        $shipping_rate_id = JRequest::getVar('shipping_rate_id');
        $shippingdetail   = explode("|", $shippinghelper->decryptShipping(str_replace(" ", "+", $shipping_rate_id)));
        if (count($shippingdetail) < 4)
        {
            $shipping_rate_id = "";
        }
        $cart = $session->get('cart');

        if ($cart['idx'] < 1)
        {
            $msg = JText::_('COM_REDSHOP_EMPTY_CART');
            $mainframe->Redirect('index.php?option=' . $option . '&Itemid=' . $Itemid, $msg);
        }
        if (SHIPPING_METHOD_ENABLE)
        {
            if ($users_info_id < 1)
            {
                $msg  = JText::_('COM_REDSHOP_SELECT_SHIP_ADDRESS');
                $link = 'index.php?option=' . $option . '&view=checkout&Itemid=' . $Itemid . '&users_info_id=' . $users_info_id . '&shipping_rate_id=' . $shipping_rate_id . '&payment_method_id=' . $payment_method_id;
                $mainframe->Redirect($link, $msg);
            }
            if ($shipping_rate_id == '' && $cart['free_shipping'] != 1)
            {
                $msg  = JText::_('COM_REDSHOP_SELECT_SHIP_METHOD');
                $link = 'index.php?option=' . $option . '&view=checkout&Itemid=' . $Itemid . '&users_info_id=' . $users_info_id . '&shipping_rate_id=' . $shipping_rate_id . '&payment_method_id=' . $payment_method_id;
                $mainframe->Redirect($link, $msg);
            }
        }
        if ($payment_method_id == '')
        {
            $msg  = JText::_('COM_REDSHOP_SELECT_PAYMENT_METHOD');
            $link = 'index.php?option=' . $option . '&view=checkout&Itemid=' . $Itemid . '&users_info_id=' . $users_info_id . '&shipping_rate_id=' . $shipping_rate_id . '&payment_method_id=' . $payment_method_id;
            $mainframe->Redirect($link, $msg);
        }

        $paymentinfo     = $order_functions->getPaymentMethodInfo($payment_method_id);
        $paymentinfo     = $paymentinfo[0];
        $paymentparams   = new JRegistry($paymentinfo->params);
        $is_creditcard   = $paymentparams->get('is_creditcard', '');
        $is_subscription = $paymentparams->get('is_subscription', 0);

        if (@$is_creditcard == 1)
        {
            JHTML::Script('credit_card.js', 'components/com_redshop/assets/js/', false);
        }

        if ($is_subscription)
        {

            $session->set('subscription_id', $subscription_id);
        }

        $this->assignRef('cart', $cart);
        $this->assignRef('users_info_id', $users_info_id);
        $this->assignRef('shipping_rate_id', $shipping_rate_id);
        $this->assignRef('payment_method_id', $payment_method_id);
        $this->assignRef('is_creditcard', $is_creditcard);

        if ($task != '')
        {
            $tpl = $task;
        }

        parent::display($tpl);
    }
}

