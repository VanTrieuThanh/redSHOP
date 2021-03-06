<?php
/**
 * @package     RedSHOP.Backend
 * @subpackage  Model
 *
 * @copyright   Copyright (C) 2008 - 2020 redCOMPONENT.com. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('_JEXEC') or die;


class RedshopModelUser_detail extends RedshopModel
{
    public $_id = null;

    public $_uid = null;

    public $_data = null;

    public $_table_prefix = null;

    public $_pagination = null;

    public $_copydata = null;

    public $_context = null;

    public function __construct()
    {
        parent::__construct();
        $app = JFactory::getApplication();

        $this->_table_prefix = '#__redshop_';
        $this->_context      = 'order_id';

        $array      = $app->input->get('cid', 0, 'array');
        $this->_uid = $app->input->get('user_id', 0);

        $limit      = $app->getUserStateFromRequest($this->_context . 'limit', 'limit', $app->getCfg('list_limit'), 0);
        $limitstart = $app->getUserStateFromRequest($this->_context . 'limitstart', 'limitstart', 0);

        $this->setState('limit', $limit);
        $this->setState('limitstart', $limitstart);
        $this->setId((int)$array[0]);
    }

    public function setId($id)
    {
        $this->_id   = $id;
        $this->_data = null;
    }

    public function &getData()
    {
        if ($this->_loadData()) {
        } else {
            $this->_initData();
        }

        return $this->_data;
    }

    public function _loadData()
    {
        if (empty($this->_data)) {
            $this->_uid = 0;

            $queryId = $this->_id;
            $user    = \Redshop\User\Helper::getUsers([], ['ui.users_info_id' => ['=' => $queryId]]);

            $shipping = \JFactory::getApplication()->input->getInt('shipping', 0);

            if ($shipping == 1) {
                $queryId = (int)\JFactory::getApplication()->input->get('cid', [0])[0];

                $user = \Redshop\User\Helper::getUsers(
                    [],
                    [
                        'ui.users_info_id' => ['=' => $queryId],
                        'ui.address_type'  => ['=' => 'ST']
                    ]
                );
            }

            $this->_data = $user[0] ?? null;

            if (isset($this->_data->user_id)) {
                $this->_uid = $this->_data->user_id;
            }

            if (!empty($this->_data) && !$this->_data->email) {
                $this->_data->email = $this->_data->user_email;
            }

            return (boolean)$this->_data;
        }

        return true;
    }

    public function _initData()
    {
        $data = JFactory::getApplication()->getUserState('com_redshop.user_detail.data');

        if (!empty($data)) {
            $this->_data = (object)$data;

            return (boolean)$this->_data;
        } elseif (empty($this->_data)) {
            $detail = new stdClass;

            $detail->users_info_id         = 0;
            $detail->user_id               = 0;
            $detail->id                    = 0;
            $detail->gid                   = null;
            $detail->name                  = null;
            $detail->username              = null;
            $detail->email                 = null;
            $detail->password              = null;
            $detail->usertype              = null;
            $detail->block                 = null;
            $detail->sendEmail             = null;
            $detail->registerDate          = null;
            $detail->lastvisitDate         = null;
            $detail->activation            = null;
            $detail->is_company            = null;
            $detail->firstname             = null;
            $detail->lastname              = null;
            $detail->contact_info          = null;
            $detail->address_type          = null;
            $detail->company_name          = null;
            $detail->vat_number            = null;
            $detail->tax_exempt            = 0;
            $detail->country_code          = null;
            $detail->state_code            = null;
            $detail->shopper_group_id      = null;
            $detail->published             = 1;
            $detail->address               = null;
            $detail->city                  = null;
            $detail->zipcode               = null;
            $detail->phone                 = null;
            $detail->requesting_tax_exempt = 0;
            $detail->tax_exempt_approved   = 0;
            $detail->approved              = 1;
            $detail->ean_number            = null;
            $detail->state_code_ST         = null;

            $input      = JFactory::getApplication()->input;
            $userInfoId = $input->get('info_id', 0);
            $shipping   = $input->get('shipping', 0);

            if ($shipping) {
                $temp     = \Redshop\User\Helper::getUsers(
                    [],
                    ['ui.users_info_id' => ['=' => $this->_id ?? (int)$userInfoId]]
                );
                $billData = $temp[0] ?? null;

                if (isset($billData)) {
                    $detail->id                    = $detail->user_id = $this->_uid = $billData->user_id;
                    $detail->email                 = $billData->user_email;
                    $detail->is_company            = $billData->is_company;
                    $detail->company_name          = $billData->company_name;
                    $detail->vat_number            = $billData->vat_number;
                    $detail->tax_exempt            = $billData->tax_exempt;
                    $detail->shopper_group_id      = $billData->shopper_group_id;
                    $detail->requesting_tax_exempt = $billData->requesting_tax_exempt;
                    $detail->tax_exempt_approved   = $billData->tax_exempt_approved;
                    $detail->ean_number            = $billData->ean_number;
                }
            }

            $this->_data = $detail;

            return (boolean)$this->_data;
        }

        return true;
    }

    public function storeUser($post)
    {
        $post['createaccount'] = (isset($post['username']) && $post['username'] != "") ? 1 : 0;
        $post['user_email']    = $post['email1'] = $post['email'];

        JFactory::getApplication()->input->post->set('password1', $post['password']);

        $post['billisship'] = 1;

        if ($post['createaccount']) {
            if ($post['user_id'] == 0 && ($post['password'] == '' || $post['password2'] == '')) {
                /** @scrutinizer ignore-deprecated */
                JError::raiseWarning('', JText::_('COM_REDSHOP_PLEASE_ENTER_PASSWORD'));

                return false;
            }

            $joomlaUser = RedshopHelperJoomla::createJoomlaUser($post);
        } else {
            $joomlaUser = RedshopHelperJoomla::updateJoomlaUser($post);
        }

        if (!$joomlaUser) {
            return false;
        }

        $redUser = RedshopHelperUser::storeRedshopUser($post, $joomlaUser->id, 1);

        return $redUser;
    }

    public function store($post)
    {
        $shipping              = isset($post["shipping"]) ? true : false;
        $post['createaccount'] = (isset($post['username']) && $post['username'] != "") ? 1 : 0;
        $post['user_email']    = $post['email1'] = $post['email'];

        if ($shipping) {
            $post['country_code_ST'] = $post['country_code'];
            $post['state_code_ST']   = $post['state_code'];
            $post['firstname_ST']    = $post['firstname'];
            $post['lastname_ST']     = $post['lastname'];
            $post['address_ST']      = $post['address'];
            $post['city_ST']         = $post['city'];
            $post['zipcode_ST']      = $post['zipcode'];
            $post['phone_ST']        = $post['phone'];

            if ($post['user_id'] == 0) {
                $userInfoId      = $post['user_info_id'];
                $user            = \Redshop\User\Helper::getUsers([], ['ui.users_info_id' => ['=' => $userInfoId]])[0];
                $post['user_id'] = $user->user_id;
            }

            $redUser = RedshopHelperUser::storeRedshopUserShipping($post);
        } else {
            $post['billisship'] = 1;
            $joomlaUser         = RedshopHelperJoomla::updateJoomlaUser($post);

            if (!$joomlaUser) {
                return false;
            }

            $redUser = RedshopHelperUser::storeRedshopUser($post, $joomlaUser->id, 1);
        }

        return $redUser;
    }

    /**
     * Delete redSHOP and Joomla! users
     *
     * @param   array  $cid                Array of user ids
     * @param   bool   $deleteJoomlaUsers  Delete Joomla! users
     *
     * @return boolean
     *
     * @since version
     */
    public function delete($cid = array(), $deleteJoomlaUsers = false)
    {
        if (count($cid)) {
            $db   = JFactory::getDbo();
            $cids = implode(',', $cid);

            $queryDefault = $db->getQuery(true)
                ->delete($db->qn('#__redshop_users_info'))
                ->where($db->qn('users_info_id') . ' IN (' . $cids . ' )');

            if ($deleteJoomlaUsers) {
                $queryAllUserIds = $db->getQuery(true)
                    ->select($db->qn('id'))
                    ->from($db->qn('#__users'));
                $allUserIds      = $db->setQuery($queryAllUserIds)->loadColumn();

                $queryCustom = $db->getQuery(true)
                    ->select($db->qn('user_id'))
                    ->from($db->qn('#__redshop_users_info'))
                    ->where($db->qn('users_info_id') . ' IN (' . $cids . ' )')
                    ->where($db->qn('user_id') . ' IN (' . implode(',', $allUserIds) . ' )')
                    ->group($db->qn('user_id'));

                $joomlaUserIds = $db->setQuery($queryCustom)->loadColumn();

                foreach ($joomlaUserIds as $joomlaUserId) {
                    $joomlaUser = JFactory::getUser($joomlaUserId);

                    // Skip this user whom in Super Administrator group.
                    if ($joomlaUser->authorise('core.admin')) {
                        continue;
                    }

                    $user = JFactory::getUser($joomlaUserId);

                    if ($user->guest) {
                        continue;
                    }

                    if (!$user->delete()) {
                        /** @scrutinizer ignore-deprecated */
                        $this->setError(/** @scrutinizer ignore-deprecated */ $user->getError());

                        return false;
                    }
                }
            }

            $db->setQuery($queryDefault);

            if (!$db->execute()) {
                /** @scrutinizer ignore-deprecated */
                $this->setError(/** @scrutinizer ignore-deprecated */ $db->getErrorMsg());

                return false;
            }
        }

        return true;
    }

    public function publish($cid = array(), $publish = 1)
    {
        if (count($cid)) {
            $cids = implode(',', $cid);

            $query = 'UPDATE ' . $this->_table_prefix . 'users_info '
                . 'SET approved=' . intval($publish) . ' '
                . 'WHERE user_id IN ( ' . $cids . ' ) ';
            $this->_db->setQuery($query);

            if (!$this->_db->execute()) {
                /** @scrutinizer ignore-deprecated */
                $this->setError(/** @scrutinizer ignore-deprecated */ $this->_db->getErrorMsg());

                return false;
            }
        }

        return true;
    }

    /**
     * @param $user
     * @param $uid
     *
     * @return int
     */
    public function validate_user($user, $uid)
    {
        return \Redshop\User\Helper::isUserExist($user, $uid);
    }

    /**
     * @param $email
     * @param $uid
     *
     * @return int
     */
    public function validate_email($email, $uid)
    {
        return \Redshop\User\Helper::isUserEmailExist($email, $uid);
    }

    public function userOrders()
    {
        $query = $this->buildUserOrderQuery();
        $list  = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit'));

        return $list;
    }

    /**
     * @return mixed
     */
    public function buildUserOrderQuery()
    {
        $db    = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*')
            ->from($db->qn('#__redshop_orders'))
            ->where($db->qn('user_id') . ' = ' . $db->q((int)$this->_uid))
            ->order($db->qn('order_id') . ' DESC');

        return $query;
    }

    public function getPagination()
    {
        if (empty($this->_pagination)) {
            jimport('joomla.html.pagination');
            $this->_pagination = new JPagination(
                $this->getTotal(),
                $this->getState('limitstart'),
                $this->getState('limit')
            );
        }

        return $this->_pagination;
    }

    public function getTotal()
    {
        if ($this->_id) {
            $query        = $this->buildUserOrderQuery();
            $this->_total = $this->_getListCount($query);

            return $this->_total;
        }
    }
}
