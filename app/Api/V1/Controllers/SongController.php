<?php

namespace App\Api\V1\Controllers;

use Auth;
use Config;
use App\User;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\SignUpRequest;
use App\Favorite;
use App\Song;
use Exception;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;

class SongController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $file = $request->file('file');
        $song = new Song();
        $song->title = basename($file->getClientOriginalName(), '.mp3');
        $song->save();

        $songFolderPath = public_path() . "/song/$song->id";
        if (!File::exists($songFolderPath)) {
            File::makeDirectory($songFolderPath, $mode = 0777, true, false);
        }

        $oldPath = $file->getRealPath();
        $newPath = "$songFolderPath/$song->id.mp3";
        File::move($oldPath, $newPath);

        $mp3Info = new \wapmorgan\Mp3Info\Mp3Info($newPath, true);
        $song->duration = round($mp3Info->duration);


        ini_set('max_execution_time', 180); //3 minutes
        $cmd = "python -m spleeter separate -i \"$newPath\" -o \"$songFolderPath/output\" -p spleeter:2stems";
        exec($cmd);

        $cmd = "ffmpeg -i \"$songFolderPath/output/$song->id/accompaniment.wav\" -vn -ar 44100 -ac 2 -b:a 192k \"$songFolderPath/output/$song->id/accompaniment.mp3\"";
        exec($cmd);

        $cmd = "ffmpeg -i \"$songFolderPath/output/$song->id/vocals.wav\" -vn -ar 44100 -ac 2 -b:a 192k \"$songFolderPath/output/$song->id/vocals.mp3\"";
        exec($cmd);

        $song->accompaniment_path = "/song/$song->id/output/$song->id/accompaniment.mp3";
        $song->vocals_path = "/song/$song->id/output/$song->id/vocals.mp3";
        $song->save();

        $user = Auth::guard()->user();

        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->song_id = $song->id;
        $favorite->save();

        return response()->json($song);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
