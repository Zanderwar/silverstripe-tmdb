<?php
class GenreTest extends FunctionalTest {

    /**
     * Test \TMDB\Genres::inst()->getList() and assert that results are returned
     */
    public function testGetList() {
        $list = \TMDB\Genres::inst()->getList();
        $this->assertTrue((array_key_exists("genres", $list) && count($list["genres"]) > 0));
    }

    /**
     * Test \TMDB\Genres::inst()->getGenreById(28) and assert that "Action" is returned
     */
    public function testGetGenreById() {
        $result = \TMDB\Genres::inst()->getGenre(28);

        // assert that the key "id" is in the result
        $this->assertTrue(array_key_exists("id", $result));

        // assert that the key "name" is in the result
        $this->assertTrue(array_key_exists("name", $result));

        // asset that $result["name"] == 'Action'
        $this->assertTrue($result["name"] == "Action");

        // assert that the string to id conversion is working
        $result = \TMDB\Genres::inst()->getGenre('Action');
        $this->assertTrue($result["id"] == 28);

    }
}