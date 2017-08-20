<?php

namespace Miaoxing\WechatPoi;

use miaoxing\plugin\BasePlugin;
use Miaoxing\Shop\Service\Shop;
use Miaoxing\WechatPoi\Service\WechatPoi;
use Wei\Request;
use Wei\WeChatApp;

class Plugin extends BasePlugin
{
    /**
     * {@inheritdoc}
     */
    protected $name = '微信地址';

    /**
     * {@inheritdoc}
     */
    protected $description = '微信门店接口';

    public function onPostAdminShopListFind(Request $req, array &$data)
    {
        foreach ($data as &$row) {
            $row['available_state_text'] = wei()->wechatPoi->getConstantValue('available_state', $row['available_state'], 'text');
        }
    }

    public function onAdminShopList()
    {
        $this->display();
    }

    public function onAdminShopListContent()
    {
        $this->display();
    }

    public function onAdminShopsEdit()
    {
        $this->display();
    }

    public function onPostShopSave(Shop $shop)
    {
        $shop['offset_type'] = WechatPoi::OFFSET_TYPE_BAIDU;

        $ret = wei()->wechatPoi->addOrUpdateShop($shop);

        if ($ret['code'] !== 1) {
            $ret['message'] = '保存成功，但同步微信失败：' . $ret['message'];
            return $ret;
        }

        $shop->save();
    }

    public function onPreShopDestroy(Shop $shop)
    {
        $ret = wei()->wechatPoi->delShop($shop);
        if ($ret['code'] !== 1) {
            $ret['message'] = '删除失败，微信返回：' . $ret['message'];
            return $ret;
        }
    }

    public function onWechatPoiCheckNotify(WeChatApp $app, $user, $account)
    {
        /** @var Shop $shop */
        $shop = wei()->shop()->find(['poi_id' => $app->getAttr('PoiId')]);
        if (!$shop) {
            $this->logger->info('Unknown poi id', $app->getAttrs());
        }

        if ($app->getAttr('Result') === 'fail') {
            $shop['available_state'] = WechatPoi::AVAILABLE_STATE_AUDIT_FAIL;
        } else {
            $shop['available_state'] = WechatPoi::AVAILABLE_STATE_AUDIT_SUCC;
        }

        $shop['result_message'] = $app->getAttr('msg');

        $shop->save();
    }
}
