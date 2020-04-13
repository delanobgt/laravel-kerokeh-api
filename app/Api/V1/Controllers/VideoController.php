<?php

namespace App\Api\V1\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class VideoController extends Controller
{
    public function search(Request $request)
    {
        $searchTerm = $request->input('search');
        // $videoList = Youtube::searchVideos($searchTerm);
        // dd($videoList);

        // $video = Youtube::getVideoInfo('UR7mANKFDd4');
        // dd($video);

        $songName = "I like U";
        $songArtist = "NIKI";

        $search = new MusixmatchHelper();
        $track = $search->trackSearch($songName, $songArtist)->formatTrackSearch()->result();

        $albumId = $track->album_id;
        $search = new MusixmatchHelper();
        $album = $search->albumGet($albumId)->formatAlbumGet()->result();

        $search = new MusixmatchHelper();
        $lyrics = $search->getLyrics($track->track_id)->formatLyricsGet()->result();

        dd($lyrics);

        return response()->json($lyrics);
    }
}
