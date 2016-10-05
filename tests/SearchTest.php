<?php

class SearchTest extends SapphireTest
{

    public function testMovies()
    {

        $movies = new \TMDB\Search\Movies();
        
        // I actually thought about testing against how many movies Tom Hanks comes up in. So that if he was ever in a
        // new one, this test would fail lol. I really am way too verbal in these comments...
        $search = $movies->setParams(
            array(
                "query" => "Tom Hanks"
            )
        )->request();

        $this->assertTrue(!empty($search->results), "We didn't receive any results");

    }
}