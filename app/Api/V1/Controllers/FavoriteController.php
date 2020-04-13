<?php

namespace App\Api\V1\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Favorite;
use App\Song;
use Illuminate\Support\Facades\File;


class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::guard()->user();
        $data = Favorite::with('song')->where('user_id', '=', $user->id)->get();
        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $search = new MusixmatchHelper();
        $track = $search->trackSearch($request->title, $request->artist)->formatTrackSearch()->result();
        $search = new MusixmatchHelper();
        $lyrics = $search->getLyrics($track->track_id)->formatLyricsGet()->result();

        $song = new Song();
        if ($track) {
            $song->title = $track->track_name;
            $song->artist = $track->artist_name;
            $song->album = $track->album_name;
            $song->genre = $track->genre;
            $song->lyrics = $lyrics;
        } else {
            $song->title = $request->title;
            $song->artist = $request->artist;
            $song->album = "<unknown>";
            $song->genre = "<unknown>";
            $song->lyrics = "<unknown>";
        }
        $song->save();

        // create dir if not exist yet
        $currentSongFolderPath = public_path() . "/song/$song->id";
        if (!File::exists($currentSongFolderPath)) {
            File::makeDirectory($currentSongFolderPath, $mode = 0777, true, false);
        }

        // move song to public folder
        $file = $request->file('file');
        $oldPath = $file->getRealPath();
        $newPath = "$currentSongFolderPath/$song->id.mp3";
        File::move($oldPath, $newPath);

        // get song duration
        $mp3Info = new \wapmorgan\Mp3Info\Mp3Info($newPath, true);
        $song->duration = round($mp3Info->duration);

        $songOutputFolderPath = "$currentSongFolderPath/output/$song->id";
        if (!File::exists($songOutputFolderPath)) {
            File::makeDirectory($songOutputFolderPath, $mode = 0777, true, false);
        }

        // 3 minutes
        ini_set('max_execution_time', 180);
        $cmd = "conda activate && python -m spleeter separate -i \"$newPath\" -o \"$currentSongFolderPath/output\"";
        exec($cmd);

        $songOutputFolderPath = "song/$song->id/output/$song->id";

        // convert to mp3s
        $cmd = "ffmpeg -i \"$songOutputFolderPath/accompaniment.wav\" -vn -ar 44100 -ac 2 -b:a 192k \"$songOutputFolderPath/accompaniment.mp3\"";
        exec($cmd);
        $cmd = "ffmpeg -i \"$songOutputFolderPath/vocals.wav\" -vn -ar 44100 -ac 2 -b:a 192k \"$songOutputFolderPath/vocals.mp3\"";
        exec($cmd);

        // update song in DB
        $song->accompaniment_path = "$songOutputFolderPath/accompaniment.mp3";
        $song->vocals_path = "$songOutputFolderPath/vocals.mp3";
        $song->save();

        $user = Auth::guard()->user();
        $favorite = new Favorite();
        $favorite->user_id = $user->id;
        $favorite->song_id = $song->id;
        $favorite->save();

        return response()->json(["status" => "ok"]);
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
        $search = new MusixmatchHelper();
        $track = $search->trackSearch($request->title, $request->artist)->formatTrackSearch()->result();
        $search = new MusixmatchHelper();
        $lyrics = $search->getLyrics($track->track_id)->formatLyricsGet()->result();

        $favorite = Favorite::find($id);
        $song = Song::find($favorite->song_id);
        if ($track) {
            $song->title = $track->track_name;
            $song->artist = $track->artist_name;
            $song->album = $track->album_name;
            $song->genre = $track->genre;
            $song->lyrics = $lyrics;
        } else {
            $song->title = $request->title;
            $song->artist = $request->artist;
            $song->album = "<unknown>";
            $song->genre = "<unknown>";
            $song->lyrics = "<unknown>";
        }
        $song->save();

        return response()->json(["status" => "ok"]);
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

        $song_id = $favorite->song_id;
        $favorite->delete();
        Song::destroy($song_id);

        return "OK";
    }
}
