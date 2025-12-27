<?php

use Illuminate\Support\Collection;
use Marvin\Ask\Ask;

it('can search for documents by vector', function () {
    $vector = Ask::embed('Palagano');
    dump($vector);

    $results = Ask::vectorialDatabase()->search($vector, filterResults: false);
    dump($results);
    expect($results)->toBeInstanceOf(Collection::class)
        ->and($results->isNotEmpty())->toBeTrue();
});
