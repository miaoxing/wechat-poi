<?php

namespace Miaoxing\WechatPoi\Service;

use miaoxing\plugin\BaseService;
use Miaoxing\Plugin\Constant;
use Miaoxing\Shop\Service\Shop;
use Wei\RetTrait;

class WechatPoi extends BaseService
{
    use RetTrait;
    use Constant;

    const AVAILABLE_STATE_NONE = 0;

    const AVAILABLE_STATE_SYS_ERR = 1;

    const AVAILABLE_STATE_AUDITING = 2;

    const AVAILABLE_STATE_AUDIT_SUCC = 3;

    const AVAILABLE_STATE_AUDIT_FAIL = 4;

    const OFFSET_TYPE_MARS = 1;

    const OFFSET_TYPE_SOGOU = 2;

    const OFFSET_TYPE_BAIDU = 3;

    const OFFSET_TYPE_MAPBAR = 4;

    const OFFSET_TYPE_GPS = 5;

    const OFFSET_TYPE_MERCATOR = 6;

    protected $availableStateTable = [
        self::AVAILABLE_STATE_NONE => [
            'text' => '未提交',
        ],
        self::AVAILABLE_STATE_SYS_ERR => [
            'text' => '微信系统错误',
        ],
        self::AVAILABLE_STATE_AUDITING => [
            'text' => '审核中',
        ],
        self::AVAILABLE_STATE_AUDIT_SUCC => [
            'text' => '审核通过',
        ],
        self::AVAILABLE_STATE_AUDIT_FAIL => [
            'text' => '审核驳回',
        ]
    ];

    public function addShop(Shop $shop)
    {
        $api = wei()->wechatAccount()->getCurrentAccount()->createApiService();

        $ret = $api->addPoi($this->getAddShopData($shop));
        if ($ret['code'] === 1) {
            $shop['wechat_poi_id'] = $ret['poi_id'];
            $shop['available_state'] = static::AVAILABLE_STATE_AUDITING;
        }

        return $ret;
    }

    public function updateShop(Shop $shop)
    {
        $api = wei()->wechatAccount()->getCurrentAccount()->createApiService();

        $data = $this->getUpdateShopData($shop);
        $data['business']['base_info']['poi_id'] = $shop['wechat_poi_id'];

        return $api->updatePoi($data);
    }

    public function addOrUpdateShop(Shop $shop)
    {
        if ($shop['wechat_poi_id']) {
            return wei()->wechatPoi->updateShop($shop);
        }

        return wei()->wechatPoi->addShop($shop);
    }

    public function delShop(Shop $shop)
    {
        if (!$shop['wechat_poi_id']) {
            return $this->suc();
        }

        $api = wei()->wechatAccount()->getCurrentAccount()->createApiService();

        return $api->delpoi([
            'poi_id' => $shop['wechat_poi_id'],
        ]);
    }

    protected function getUpdateShopData(Shop $shop)
    {
        $photoList = [];
        foreach ($shop['photo_list'] as $photo) {
            $photo['photo_url'] =  wei()->wechatMedia->updateUrlToWechatUrl($photoList['photo_url']);
            $photoList[] = $photo;
        }

        return [
            'business' => [
                'base_info' => [
                    'sid' => $shop['id'],
                    'telephone' => $shop['phone'],
                    'photo_list' => $photoList,
                    'recommend' => $shop['recommend'],
                    'special' => $shop['special'],
                    'introduction' => $shop['introduction'],
                    'open_time' => $shop['open_time'],
                    'avg_price' => $shop['avg_price'],
                ]
            ]
        ];
    }

    protected function getAddShopData(Shop $shop)
    {
        $data = $this->getUpdateShopData($shop);

        $data['business']['base_info'] += [
            'business_name' => $shop['name'],
            'branch_name' => $shop['branchName'],
            'province' => $shop['province'],
            'city' => $shop['city'],
            'district' => '', // TODO $shop['district'],
            'address' => $shop['address'],
            'categories' => $shop['categories'],
            'offset_type' => $shop['offset_type'],
            'longitude' => $shop['lng'],
            'latitude' => $shop['lat'],
        ];

        return $data;
    }
}
