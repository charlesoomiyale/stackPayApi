<?php

class TvTest extends TestCase
{
    /** @test */
    public function testGetDataProviders()
    {
        $response = $this->call('GET','/v1/tv/providers');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }

    /** @test */
    public function testGetDataProviderDstv()
    {
        $response = $this->call('GET','/v1/data/provider/dstv');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }


    /** @test */
    public function testGetDataProviderGotv()
    {
        $response = $this->call('GET','/v1/data/provider/gotv');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }


    /** @test */
    public function testGetDataProviderStartimes()
    {
        $response = $this->call('GET','/v1/data/provider/startimes');

        $response->isSuccessful();
        $this->seeJsonStructure([
            'message','data', "status"
        ]);
    }

}
