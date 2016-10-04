<?php
class GenreTest extends FunctionalTest {

    /**
     * Test \TMDB\Genres::inst()->getList()
     */
    public function testGetList() {
        $list = \TMDB\Genres::inst()->getList();
        $this->assertTrue((array_key_exists("genres", $list) && count($list["genres"]) > 0));
    }

    /**
     * Test \TMDB\Genres::inst()->getGenre()
     */
    public function testGetGenre() {
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

    /**
     * Test \TMDB\Genres::inst()->getTotalMoviesInGenre()
     */
    public function testGetTotalMoviesInGenre() {
        $movies = \TMDB\Genres::inst()->getTotalMoviesInGenre(28);

        // assert that a value larger than 0 is returned
        $this->assertTrue($movies > 0);
    }

    /**
     * Test \TMDB\Genres::inst()->isValidGenre()
     */
    public function testIsValidGenre() {
        // assert that 28 is a valid genre - it is...
        $this->assertTrue(\TMDB\Genres::inst()->isValidGenre(28));

        // assert that 'Action' is a valid genre - it is...
        $this->assertTrue(\TMDB\Genres::inst()->isValidGenre('Action'));

        // assert that 1337 is NOT a valid genre - it's not...
        $this->assertFalse(\TMDB\Genres::inst()->isValidGenre(1337));

        // assert that DOESNT_EXIST is NOT a valid genre - it's not..
        $this->assertFalse(\TMDB\Genres::inst()->isValidGenre('DOESNT_EXIST'));
    }
}