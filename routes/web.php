<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/translate', function (Request $request) {
    $q = trim((string) $request->query('q', ''));
    $source = (string) $request->query('source', 'en');
    $target = (string) $request->query('target', 'ar');

    if ($q === '') {
        return response()->json(['error' => 'Enter text to translate.'], 422);
    }

    if ($source === 'auto') {
        $source = preg_match('/\p{Arabic}/u', $q) ? 'ar' : 'en';
    }

    $res = Http::timeout(25)
        ->acceptJson()
        ->get('https://api.mymemory.translated.net/get', [
            'q' => $q,
            'langpair' => $source.'|'.$target,
        ]);

    $text = $res->json('responseData.translatedText');
    if (! is_string($text) || $text === '' || $res->json('responseStatus') != 200) {
        return response()->json(['error' => 'Translation failed.'], 502);
    }

    return response()->json([
        'translatedText' => html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
    ]);
});
