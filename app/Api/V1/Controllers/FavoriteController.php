<?php

namespace App\Http\Controllers;

use Config;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use App\Favorite;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;


class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
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
        $favorite = Favorite::find($id);

        $folderPath = public_path() . "/song/$favorite->song_id";
        File::deleteDirectory($folderPath);

        $favorite->song->delete();
        $favorite->delete();

        return "OK";
    }
}
