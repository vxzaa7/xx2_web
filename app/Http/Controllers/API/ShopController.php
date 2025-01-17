<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Model\Image;
use App\Model\item_id;
use App\Model\shop;
use App\Model\shopFeedback;
use App\Model\shopFeedbackItem;
use App\Model\shopItemList;
use App\Model\shopLog;
use App\Model\shopSendItemLog;
use App\Model\shopUserDepot;
use DB;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Carbon\Carbon;
class ShopController extends Controller
{
    public function index(Request $request)
    {
        if ($request->type == 'login') {
            $result = ShopController::login($request);
            return $result;
        } elseif ($request->type == 'item_desc') {
            $result = ShopController::item_desc($request);
            return $result;
        } elseif ($request->type == 'buy_item') {
            $result = ShopController::buy_item($request);
            return $result;
        } elseif ($request->type == 'get_item') {
            $result = ShopController::get_item($request);
            return $result;
        };
    }
    public function login($request)
    {
        // 消費回饋若有開啟,先進行判斷
        $now =Carbon::now();
        $check_feedback = shopFeedback::where('status', 1)->where('start', '<', $now)->where('end', '>', $now)->first();
        if ($check_feedback && $now > $check_feedback->start && $now < $check_feedback->end) {
            if (isset($_COOKIE['StrID'])) {
                $spend = shopLog::where('user_id', $_COOKIE['StrID'])->whereBetween('created_at', [$check_feedback->start, $check_feedback->end])->sum('total_price');
            }
            $feedback = $check_feedback;
            $shopFeedbackItem = shopFeedbackItem::select('price', DB::raw('GROUP_CONCAT(item_name) as item_names'))
            ->where('feedback_id',$check_feedback->id)
                ->groupBy('price')
                ->get();
            foreach ($shopFeedbackItem as $key => $value) {
                $reset_feedback_item = explode(',', $value['item_names']);
                $shopFeedbackItem[$key]['item_names'] = $reset_feedback_item;
            }
            $feedback['item'] = $shopFeedbackItem;
        } else {
            $feedback = false;
        }
        if(!isset($spend)){
            $spend  = 0;
        }
        // 找出上架商品
                if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $real_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } else {
            $real_ip = $_SERVER["REMOTE_ADDR"];
        }
        if ($real_ip != '211.23.144.219') {
            $shop = shop::where('status', 1)->where(function ($query) use ($now) {
            $query->whereNull('limit_start')
                ->whereNull('limit_end')
                ->orWhere(function ($query) use ($now) {
                    $query->whereNotNull('limit_start')
                        ->whereNotNull('limit_end')
                        ->where('limit_start', '<=', $now)
                        ->where('limit_end', '>=', $now);
                    }); 
                })->orderby('sort','desc')->get();
            }else{
            $shop = shop::where(function ($query) use ($now) {
            $query->whereNull('limit_start')
                ->whereNull('limit_end')
                ->orWhere(function ($query) use ($now) {
                    $query->whereNotNull('limit_start')
                        ->whereNotNull('limit_end')
                        ->where('limit_start', '<=', $now)
                        ->where('limit_end', '>=', $now);
                    }); 
                })->orderby('sort','desc')->get();

        }
        // 找出banner
        $banner = Image::where('type', 'shop')->orderBy('status', 'desc')->orderBy('sort', 'asc')->get();
        if (!isset($_COOKIE['StrID'])) {
            return response()->json([
                'status' => -99,
                'item' => $shop,
                'buy_list' => false,
                'char' => false,
                'point' => 0,
                'msg' => '未登入',
                'banner' => $banner,
                'feedback' => $feedback,
                'spend' => 0,
            ]);
        } else {

            $depot = shopUserDepot::where('user_id', $_COOKIE['StrID'])->where('count', '>', 0)->get();
            $client = new Client();
            $data = [
                'user_id' => $_COOKIE['StrID'],
            ];

            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            $res = $client->request('POST', 'https://webapi.digeam.com/xx2/get_point', [
                'headers' => $headers,
                'json' => $data,
            ]);
            $result = $res->getBody();
            $point = json_decode($result);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://xx2.digeam.com/api/service_api?type=getinfo&account=" . $_COOKIE['StrID']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if ($result->status == 0) {
                $info = $result->account_info;
                $uid = $info->uid;
                //找角色名單
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://xx2.digeam.com/api/service_api?accid=" . $uid . "&zoneid=" . 1801 . "&type=getcharlist_3party");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $result2 = curl_exec($ch);
                curl_close($ch);
                $result2 = json_decode($result2);
                if ($result2->status == 0) {
                    return response()->json([
                        'status' => 1,
                        'msg' => $result2->msg,
                        'char_list' => $result2->role_info,
                        'buy_list' => $depot,
                        'item' => $shop,
                        'point' => $point,
                        'msg' => '已登入',
                        'banner' => $banner,
                        'feedback' => $feedback,
                        'spend' => $spend,
                    ]);
                } else {
                    return response()->json([
                        'status' => 1,
                        'msg' => $result2->msg,
                        'char_list' => [],
                        'buy_list' => $depot,
                        'item' => $shop,
                        'point' => $point,
                        'msg' => '已登入',
                        'banner' => $banner,
                        'feedback' => $feedback,
                        'spend' => $spend,
                    ]);
                }
            } else {
                return response()->json([
                    'status' => 1,
                    'msg' => $result->msg,
                    'char_list' => [],
                    'buy_list' => $depot,
                    'item' => $shop,
                    'point' => $point,
                    'msg' => '已登入',
                    'banner' => $banner,
                    'feedback' => $feedback,
                    'spend' => $spend,
                ]);
            }
        }

    }
    public function item_desc($request)
    {
        $shop = shop::where('id', $request->item_id)->first();
        if ($shop) {
            return response()->json([
                'status' => 1,
                'item_info' => $shop,
                'msg' => '道具資訊讀取成功',
            ]);
        } else {
            return response()->json([
                'status' => -99,
                'msg' => '道具資訊讀取失敗',
            ]);
        }
    }
    public function buy_item($request)
    {
        $now =Carbon::now();
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $real_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } else {
            $real_ip = $_SERVER["REMOTE_ADDR"];
        }
        // 沒登入
        if (!isset($_COOKIE['StrID'])) {
            return response()->json([
                'status' => -99,
                'msg' => '請先登入',
            ]);
        }
        $shop_item = shop::where('id', $request->item_id)->first();
        // limit_type == 1 為全服限購 2為帳號限購 3 為區間內帳號限購 4為區間內伺服器限購
        if ($shop_item->limit_type != 0) {
            if ($shop_item->limit_type == 1) {
                $check = shopLog::where('item_id', $request->item_id)->count();
                if ($check >= $shop_item->limit_count) {
                    return response()->json([
                        'status' => -97,
                        'msg' => '限購道具數量不足',
                    ]);
                }
            } else if ($shop_item->limit_type == 2) {
                $check = shopLog::where('user_id', $_COOKIE['StrID'])->where('item_id', $request->item_id)->count();
                if ($check >= $shop_item->limit_count) {
                    return response()->json([
                        'status' => -97,
                        'msg' => '限購道具數量不足',
                    ]);
                }
            } else if ($shop_item->limit_type == 3) {
                $check = shopLog::where('item_id', $request->item_id)->whereBetween('created_at', [$shop_item->limit_start, $shop_item->limit_end])->count();
                if ($check >= $shop_item->limit_count) {
                    return response()->json([
                        'status' => -97,
                        'msg' => '限購道具數量不足',
                    ]);
                }else if($now <  $shop_item->limit_start || $now > $shop_item->limit_end){
                    return response()->json([
                        'status' => -96,
                        'msg' => '不在購買時間內',
                    ]);
                }
            } else if ($shop_item->limit_type == 4) {
                $check = shopLog::where('user_id', $_COOKIE['StrID'])->where('item_id', $request->item_id)->whereBetween('created_at', [$shop_item->limit_start, $shop_item->limit_end])->count();
                if ($check >= $shop_item->limit_count) {
                    return response()->json([
                        'status' => -97,
                        'msg' => '限購道具數量不足',
                    ]);
                }else if($now <  $shop_item->limit_start || $now > $shop_item->limit_end){
                    return response()->json([
                        'status' => -96,
                        'msg' => '不在購買時間內',
                    ]);
                }
            }
        }
        // 有登入,打api確認款項和扣款
        $client = new Client();
        $data = [
            'user_id' => $_COOKIE['StrID'],
            'price' => $shop_item->price,
            'count' => $request->count,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $res = $client->request('POST', 'https://webapi.digeam.com/xx2/shop_buy_item', [
            'headers' => $headers,
            'json' => $data,
        ]);
        $result = $res->getBody();
        $result = json_decode($result);
        // 錢不夠
        if ($result->status == -98) {
            return response()->json([
                'status' => -98,
                'msg' => '點數不足',
            ]);
        } else {
            // 判斷是否為抽包
            if ($shop_item->item_type == 3) {
                $get_present_item = shopItemList::where('shop_id', $shop_item->id)->get();
                $min = 0;
                $max = 100;
                for ($z = 0; $z < $request->count; $z++) {
                    for ($i = 1; $i <= 10000; $i++) {
                        $target = $min + mt_rand() / mt_getrandmax() * ($max - $min);
                    }
                    $item_probability_count = 0;
                    foreach ($get_present_item as $key => $value) {
                        $item_probability_count += $value['percentage'];
                        if ($item_probability_count > $target) {
                            $get = $value;
                            break;
                        };
                    }
                    // 記錄抽包結果,並且寫入倉庫
                    $newLog = new shopLog();
                    $newLog->user_id = $_COOKIE['StrID'];
                    $newLog->ip = $real_ip;
                    $newLog->item_id = $request->item_id . '-' . $get['id'];
                    $newLog->item_name = $shop_item->title . '-' . $get['item_name'];
                    $newLog->count = 1;
                    $newLog->item_price = $shop_item->price;
                    $newLog->total_price = $shop_item->price;
                    $newLog->user_xx2_origin_point = $result->user_xx2_origin_point;
                    $newLog->user_xx2_origin_b_point = $result->user_xx2_origin_b_point;
                    $newLog->user_xx2_update_point = $result->user_xx2_update_point;
                    $newLog->user_xx2_update_b_point = $result->user_xx2_update_b_point;
                    $newLog->save();

                    //找尋玩家倉庫是否有該道具
                    $search_depot = shopUserDepot::where('user_id', $_COOKIE['StrID'])->where('item_id', $request->item_id . '-' . $get['id'])->where('reason', $shop_item->title . '-' . $get['item_name'])->first();
                    if ($search_depot) {
                        $search_depot->count += 1;
                        $search_depot->save();
                    } else {
                        $new_depot_item = new shopUserDepot();
                        $new_depot_item->user_id = $_COOKIE['StrID'];
                        $new_depot_item->count = 1;
                        $new_depot_item->item_id = $request->item_id . '-' . $get['id'];
                        $new_depot_item->item_name = $shop_item->title . '-' . $get['item_name'];
                        $new_depot_item->reason = $shop_item->title . '-' . $get['item_name'];
                        $new_depot_item->type = 'shop';
                        $new_depot_item->save();
                    }
                }
                $result = ShopController::send_perchase($shop_item->price,$request->count,$shop_item->title);
                $result = ShopController::feedback($request);
                return response()->json([
                    'status' => 1,
                    'msg' => '購買成功',
                ]);
            }
            // 寫購買紀錄
            $newLog = new shopLog();
            $newLog->user_id = $_COOKIE['StrID'];
            $newLog->ip = $real_ip;
            $newLog->item_id = $request->item_id;
            $newLog->item_name = $shop_item->title;
            $newLog->count = $request->count;
            $newLog->item_price = $shop_item->price;
            $newLog->total_price = $result->total_item_price;
            $newLog->user_xx2_origin_point = $result->user_xx2_origin_point;
            $newLog->user_xx2_origin_b_point = $result->user_xx2_origin_b_point;
            $newLog->user_xx2_update_point = $result->user_xx2_update_point;
            $newLog->user_xx2_update_b_point = $result->user_xx2_update_b_point;
            $newLog->save();

            //找尋玩家倉庫是否有該道具
            $search_depot = shopUserDepot::where('user_id', $_COOKIE['StrID'])->where('item_id', $request->item_id)->where('reason', '商城購買')->first();
            if ($search_depot) {
                $search_depot->count += $request->count;
                $search_depot->save();
            } else {
                $new_depot_item = new shopUserDepot();
                $new_depot_item->user_id = $_COOKIE['StrID'];
                $new_depot_item->count = $request->count;
                $new_depot_item->item_id = $request->item_id;
                $new_depot_item->item_name = $shop_item->title;
                $new_depot_item->reason = '商城購買';
                $new_depot_item->type = 'shop';
                $new_depot_item->save();
            }
            $result = ShopController::send_perchase($shop_item->price,$request->count,$shop_item->title);
            $result = ShopController::feedback($request);
            return response()->json([
                'status' => 1,
                'msg' => '購買成功',
            ]);
        }
    }
    public function get_item($request)
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $real_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } else {
            $real_ip = $_SERVER["REMOTE_ADDR"];
        }
        if (!isset($_COOKIE['StrID'])) {
            return response()->json([
                'status' => -99,
                'msg' => '未登入',
            ]);
        }
        $check = shopUserDepot::where('user_id', $_COOKIE['StrID'])->where('id', $request->item_id)->first();
        if (!$check) {
            return response()->json([
                'status' => -97,
                'msg' => '玩家沒有獲得此道具',
            ]);
        }
        if ($check->count <= 0) {
            return response()->json([
                'status' => -96,
                'msg' => '此道具在資料庫剩餘數量小於或等於0',
            ]);
        }

        if ($check->count - $request->count < 0) {
            return response()->json([
                'status' => -98,
                'msg' => '倉庫剩餘道具量不足',
            ]);
        }
        $check_item_type = explode('-', $check->item_id);
        if (isset($check_item_type[1])) {
            $send = shopItemList::where('id', $check_item_type[1])->get();
        } else {
            if ($check->type == 'shop') {
                $send = shopItemList::where('shop_id', $check->item_id)->get();
            } else {
                $send = shopFeedbackItem::where('id', $check->item_id)->get();
            }
        }
        // 找uid
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://xx2.digeam.com/api/service_api?type=getinfo&account=" . $_COOKIE['StrID']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if ($result->status == 0) {
            $info = $result->account_info;
            $uid = $info->uid;
        }
        // 找角色
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://xx2.digeam.com/api/service_api?accid=" . $uid . "&zoneid=" . 1801 . "&type=getcharlist_3party");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result_2 = curl_exec($ch);
        curl_close($ch);
        $result_2 = json_decode($result_2);
        foreach ($result_2->role_info as $value) {
            if ($value->charid == $request->char_id) {
                $char_name = $value->name;
                break;
            }
        }
        // 確認商品是否為禮包,並派獎
        foreach ($send as $key =>$value) {
            $ch = curl_init();
            $url = "https://xx2.digeam.com/api/service_api?type=athena_email&uid=" . $uid
            . "&zoneid=" . 1801 . "&charid=" . $request->char_id . "&content=" . '您於商城購買的道具已送達,請盡速領取！' . "&title=" . '網頁商城購買道具' . "&name=" . $char_name . "&itemid=" . $value['item_code'] . "&itemnum=" . $value['item_cnt'] * $request->count . "&isbind=" . $value['is_bind'];
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result_3 = curl_exec($ch);
            curl_close($ch);
            $result_3 = json_decode($result_3);
            $status = $result_3->status;
            if ($status == 0) {
                if($key == 0){
                    $check->count -= $request->count;
                    $check->save();
                }

                $newLog = new shopSendItemLog();
                $newLog->user_id = $_COOKIE['StrID'];
                $newLog->ip = $real_ip;
                $newLog->count = $request->count;
                $newLog->item_code = $value['item_code'];
                $newLog->item_id = $request->item_id;
                $newLog->item_name = $check->item_name;
                $newLog->server_id = 1801;
                $newLog->char_id = $request->char_id;
                $newLog->char_name = $char_name;
                $newLog->save();
            } else {
                return response()->json([
                    'status' => -95,
                    'msg' => '發送失敗',
                ]);
            }
        }
        return response()->json([
            'status' => 1,
            'msg' => '發送成功',
        ]);
    }
    public function feedback($request)
    {
        // 消費回饋
        $now = Carbon::now();
        $check_feedback = shopFeedback::where('status', 1)->where('start', '<', $now)->where('end', '>', $now)->first();
        if ($check_feedback && $now > $check_feedback->start && $now < $check_feedback->end) {
            if (isset($_COOKIE['StrID'])) {
                $spend = shopLog::where('user_id', $_COOKIE['StrID'])->whereBetween('created_at', [$check_feedback->start, $check_feedback->end])->sum('total_price');
                // 依照金額替消費回饋增加項目,直到消費總額小於回饋金額
                $feedBackItem = shopFeedbackItem::where('feedback_id', $check_feedback->id)->orderBy('price', 'desc')->get();
                foreach ($feedBackItem as $value) {
                    if ($value['price'] <= $spend) {
                        $check_feed_back_item = shopUserDepot::where('user_id', $_COOKIE['StrID'])->where('item_id', $value['id'])->where('reason', $check_feedback->title . '-' . $value['item_name'])->where('type', 'feedback')->first();
                        if (!$check_feed_back_item) {
                            $new_depot_item = new shopUserDepot();
                            $new_depot_item->user_id = $_COOKIE['StrID'];
                            $new_depot_item->count = 1;
                            $new_depot_item->item_id = $value['id'];
                            $new_depot_item->item_name = $value['item_name'];
                            $new_depot_item->reason = $check_feedback->title . '-' . $value['item_name'];
                            $new_depot_item->type = 'feedback';
                            $new_depot_item->save();
                        }
                    }
                }
            }
        }
    }
    public function send_perchase($item_price,$count,$item_name){
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $real_ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
        } else {
            $real_ip = $_SERVER["REMOTE_ADDR"];
        }

        $client = new Client();
        $data = [
            'user_id' => $_COOKIE['StrID'],
            'price' => $item_price,
            'count' => $count,
            'item_name' => $item_name,
            'ip'=>$real_ip,
        ];

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        $res = $client->request('POST', 'https://webapi.digeam.com/xx2/add_perchase', [
            'headers' => $headers,
            'json' => $data,
        ]);
    }
}
