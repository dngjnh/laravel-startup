<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Api\ApiController as Controller;
use App\Http\Requests\Api\V1\UserStoreRequest;
use App\Http\Requests\Api\V1\UserUpdateRequest;
use App\Models\User;
use Exception;
use Dingo\Api\Exception\StoreResourceFailedException;
use Dingo\Api\Exception\UpdateResourceFailedException;
use Dingo\Api\Exception\DeleteResourceFailedException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api-combined', ['except' => [
            //
        ]]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('index', User::class);

        $users = User::forList(
            [],
            [
                'users.id',
                'users.name',
                'users.email',
                'users.created_at',
                'users.updated_at',
            ]
        );

        return $this->response->array($users->toArray());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\Api\V1\UserStoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserStoreRequest $request)
    {
        $this->authorize('store', User::class);

        DB::beginTransaction();
        try {

            // 创建用户
            $data = $request->except(['roles']);
            $data['password'] = bcrypt($data['password']);
            $user = User::create($data);

            // 更新角色
            $user->syncRolesWithChecking($request->input('roles'));

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw new StoreResourceFailedException($e->getMessage());
        }

        return $this->response->created();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::forList(
            $id,
            [
                'users.id',
                'users.name',
                'users.email',
                'users.created_at',
                'users.updated_at',
            ],
            [],
            [
                'roles',
            ]
        )->first();

        $this->authorize('show', $user);

        return $this->response->array($user->toArray());
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\Api\V1\UserUpdateRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserUpdateRequest $request, $id)
    {
        $user = User::find($id);

        $this->authorize('update', $user);

        DB::beginTransaction();
        try {

            // 更新用户
            $data = $request->input();
            $data['password'] = bcrypt($data['password']);
            $user->update($data);

            // 更新角色
            $user->syncRolesWithChecking($request->input('roles'));

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw new UpdateResourceFailedException($e->getMessage());
        }

        return $this->response->noContent();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);

        $this->authorize('destroy', $user);

        DB::beginTransaction();
        try {

            $user->delete();

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw new DeleteResourceFailedException($e->getMessage());
        }

        return $this->response->noContent();
    }
}
