<?php

use Illuminate\Support\Collection;
use Marvin\Ask\Facades\Ask;

it('can search for documents by vector', function () {
    $vector = Ask::embed('Palagano');

    $results = Ask::vectorialDatabase()->search($vector, filterResults: false);

    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results->isNotEmpty())->toBeTrue();
})->only();
