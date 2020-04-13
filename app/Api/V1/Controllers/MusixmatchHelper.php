<?php

namespace App\Api\V1\Controllers;


class MusixmatchHelper
{
    public $base_url;
    public $parameters;
    public $endpoint;

    private $api_key;
    private $formatter = null;

    public function __construct()
    {
        $this->api_key = config('laravel-musixmatch.api_key');
        $this->base_url = "https://api.musixmatch.com/ws/1.1/";
        $this->parameters = [
            'apikey' => $this->api_key,
        ];
    }

    ////
    // Static Helper Functions
    ////

    public function formatTrackSearch()
    {
        $this->formatter = function ($result) {
            if (count($result->track_list) <= 0) return null;
            $track = $result->track_list[0]->track;

            $genre = "";
            $genreList = $track->primary_genres->music_genre_list;
            for ($i = 0; $i < count($genreList); $i++) {
                if ($i > 0) $genre = $genre . ", ";
                $genre = $genre . $genreList[0]->music_genre->music_genre_name;
            }
            $track->genre = $genre;

            return $track;
        };

        return $this;
    }

    public function formatAlbumGet()
    {
        $this->formatter = function ($result) {
            return $result;
        };

        return $this;
    }

    public function formatLyricsGet()
    {
        $this->formatter = function ($result) {
            return $result->lyrics->lyrics_body;
        };

        return $this;
    }

    ////
    // API functions
    ////

    public function albumGet($album_id)
    {
        $this->parameters['album_id'] = $album_id;
        $this->endpoint = "album.get";

        return $this;
    }

    /**
     * Set the request endpoint to track.search
     */
    public function trackSearch($q_track = null, $q_artist = null, $q_lyrics = null)
    {
        if (!is_null($q_track)) {
            $this->parameters['q_track'] = $q_track;
        }

        if (!is_null($q_artist)) {
            $this->parameters['q_artist'] = $q_artist;
        }

        if (!is_null($q_lyrics)) {
            $this->parameters['q_lyrics'] = $q_lyrics;
        }

        $this->endpoint = "track.search";

        return $this;
    }

    public function getLyrics($track_id)
    {
        $this->endpoint = "track.lyrics.get";
        $this->parameters['track_id'] = $track_id;

        return $this;
    }

    /**
     * Calls the API to get the results
     */
    public function result()
    {
        $request_url = $this->createRequestUrl();

        $response = \Httpful\Request::get($request_url)->expectsJson()->send();

        $body = $this->formatApiResults($response);

        if ($this->formatter) {
            $formatter = $this->formatter;
            return $formatter($body);
        } else {
            return $body;
        }
    }

    ////
    // Utilities
    ////

    private function formatApiResults($result)
    {
        return $result->body->message->body;
    }

    /**
     * Creates the URL to make the request to the API
     */
    private function createRequestUrl()
    {
        $parameters = http_build_query($this->parameters);
        return "{$this->base_url}{$this->endpoint}?{$parameters}";
    }
}
