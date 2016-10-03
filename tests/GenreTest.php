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
        $result = \TMDB\Genres::inst()->getGenreById(28);

        $this->assertTrue($result == "Action");
    }

    /**
     * Test \TMDB\Genres::inst()->getGenreIdByName("Action") and assert that 28 is returned
     */
    public function testGetGenreIdByName() {
        $result = \TMDB\Genres::inst()->getGenreIdByName("Action");

        $this->assertTrue($result == 28);
    }
    
    public function testGetTotalMoviesInGenre() {
        $result = \TMDB\Genres::inst()->getTotalMoviesInGenre(28);
    }
}