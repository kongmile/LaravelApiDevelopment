<?php

namespace App\Api\Controllers;

use App\Events\FriendRequestingCreated;
use App\Friend;
use Illuminate\Http\Request;
use App\FriendRequesting;
use Dingo\Api\Routing\Helpers;
use App\Events\FriendRequestingSent;
use App\User;
use App\Events\FriendRequestingAgreed;
use App\Transformer\FriendTransformer;
class FriendController extends BaseController
{
    use Helpers;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return $this->response->array([
            [
                'tags' => '我的好友',
                'members' => (new FriendTransformer())->transformCollection($this->auth->user()->friends->unique()->toArray())
            ]
        ]);
    }

    /**
     * 创建好友请求
     *
     * @return \Illuminate\Http\Response
     */
    public function createFriendRequesting(Request $request)
    {
        $FriendRequesting = new FriendRequesting();
        $FriendRequesting->from = $this->auth->user()->id;
        $FriendRequesting->to = $request->to;
        $FriendRequesting->message = $request->message;
        $FriendRequesting->save();
        broadcast(new FriendRequestingCreated($FriendRequesting));
        return $this->response->array([
            'message' => 'FriendRequesting sent',
            'socket' => $FriendRequesting
        ]);
    }

    public function agreeFriendRequesting(Request $request, $id)
    {
        $friendRquesting = FriendRequesting::where('id', $id)->first();
        if(!$friendRquesting) {
            return $this->response->errorNotFound($id);
        }
        if($friendRquesting->is_handled)
            return $this->response->errorForbidden('You have already handled');
        if($friendRquesting->to != $this->auth->user()->id) {
            return $this->response->errorForbidden('You do not have right to agree');
        }
        $friend1 = new Friend();
        $friend1->user1 = $friendRquesting->from;
        $friend1->user2 = $friendRquesting->to;
        $friend1->save();
        $friend2 = new Friend();
        $friend2->user2 = $friendRquesting->from;
        $friend2->user1 = $friendRquesting->to;
        $friend2->save();
        $friendRquesting->is_handled = true;
        $friendRquesting->is_agreed = true;
        $friendRquesting->is_read = true;
        $friendRquesting->save();
        event(new FriendRequestingAgreed($friendRquesting));
        return $this->response->array($friendRquesting->toUser);
    }

    public function ignoreFriendRequesting(Request $request, $id) {
        $friendRquesting = FriendRequesting::findOrFail($id);
        if(!$friendRquesting) {
            return $this->response->errorNotFound($id);
        }
        if($friendRquesting->is_handled)
            return $this->response->errorForbidden('You have already handled');
        if($friendRquesting->to != $this->auth->user()->id) {
            return $this->response->errorForbidden('You do not have right to ignore');
        }
        $friendRquesting->is_read = true;
        $friendRquesting->is_handled = true;
        $friendRquesting->is_agreed = false;
        $friendRquesting->save();
        return $this->response->array(['msg' => 'Ignored']);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return $this->response->array((new FriendTransformer())->transform(User::where('id', $id)->first()));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Friend::where('user1', $this->auth->user()->id)->where('user2', $id)->delete();
        Friend::where('user1', $this->auth->user()->id)->where('user2', $id)->delete();
        return $this->response->array(['msg' => 'Friend deleted']);
    }

    public function search(Request $request) {
        if($request->has('keyword')) {
            $users = User::where('name', 'like', '%'.$request->keyword.'%')->orWhere('email', $request->keyword)->get();
            return $this->response->array((new FriendTransformer())->transformCollection($users->toArray()));
        } else {
            return $this->response->error('请输入关键字',403);
        }
    }
}
